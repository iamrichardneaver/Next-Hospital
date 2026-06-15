<?php

namespace App\Services;

use App\Models\BedAssignment;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Patient;
use App\Models\Invoice;
use App\Models\ServicePricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WardBillingService
{
    protected $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Calculate bed charges for an admission.
     * 
     * @param BedAssignment $assignment
     * @return array
     */
    public function calculateBedCharges(BedAssignment $assignment)
    {
        $bed = $assignment->bed;
        $ward = $assignment->ward;
        $patient = $assignment->patient;
        
        // Calculate number of days
        $admissionDate = Carbon::parse($assignment->admission_date);
        $dischargeDate = $assignment->discharge_date ? Carbon::parse($assignment->discharge_date) : now();
        $days = $admissionDate->diffInDays($dischargeDate);
        
        // Minimum 1 day charge even for same-day admission/discharge
        $days = max(1, $days);
        
        // Get bed/ward pricing from service_pricing table
        $dailyRate = $this->getBedDailyRate($ward, $bed, $patient->id, $assignment->bed->branch_id ?? auth()->user()->branches()->first()->id);
        
        $totalCharge = $days * $dailyRate;
        
        return [
            'admission_date' => $admissionDate->toDateString(),
            'discharge_date' => $dischargeDate->toDateString(),
            'days' => $days,
            'daily_rate' => $dailyRate,
            'total_charge' => $totalCharge,
            'ward_name' => $ward->name,
            'ward_type' => $ward->type,
            'bed_number' => $bed->bed_number ?? 'N/A'
        ];
    }

    /**
     * Get daily rate for a bed/ward.
     * 
     * @param Ward $ward
     * @param Bed $bed
     * @param int $patientId
     * @param int $branchId
     * @return float
     */
    protected function getBedDailyRate(Ward $ward, Bed $bed, $patientId, $branchId)
    {
        // Build service ID based on ward type
        $serviceId = 'BED_' . strtoupper($ward->type);
        
        // Try to get from service_pricing table
        $pricing = ServicePricing::where('service_id', $serviceId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
        
        if ($pricing) {
            // Use PricingService to apply any rules/discounts
            try {
                $calculatedPricing = $this->pricingService->calculateServicePrice(
                    $serviceId,
                    $patientId,
                    $branchId,
                    ['ward_type' => $ward->type]
                );
                return $calculatedPricing['final_price'] ?? $pricing->base_price;
            } catch (\Exception $e) {
                return $pricing->base_price;
            }
        }
        
        // Fallback to default rates by ward type
        return $this->getDefaultWardRate($ward->type);
    }

    /**
     * Get default ward rate (fallback when service_pricing not configured).
     * 
     * @param string $wardType
     * @return float
     */
    protected function getDefaultWardRate($wardType)
    {
        return match($wardType) {
            'icu' => 200.00,
            'isolation' => 150.00,
            'maternity' => 100.00,
            'pediatric' => 80.00,
            'private' => 80.00,
            'male' => 50.00,
            'female' => 50.00,
            'general' => 50.00,
            default => 50.00
        };
    }

    /**
     * Generate invoice for admission/discharge.
     * 
     * @param BedAssignment $assignment
     * @param array $additionalCharges
     * @return Invoice
     */
    public function generateAdmissionInvoice(BedAssignment $assignment, array $additionalCharges = [])
    {
        DB::beginTransaction();
        
        try {
            // Calculate bed charges
            $bedCharges = $this->calculateBedCharges($assignment);
            
            $items = [];
            $totalAmount = 0;
            
            // Add bed/ward charge
            $items[] = [
                'id' => 'item_' . uniqid(),
                'description' => "Ward Accommodation - {$bedCharges['ward_name']} ({$bedCharges['days']} days)",
                'quantity' => $bedCharges['days'],
                'unit_price' => $bedCharges['daily_rate'],
                'total' => $bedCharges['total_charge'],
                'service_type' => 'ward',
                'ward_id' => $assignment->ward_id,
                'bed_id' => $assignment->bed_id
            ];
            $totalAmount += $bedCharges['total_charge'];
            
            // Add additional charges (nursing care, meals, supplies, etc.)
            foreach ($additionalCharges as $charge) {
                $chargeTotal = ($charge['quantity'] ?? 1) * ($charge['unit_price'] ?? 0);
                
                $items[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => $charge['description'],
                    'quantity' => $charge['quantity'] ?? 1,
                    'unit_price' => $charge['unit_price'] ?? 0,
                    'total' => $chargeTotal,
                    'service_type' => $charge['service_type'] ?? 'ward'
                ];
                $totalAmount += $chargeTotal;
            }
            
            // Create invoice
            $invoice = Invoice::create([
                'patient_id' => $assignment->patient_id,
                'branch_id' => $assignment->bed->branch_id ?? auth()->user()->branches()->first()->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(), // 7 days to pay after discharge
                'items' => $items,
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => "Admission/Discharge Invoice - Ward: {$bedCharges['ward_name']}, Bed Assignment #{$assignment->id}",
                'created_by' => auth()->id() ?? 1
            ]);
            
            Log::info('Ward/admission invoice created', [
                'assignment_id' => $assignment->id,
                'invoice_id' => $invoice->id,
                'patient_id' => $assignment->patient_id,
                'days' => $bedCharges['days'],
                'total_amount' => $totalAmount
            ]);
            
            DB::commit();
            
            return $invoice;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to generate admission invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate discharge invoice with all accumulated charges.
     * 
     * @param int $assignmentId
     * @param array $additionalCharges
     * @return Invoice
     */
    public function generateDischargeInvoice($assignmentId, array $additionalCharges = [])
    {
        $assignment = BedAssignment::with(['patient', 'ward', 'bed'])->findOrFail($assignmentId);
        
        if ($assignment->status !== 'active') {
            throw new \Exception('Can only generate discharge invoice for active assignments');
        }
        
        // Set discharge date to now if not set
        if (!$assignment->discharge_date) {
            $assignment->discharge_date = now();
            $assignment->save();
        }
        
        return $this->generateAdmissionInvoice($assignment, $additionalCharges);
    }

    /**
     * Calculate accumulated charges for active admission (preview before discharge).
     * 
     * @param int $assignmentId
     * @return array
     */
    public function calculateCurrentCharges($assignmentId)
    {
        $assignment = BedAssignment::with(['patient', 'ward', 'bed'])->findOrFail($assignmentId);
        
        if ($assignment->status !== 'active') {
            throw new \Exception('Assignment is not active');
        }
        
        // Calculate charges as if discharging today
        $tempAssignment = clone $assignment;
        $tempAssignment->discharge_date = now();
        
        return $this->calculateBedCharges($tempAssignment);
    }

    /**
     * Batch generate invoices for all discharges on a specific date.
     * 
     * @param string $date
     * @param int $branchId
     * @return array
     */
    public function batchGenerateDischargeInvoices($date = null, $branchId = null)
    {
        $date = $date ?? now()->toDateString();
        
        $query = BedAssignment::with(['patient', 'ward', 'bed'])
            ->where('status', 'discharged')
            ->whereDate('discharge_date', $date);
        
        if ($branchId) {
            $query->whereHas('bed', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }
        
        $assignments = $query->get();
        
        $results = [];
        $successCount = 0;
        $failCount = 0;
        
        foreach ($assignments as $assignment) {
            try {
                // Check if invoice already exists for this assignment
                $existingInvoice = Invoice::where('patient_id', $assignment->patient_id)
                    ->where('notes', 'LIKE', '%Bed Assignment #' . $assignment->id . '%')
                    ->first();
                
                if ($existingInvoice) {
                    $results[] = [
                        'assignment_id' => $assignment->id,
                        'status' => 'skipped',
                        'message' => 'Invoice already exists',
                        'invoice_id' => $existingInvoice->id
                    ];
                    continue;
                }
                
                $invoice = $this->generateAdmissionInvoice($assignment);
                
                $results[] = [
                    'assignment_id' => $assignment->id,
                    'status' => 'success',
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total_amount
                ];
                $successCount++;
                
            } catch (\Exception $e) {
                $results[] = [
                    'assignment_id' => $assignment->id,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                $failCount++;
            }
        }
        
        return [
            'total' => count($assignments),
            'success' => $successCount,
            'failed' => $failCount,
            'results' => $results
        ];
    }

    /**
     * Get ward pricing for a specific branch.
     * 
     * @param int $branchId
     * @return array
     */
    public function getWardPricing($branchId)
    {
        $pricing = ServicePricing::where('service_type', 'bed')
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->get()
            ->keyBy('service_id');
        
        $wards = Ward::where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();
        
        $wardPricing = [];
        foreach ($wards as $ward) {
            $serviceId = 'BED_' . strtoupper($ward->type);
            $rate = $pricing[$serviceId]->base_price ?? $this->getDefaultWardRate($ward->type);
            
            $wardPricing[] = [
                'ward_id' => $ward->id,
                'ward_name' => $ward->name,
                'ward_type' => $ward->type,
                'daily_rate' => $rate,
                'has_custom_pricing' => isset($pricing[$serviceId])
            ];
        }
        
        return $wardPricing;
    }
}

