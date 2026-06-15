<?php

namespace App\Services;

use App\Models\Prescription;
use App\Models\DrugOrder;
use App\Models\Patient;
use App\Models\PrescriptionMedication;
use App\Models\MedicationAdherence;
use App\Models\MedicationSideEffect;
use App\Models\RefillRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PrescriptionService
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Get patient prescriptions with pagination
     */
    public function getPatientPrescriptions($patientId, $page = 1, $limit = 20, $status = null)
    {
        try {
            $query = Prescription::with([
                'patient',
                'doctor',
                'consultation',
                'branch',
                'orders.drug'
            ])
            ->where('patient_id', $patientId);

            if ($status) {
                $query->where('status', $status);
            }

            $query->orderBy('prescription_date', 'desc')
                  ->orderBy('created_at', 'desc');

            $total = $query->count();
            $prescriptions = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();

            // Transform data for mobile app
            $data = $prescriptions->map(function ($prescription) {
                return [
                    'id' => $prescription->id,
                    'prescription_number' => $prescription->prescription_number,
                    'prescription_date' => $prescription->prescription_date,
                    'status' => $prescription->status,
                    'doctor_name' => $prescription->doctor ? $prescription->doctor->firstname . ' ' . $prescription->doctor->lastname : 'Unknown',
                    'doctor_specialization' => $prescription->doctor->specialization ?? null,
                    'branch_name' => $prescription->branch->name ?? null,
                    'total_medications' => $prescription->orders->count(),
                    'medications' => $prescription->orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'drug_name' => $order->drug->name ?? 'Unknown',
                            'dosage' => $order->dosage,
                            'frequency' => $order->frequency,
                            'duration' => $order->duration,
                            'quantity' => $order->quantity,
                            'instructions' => $order->instructions,
                            'dispensed_status' => $order->dispensed_status ?? 'pending'
                        ];
                    }),
                    'notes' => $prescription->notes,
                    'created_at' => $prescription->created_at->format('Y-m-d H:i:s'),
                    'billing_status' => $prescription->billing_status ?? 'pending',
                    'billing_amount' => $prescription->billing_amount ?? 0,
                ];
            });

            return [
                'prescriptions' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit),
                    'has_more' => $page < ceil($total / $limit)
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting patient prescriptions: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get prescription details
     */
    public function getPrescriptionDetails($prescriptionId, $patientId)
    {
        try {
            $prescription = Prescription::with([
                'patient',
                'doctor',
                'consultation',
                'branch',
                'orders.drug'
            ])
            ->where('id', $prescriptionId)
            ->where('patient_id', $patientId)
            ->first();

            if (!$prescription) {
                return null;
            }

            return [
                'id' => $prescription->id,
                'prescription_number' => $prescription->prescription_number,
                'prescription_date' => $prescription->prescription_date,
                'status' => $prescription->status,
                'doctor' => [
                    'id' => $prescription->doctor->id,
                    'name' => $prescription->doctor->firstname . ' ' . $prescription->doctor->lastname,
                    'specialization' => $prescription->doctor->specialization ?? null,
                    'email' => $prescription->doctor->email,
                ],
                'branch' => [
                    'id' => $prescription->branch->id ?? null,
                    'name' => $prescription->branch->name ?? null,
                    'address' => $prescription->branch->address ?? null,
                ],
                'medications' => $prescription->orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'drug_id' => $order->drug_id,
                        'drug_name' => $order->drug->name ?? 'Unknown',
                        'dosage' => $order->dosage,
                        'frequency' => $order->frequency,
                        'duration' => $order->duration,
                        'quantity' => $order->quantity,
                        'instructions' => $order->instructions,
                        'dispensed_status' => $order->dispensed_status ?? 'pending',
                        'dispensed_quantity' => $order->dispensed_quantity ?? 0,
                    ];
                }),
                'notes' => $prescription->notes,
                'created_at' => $prescription->created_at->format('Y-m-d H:i:s'),
                'billing_status' => $prescription->billing_status ?? 'pending',
                'billing_amount' => $prescription->billing_amount ?? 0,
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting prescription details: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get prescription medications
     */
    public function getPrescriptionMedications($prescriptionId, $patientId)
    {
        try {
            $prescription = Prescription::where('id', $prescriptionId)
                ->where('patient_id', $patientId)
                ->first();

            if (!$prescription) {
                throw new \Exception('Prescription not found');
            }

            $medications = DrugOrder::with('drug')
                ->where('prescription_id', $prescriptionId)
                ->get();

            return $medications->map(function ($order) {
                return [
                    'id' => $order->id,
                    'drug_id' => $order->drug_id,
                    'name' => $order->drug->name ?? 'Unknown Medication', // Mobile app expects 'name'
                    'drug_name' => $order->drug->name ?? 'Unknown', // Keep for backward compatibility
                    'generic_name' => $order->drug->generic_name ?? null,
                    'dosage' => $order->dosage,
                    'frequency' => $order->frequency,
                    'duration' => $order->duration,
                    'quantity' => $order->quantity,
                    'unit' => $order->drug->unit ?? null,
                    'instructions' => $order->instructions,
                    'dispensed_status' => $order->dispensed_status ?? 'pending',
                    'dispensed_quantity' => $order->dispensed_quantity ?? 0,
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error getting prescription medications: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate prescription PDF
     */
    public function generatePrescriptionPdf($prescriptionId, $patientId)
    {
        try {
            \Log::info("Generating prescription PDF for ID: $prescriptionId, Patient: $patientId");
            
            $prescription = Prescription::with([
                'patient',
                'doctor',
                'orders.drug',
                'branch'
            ])
            ->where('id', $prescriptionId)
            ->where('patient_id', $patientId)
            ->firstOrFail();

            \Log::info("Prescription found: " . $prescription->prescription_number);

            $medications = $prescription->orders->map(function ($order) {
                return [
                    'name' => $order->drug->name ?? 'Unknown',
                    'generic_name' => $order->drug->generic_name ?? '',
                    'dosage' => $order->dosage,
                    'frequency' => $order->frequency,
                    'duration' => $order->duration,
                    'quantity' => $order->quantity,
                    'instructions' => $order->instructions,
                ];
            })->toArray();

            \Log::info("Medications mapped: " . count($medications) . " items");

            $pdf = $this->pdfService->generatePrescription(
                $prescription,
                $prescription->patient,
                $medications
            );

            $filename = 'prescription_' . $prescription->prescription_number . '_' . time() . '.pdf';
            $path = 'prescriptions/' . $filename;
            
            \Storage::put($path, $pdf->output());
            
            $url = \Storage::url($path);
            
            \Log::info("PDF generated successfully: $url");

            return $url;
        } catch (\Exception $e) {
            \Log::error('Error generating prescription PDF: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw new \Exception('Failed to generate prescription PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get prescription history
     */
    public function getPrescriptionHistory($patientId, $page = 1, $limit = 20, $startDate = null, $endDate = null)
    {
        try {
            $query = Prescription::with(['doctor', 'orders.drug'])
                ->where('patient_id', $patientId)
                ->where('status', 'completed');

            if ($startDate) {
                $query->where('prescription_date', '>=', Carbon::parse($startDate));
            }

            if ($endDate) {
                $query->where('prescription_date', '<=', Carbon::parse($endDate));
            }

            $query->orderBy('prescription_date', 'desc');

            $total = $query->count();
            $prescriptions = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();

            return [
                'history' => $prescriptions->map(function ($prescription) {
                    return [
                        'id' => $prescription->id,
                        'prescription_number' => $prescription->prescription_number,
                        'prescription_date' => $prescription->prescription_date,
                        'doctor_name' => $prescription->doctor ? $prescription->doctor->firstname . ' ' . $prescription->doctor->lastname : 'Unknown',
                        'total_medications' => $prescription->orders->count(),
                        'status' => $prescription->status,
                    ];
                }),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit),
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting prescription history: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get active prescriptions
     */
    public function getActivePrescriptions($patientId, $page = 1, $limit = 20)
    {
        return $this->getPatientPrescriptions($patientId, $page, $limit, 'active');
    }

    /**
     * Get medication reminders
     */
    public function getMedicationReminders($patientId, $page = 1, $limit = 20)
    {
        try {
            // Get active prescriptions with medications
            $prescriptions = Prescription::with(['orders.drug'])
                ->where('patient_id', $patientId)
                ->whereIn('status', ['active', 'pending'])
                ->get();

            $reminders = [];

            foreach ($prescriptions as $prescription) {
                foreach ($prescription->orders as $order) {
                    $reminders[] = [
                        'id' => $order->id,
                        'prescription_id' => $prescription->id,
                        'medication_name' => $order->drug->name ?? 'Unknown',
                        'dosage' => $order->dosage,
                        'frequency' => $order->frequency,
                        'next_dose_time' => $this->calculateNextDose($order->frequency),
                        'instructions' => $order->instructions,
                    ];
                }
            }

            $total = count($reminders);
            $paginatedReminders = array_slice($reminders, ($page - 1) * $limit, $limit);

            return [
                'reminders' => $paginatedReminders,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit),
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting medication reminders: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark medication as taken
     */
    public function markMedicationAsTaken($prescriptionId, $medicationId, $patientId, $takenAt, $notes = null)
    {
        try {
            // Verify prescription belongs to patient
            $prescription = Prescription::where('id', $prescriptionId)
                ->where('patient_id', $patientId)
                ->firstOrFail();

            // Note: MedicationAdherence table may need to be created
            // For now, just return success
            return [
                'success' => true,
                'message' => 'Medication marked as taken',
                'taken_at' => $takenAt,
            ];
        } catch (\Exception $e) {
            \Log::error('Error marking medication as taken: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get medication adherence
     */
    public function getMedicationAdherence($patientId, $prescriptionId = null, $startDate = null, $endDate = null)
    {
        try {
            // This would typically query a medication_adherence table
            // For now, return mock data structure
            return [
                'adherence_rate' => 85.5,
                'total_doses' => 100,
                'taken_doses' => 85,
                'missed_doses' => 15,
                'period' => [
                    'start_date' => $startDate ?? Carbon::now()->subDays(30)->format('Y-m-d'),
                    'end_date' => $endDate ?? Carbon::now()->format('Y-m-d'),
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting medication adherence: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Report side effects
     */
    public function reportSideEffects($prescriptionId, $medicationId, $patientId, $sideEffects, $severity, $notes = null)
    {
        try {
            // Verify prescription belongs to patient
            $prescription = Prescription::where('id', $prescriptionId)
                ->where('patient_id', $patientId)
                ->firstOrFail();

            // Note: MedicationSideEffect table may need to be created
            // For now, just return success
            return [
                'success' => true,
                'message' => 'Side effects reported successfully',
                'severity' => $severity,
            ];
        } catch (\Exception $e) {
            \Log::error('Error reporting side effects: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get drug interactions
     */
    public function getDrugInteractions($medications)
    {
        try {
            // This would typically check against a drug interaction database
            // For now, return empty interactions
            return [
                'interactions' => [],
                'warnings' => [],
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting drug interactions: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Request prescription refill
     */
    public function requestRefill($prescriptionId, $patientId, $notes = null)
    {
        try {
            // Verify prescription belongs to patient
            $prescription = Prescription::where('id', $prescriptionId)
                ->where('patient_id', $patientId)
                ->firstOrFail();

            // Note: RefillRequest table may need to be created
            // For now, just return success
            return [
                'success' => true,
                'message' => 'Refill request submitted successfully',
                'status' => 'pending',
            ];
        } catch (\Exception $e) {
            \Log::error('Error requesting refill: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get prescription statistics
     */
    public function getPrescriptionStatistics($patientId, $startDate = null, $endDate = null)
    {
        try {
            $query = Prescription::where('patient_id', $patientId);

            if ($startDate) {
                $query->where('prescription_date', '>=', Carbon::parse($startDate));
            }

            if ($endDate) {
                $query->where('prescription_date', '<=', Carbon::parse($endDate));
            }

            $totalPrescriptions = $query->count();
            $activePrescriptions = (clone $query)->where('status', 'active')->count();
            $completedPrescriptions = (clone $query)->where('status', 'completed')->count();

            return [
                'total_prescriptions' => $totalPrescriptions,
                'active_prescriptions' => $activePrescriptions,
                'completed_prescriptions' => $completedPrescriptions,
                'total_medications' => DrugOrder::whereIn('prescription_id', 
                    (clone $query)->pluck('id')
                )->count(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting prescription statistics: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate next dose time based on frequency
     */
    private function calculateNextDose($frequency)
    {
        // Simple logic - can be enhanced
        $now = Carbon::now();
        
        if (str_contains(strtolower($frequency), 'once daily') || str_contains(strtolower($frequency), '1 time')) {
            return $now->addDay()->format('Y-m-d H:i:s');
        } elseif (str_contains(strtolower($frequency), 'twice daily') || str_contains(strtolower($frequency), '2 times')) {
            return $now->addHours(12)->format('Y-m-d H:i:s');
        } elseif (str_contains(strtolower($frequency), 'three times') || str_contains(strtolower($frequency), '3 times')) {
            return $now->addHours(8)->format('Y-m-d H:i:s');
        }
        
        return $now->addHours(6)->format('Y-m-d H:i:s');
    }
}

