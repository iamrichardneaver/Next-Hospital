<?php

namespace App\Services;

use App\Models\SurgerySchedule;
use App\Models\Patient;
use App\Models\Invoice;
use App\Models\ServicePricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SurgeryBillingService
{
    protected $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Calculate surgery costs including all components.
     * 
     * @param SurgerySchedule $surgery
     * @param int $branchId
     * @return array
     */
    public function calculateSurgeryCosts(SurgerySchedule $surgery, $branchId)
    {
        $costs = [
            'procedure_fee' => $this->getProcedureFee($surgery, $branchId),
            'theatre_charges' => $this->getTheatreCharges($surgery, $branchId),
            'anesthesia_fee' => $this->getAnesthesiaFee($surgery, $branchId),
            'recovery_room_charges' => $this->getRecoveryRoomCharges($surgery, $branchId),
            'supplies_cost' => $this->getSuppliesCost($surgery, $branchId),
            'team_charges' => $this->getTeamCharges($surgery, $branchId)
        ];
        
        $costs['total'] = array_sum($costs);
        
        return $costs;
    }

    /**
     * Get procedure fee from service_pricing table.
     */
    protected function getProcedureFee(SurgerySchedule $surgery, $branchId)
    {
        if ($surgery->procedure_id) {
            // Try to find procedure-specific pricing
            $serviceId = 'PROC_' . $surgery->procedure_id;
        } else {
            // Use surgery type for pricing
            $serviceId = 'SURGERY_' . strtoupper($surgery->surgery_type ?? 'GENERAL');
        }
        
        try {
            $pricing = $this->pricingService->calculateServicePrice(
                $serviceId,
                $surgery->patient_id,
                $branchId,
                [
                    'surgery_type' => $surgery->surgery_type,
                    'priority' => $surgery->priority
                ]
            );
            return $pricing['final_price'] ?? $this->getDefaultProcedureFee($surgery->surgery_type);
        } catch (\Exception $e) {
            return $this->getDefaultProcedureFee($surgery->surgery_type);
        }
    }

    /**
     * Get theatre charges based on usage time.
     */
    protected function getTheatreCharges(SurgerySchedule $surgery, $branchId)
    {
        // Calculate theatre usage in hours
        $hours = 1; // Default
        
        if ($surgery->actual_start_time && $surgery->actual_end_time) {
            $start = Carbon::parse($surgery->actual_start_time);
            $end = Carbon::parse($surgery->actual_end_time);
            $hours = ceil($start->diffInMinutes($end) / 60);
        } elseif ($surgery->estimated_duration) {
            $hours = ceil($surgery->estimated_duration / 60); // Convert minutes to hours
        }
        
        // Get theatre hourly rate from service_pricing
        $theatreId = $surgery->theatre_id ? 'THEATRE_' . $surgery->theatre_id : 'THEATRE_GENERAL';
        
        $pricing = ServicePricing::where('service_id', $theatreId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
        
        $hourlyRate = $pricing->base_price ?? 100.00; // Default GHS 100/hour
        
        return $hours * $hourlyRate;
    }

    /**
     * Get anesthesia fee based on type and duration.
     */
    protected function getAnesthesiaFee(SurgerySchedule $surgery, $branchId)
    {
        if (!$surgery->anesthesia_type || $surgery->anesthesia_type === 'none') {
            return 0;
        }
        
        // Calculate anesthesia duration
        $hours = 1; // Default
        
        if ($surgery->anesthesia_start_time && $surgery->anesthesia_end_time) {
            $start = Carbon::parse($surgery->anesthesia_start_time);
            $end = Carbon::parse($surgery->anesthesia_end_time);
            $hours = ceil($start->diffInMinutes($end) / 60);
        }
        
        $serviceId = 'ANESTHESIA_' . strtoupper($surgery->anesthesia_type);
        
        $pricing = ServicePricing::where('service_id', $serviceId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
        
        $hourlyRate = $pricing->base_price ?? $this->getDefaultAnesthesiaFee($surgery->anesthesia_type);
        
        return $hours * $hourlyRate;
    }

    /**
     * Get recovery room charges.
     */
    protected function getRecoveryRoomCharges(SurgerySchedule $surgery, $branchId)
    {
        if (!$surgery->recovery_room_time) {
            return 0;
        }
        
        // Calculate hours in recovery
        $admissionTime = $surgery->recovery_room_time;
        $dischargeTime = now(); // Assume discharged now for calculation
        
        $start = Carbon::parse($admissionTime);
        $hours = ceil($start->diffInMinutes($dischargeTime) / 60);
        
        $pricing = ServicePricing::where('service_id', 'RECOVERY_ROOM')
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
        
        $hourlyRate = $pricing->base_price ?? 50.00; // Default GHS 50/hour
        
        return $hours * $hourlyRate;
    }

    /**
     * Get supplies cost (consumables, instruments, etc.).
     */
    protected function getSuppliesCost(SurgerySchedule $surgery, $branchId)
    {
        // This could be tracked separately in surgery_supplies table
        // For now, use a percentage of procedure fee or fixed amount
        $procedureFee = $this->getProcedureFee($surgery, $branchId);
        
        // 20% of procedure fee for supplies
        return $procedureFee * 0.20;
    }

    /**
     * Get team charges (assistant surgeons, nurses, etc.).
     */
    protected function getTeamCharges(SurgerySchedule $surgery, $branchId)
    {
        // If surgery team is tracked, calculate based on team size and roles
        if ($surgery->team && $surgery->team->count() > 0) {
            $teamCost = 0;
            foreach ($surgery->team as $member) {
                $teamCost += $this->getTeamMemberFee($member->role, $branchId);
            }
            return $teamCost;
        }
        
        // Default team charge
        return 100.00;
    }

    /**
     * Get team member fee by role.
     */
    protected function getTeamMemberFee($role, $branchId)
    {
        $serviceId = 'TEAM_' . strtoupper($role);
        
        $pricing = ServicePricing::where('service_id', $serviceId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
        
        return $pricing->base_price ?? 50.00;
    }

    /**
     * Generate invoice for surgery.
     * 
     * @param int $surgeryId
     * @param int $branchId
     * @param array $additionalCharges
     * @return Invoice
     */
    public function generateSurgeryInvoice($surgeryId, $branchId, array $additionalCharges = [])
    {
        DB::beginTransaction();
        
        try {
            $surgery = SurgerySchedule::with(['patient', 'surgeon', 'procedure', 'team'])->findOrFail($surgeryId);
            
            // Calculate all surgery costs
            $costs = $this->calculateSurgeryCosts($surgery, $branchId);
            
            $items = [];
            $totalAmount = 0;
            
            // Add main procedure fee
            if ($costs['procedure_fee'] > 0) {
                $items[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => 'Surgical Procedure - ' . ($surgery->surgery_type ?? 'General Surgery'),
                    'quantity' => 1,
                    'unit_price' => $costs['procedure_fee'],
                    'total' => $costs['procedure_fee'],
                    'service_type' => 'surgery'
                ];
                $totalAmount += $costs['procedure_fee'];
            }
            
            // Add theatre charges
            if ($costs['theatre_charges'] > 0) {
                $items[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => 'Operating Theatre Charges',
                    'quantity' => 1,
                    'unit_price' => $costs['theatre_charges'],
                    'total' => $costs['theatre_charges'],
                    'service_type' => 'surgery'
                ];
                $totalAmount += $costs['theatre_charges'];
            }
            
            // Add anesthesia fee
            if ($costs['anesthesia_fee'] > 0) {
                $items[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => 'Anesthesia Services - ' . ucfirst($surgery->anesthesia_type ?? 'General'),
                    'quantity' => 1,
                    'unit_price' => $costs['anesthesia_fee'],
                    'total' => $costs['anesthesia_fee'],
                    'service_type' => 'surgery'
                ];
                $totalAmount += $costs['anesthesia_fee'];
            }
            
            // Add recovery room charges
            if ($costs['recovery_room_charges'] > 0) {
                $items[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => 'Recovery Room Charges',
                    'quantity' => 1,
                    'unit_price' => $costs['recovery_room_charges'],
                    'total' => $costs['recovery_room_charges'],
                    'service_type' => 'surgery'
                ];
                $totalAmount += $costs['recovery_room_charges'];
            }
            
            // Add supplies cost
            if ($costs['supplies_cost'] > 0) {
                $items[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => 'Medical Supplies & Consumables',
                    'quantity' => 1,
                    'unit_price' => $costs['supplies_cost'],
                    'total' => $costs['supplies_cost'],
                    'service_type' => 'surgery'
                ];
                $totalAmount += $costs['supplies_cost'];
            }
            
            // Add team charges
            if ($costs['team_charges'] > 0) {
                $items[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => 'Surgical Team Charges',
                    'quantity' => 1,
                    'unit_price' => $costs['team_charges'],
                    'total' => $costs['team_charges'],
                    'service_type' => 'surgery'
                ];
                $totalAmount += $costs['team_charges'];
            }
            
            // Add any additional charges
            foreach ($additionalCharges as $charge) {
                $chargeTotal = ($charge['quantity'] ?? 1) * ($charge['unit_price'] ?? 0);
                
                $items[] = [
                    'id' => 'item_' . uniqid(),
                    'description' => $charge['description'],
                    'quantity' => $charge['quantity'] ?? 1,
                    'unit_price' => $charge['unit_price'] ?? 0,
                    'total' => $chargeTotal,
                    'service_type' => $charge['service_type'] ?? 'surgery'
                ];
                $totalAmount += $chargeTotal;
            }
            
            // Create invoice
            $invoice = Invoice::create([
                'patient_id' => $surgery->patient_id,
                'branch_id' => $branchId,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(), // 14 days to pay after surgery
                'items' => $items,
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => "Surgery Invoice - " . ($surgery->surgery_type ?? 'Procedure') . " - Surgery Schedule #{$surgery->id}",
                'created_by' => auth()->id() ?? 1
            ]);
            
            Log::info('Surgery invoice created', [
                'surgery_id' => $surgery->id,
                'invoice_id' => $invoice->id,
                'patient_id' => $surgery->patient_id,
                'total_amount' => $totalAmount,
                'cost_breakdown' => $costs
            ]);
            
            DB::commit();
            
            return [
                'invoice' => $invoice,
                'cost_breakdown' => $costs,
                'total_amount' => $totalAmount
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to generate surgery invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get default procedure fee (fallback).
     */
    protected function getDefaultProcedureFee($surgeryType)
    {
        return match(strtolower($surgeryType ?? '')) {
            'major' => 2000.00,
            'minor' => 500.00,
            'emergency' => 1500.00,
            'elective' => 1000.00,
            'laparoscopic' => 2500.00,
            'orthopedic' => 1800.00,
            'cardiac' => 5000.00,
            'neurosurgery' => 6000.00,
            default => 1000.00
        };
    }

    /**
     * Get default anesthesia fee by type.
     */
    protected function getDefaultAnesthesiaFee($anesthesiaType)
    {
        return match(strtolower($anesthesiaType ?? '')) {
            'general' => 300.00,
            'spinal' => 200.00,
            'epidural' => 250.00,
            'local' => 50.00,
            'sedation' => 150.00,
            default => 200.00
        };
    }

    /**
     * Get surgery pricing for a specific branch.
     * 
     * @param int $branchId
     * @return array
     */
    public function getSurgeryPricing($branchId)
    {
        $pricing = ServicePricing::where('service_type', 'surgery')
            ->orWhere('service_type', 'procedure')
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();
        
        return $pricing->map(function($item) {
            return [
                'service_id' => $item->service_id,
                'service_name' => $item->service_name,
                'base_price' => $item->base_price,
                'currency' => $item->currency,
                'description' => $item->description
            ];
        })->toArray();
    }

    /**
     * Generate batch invoices for completed surgeries.
     * 
     * @param string $date
     * @param int $branchId
     * @return array
     */
    public function batchGenerateSurgeryInvoices($date = null, $branchId = null)
    {
        $date = $date ?? now()->toDateString();
        
        $query = SurgerySchedule::with(['patient'])
            ->where('status', 'completed')
            ->whereDate('surgery_date', $date);
        
        $surgeries = $query->get();
        
        $results = [];
        $successCount = 0;
        $failCount = 0;
        
        foreach ($surgeries as $surgery) {
            try {
                // Check if invoice already exists
                $existingInvoice = Invoice::where('patient_id', $surgery->patient_id)
                    ->where('notes', 'LIKE', '%Surgery Schedule #' . $surgery->id . '%')
                    ->first();
                
                if ($existingInvoice) {
                    $results[] = [
                        'surgery_id' => $surgery->id,
                        'status' => 'skipped',
                        'message' => 'Invoice already exists',
                        'invoice_id' => $existingInvoice->id
                    ];
                    continue;
                }
                
                $result = $this->generateSurgeryInvoice($surgery->id, $branchId ?? 1);
                
                $results[] = [
                    'surgery_id' => $surgery->id,
                    'status' => 'success',
                    'invoice_id' => $result['invoice']->id,
                    'amount' => $result['total_amount']
                ];
                $successCount++;
                
            } catch (\Exception $e) {
                $results[] = [
                    'surgery_id' => $surgery->id,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                $failCount++;
            }
        }
        
        return [
            'total' => count($surgeries),
            'success' => $successCount,
            'failed' => $failCount,
            'results' => $results
        ];
    }
}

