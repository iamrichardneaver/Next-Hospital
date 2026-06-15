<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\RadiologyRequest;
use App\Models\EmergencyAlert;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RevenueTransaction;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PatientProfileController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Check if the authenticated user can access the patient record
     */
    private function canAccessPatient(Patient $patient): bool
    {
        $user = auth()->user();
        
        // If user has view_patients permission, they can access any patient
        if ($user->can('view_patients')) {
            return true;
        }
        
        // If user is a patient, they can only access their own record
        if ($user->hasRole('patient')) {
            return $patient->user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Get comprehensive patient profile with all related data
     */
    public function getComprehensiveProfile(Request $request, $patientId): JsonResponse
    {
        try {
            // First, try to get the basic patient data
            $patient = Patient::findOrFail($patientId);
            
            // Check if user can access this patient record
            if (!$this->canAccessPatient($patient)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'errors' => null
                ], 403);
            }
            
            // Load basic relationships first
            $patient->load(['branch', 'creator', 'updater']);

            // Load appointments
            $patient->load([
                'appointments' => function($query) {
                    $query->with(['doctor', 'branch'])->latest();
                }
            ]);

            // Load consultations with basic relationships only
            $patient->load([
                'consultations' => function($query) {
                    $query->with([
                        'doctor',
                        'branch',
                        'diagnoses',
                        'vitals',
                        'notes',
                        'interventions',
                        'followUps',
                        'referrals'
                    ])->latest();
                }
            ]);

            // Load other relationships
            $patient->load([
                'prescriptions' => function($query) {
                    $query->with(['doctor', 'consultation'])->latest();
                },
                'labRequests' => function($query) {
                    $query->with(['doctor', 'results', 'technician'])->latest();
                },
                'radiologyRequests' => function ($query) {
                    $query->with(['doctor', 'modality', 'study.report'])->latest();
                },
                'scans' => function($query) {
                    $query->with(['doctor', 'technician'])->latest();
                },
                'bedAssignments' => function($query) {
                    $query->with(['bed.ward', 'assignedBy'])->latest();
                },
                'invoices' => function($query) {
                    $query->with(['payments', 'createdBy'])->latest();
                },
                'allergies',
                'medicalHistory',
                'insurancePolicies' => function($query) {
                    $query->with(['insuranceProvider'])->latest();
                },
                'storeOrders' => function($query) {
                    $query->with(['orderItems.storeItem'])->latest();
                }
            ]);

            // Calculate summary statistics
            $summary = $this->calculatePatientSummary($patient);

            // Get recent activity (last 30 days)
            $recentActivity = $this->getRecentActivity($patient);

            // Get emergency alerts for this patient
            $emergencyAlerts = EmergencyAlert::whereHas('emergencyVisit', function($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })->with(['emergencyVisit', 'createdBy', 'acknowledgedBy'])
              ->latest()
              ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'patient' => $patient,
                    'summary' => $summary,
                    'recent_activity' => $recentActivity,
                    'emergency_alerts' => $emergencyAlerts
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Comprehensive Profile Error: ' . $e->getMessage(), [
                'patient_id' => $patientId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patient profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint to debug the comprehensive profile
     */
    public function testComprehensiveProfile(Request $request, $patientId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);

            if (!auth()->check() || (!$this->canAccessPatient($patient) && !auth()->user()->can('view_patients'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                ], 403);
            }

            $patient->load(['branch', 'creator', 'updater']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'patient_id' => $patient->id,
                    'patient_name' => $patient->full_name,
                    'basic_info' => $patient->only(['id', 'patient_number', 'first_name', 'last_name', 'gender']),
                    'branch' => $patient->branch ? $patient->branch->name : 'No branch',
                    'creator' => $patient->creator ? $patient->creator->name : 'Unknown',
                    'age' => $patient->getAge(),
                    'has_insurance' => $patient->hasActiveInsurance(),
                    'insurance_type' => $patient->getInsuranceType()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate patient summary statistics
     */
    private function calculatePatientSummary($patient): array
    {
        return [
            'total_consultations' => $patient->consultations->count(),
            'total_prescriptions' => $patient->prescriptions->count(),
            'total_lab_tests' => $patient->labRequests->count(),
            'total_scans' => $patient->scans->count(),
            'total_invoices' => $patient->invoices->count(),
            'total_payments' => $patient->invoices->sum(function($invoice) {
                return $invoice->payments->where('status', 'completed')->sum('amount');
            }),
            'outstanding_balance' => $patient->invoices->sum(function($invoice) {
                return $invoice->getRemainingBalance();
            }),
            'active_prescriptions' => $patient->prescriptions->where('status', 'active')->count(),
            'pending_lab_tests' => $patient->labRequests->where('status', 'pending')->count(),
            'current_bed_assignment' => $patient->bedAssignments->where('discharge_date', null)->first(),
            'has_active_insurance' => $patient->hasActiveInsurance(),
            'insurance_type' => $patient->getInsuranceType(),
            'allergies_count' => $patient->allergies->count(),
            'medical_conditions_count' => $patient->medicalHistory->count(),
            'last_visit' => $patient->consultations->max('consultation_date'),
            'next_appointment' => $patient->appointments->where('status', 'scheduled')->min('appointment_date')
        ];
    }

    /**
     * Get recent activity for the patient
     */
    private function getRecentActivity($patient): array
    {
        $activities = collect();

        // Add consultations
        $patient->consultations->each(function($consultation) use ($activities) {
            $activities->push([
                'type' => 'consultation',
                'date' => $consultation->consultation_date,
                'time' => $consultation->consultation_time,
                'description' => 'Consultation with Dr. ' . ($consultation->doctor ? $consultation->doctor->name : 'Unknown'),
                'status' => $consultation->consultation_status,
                'data' => $consultation
            ]);
        });

        // Add prescriptions
        $patient->prescriptions->each(function($prescription) use ($activities) {
            $activities->push([
                'type' => 'prescription',
                'date' => $prescription->prescription_date,
                'time' => $prescription->created_at->format('H:i'),
                'description' => 'Prescription issued by Dr. ' . ($prescription->doctor ? $prescription->doctor->name : 'Unknown'),
                'status' => $prescription->status,
                'data' => $prescription
            ]);
        });

        // Add lab requests
        $patient->labRequests->each(function($labRequest) use ($activities) {
            $activities->push([
                'type' => 'lab_request',
                'date' => $labRequest->request_date,
                'time' => $labRequest->created_at->format('H:i'),
                'description' => 'Lab test requested: ' . ($labRequest->test_type ?? 'Unknown'),
                'status' => $labRequest->status,
                'data' => $labRequest
            ]);
        });

        // Add radiology requests
        $patient->loadMissing(['radiologyRequests.modality']);
        $patient->radiologyRequests->each(function ($radiologyRequest) use ($activities) {
            $activities->push([
                'type' => 'radiology_request',
                'date' => $radiologyRequest->requested_date,
                'time' => $radiologyRequest->created_at->format('H:i'),
                'description' => 'Radiology: ' . ($radiologyRequest->modality?->name ?? 'Imaging'),
                'status' => $radiologyRequest->status,
                'data' => $radiologyRequest,
            ]);
        });

        // Add bed assignments
        $patient->bedAssignments->each(function($bedAssignment) use ($activities) {
            $wardName = 'Unknown Ward';
            $bedNumber = 'Unknown Bed';
            
            if ($bedAssignment->bed && $bedAssignment->bed->ward) {
                $wardName = $bedAssignment->bed->ward->name;
            }
            if ($bedAssignment->bed) {
                $bedNumber = $bedAssignment->bed->bed_number;
            }
            
            $activities->push([
                'type' => 'bed_assignment',
                'date' => $bedAssignment->admission_date,
                'time' => $bedAssignment->created_at->format('H:i'),
                'description' => 'Admitted to ' . $wardName . ' - Bed ' . $bedNumber,
                'status' => $bedAssignment->discharge_date ? 'discharged' : 'active',
                'data' => $bedAssignment
            ]);
        });

        // Add payments
        $patient->invoices->each(function($invoice) use ($activities) {
            $invoice->payments->each(function($payment) use ($activities) {
                $activities->push([
                    'type' => 'payment',
                    'date' => $payment->payment_date,
                    'time' => $payment->created_at->format('H:i'),
                    'description' => 'Payment of GHS ' . number_format($payment->amount, 2) . ' via ' . $payment->payment_method,
                    'status' => $payment->status,
                    'data' => $payment
                ]);
            });
        });

        return $activities->sortByDesc('date')->take(20)->values()->toArray();
    }

    /**
     * Get patient timeline (chronological view)
     */
    public function getPatientTimeline(Request $request, $patientId): JsonResponse
    {
        try {
            $patient = Patient::with(['radiologyRequests.modality', 'radiologyRequests.study.report'])->findOrFail($patientId);
            
            // Check if user can access this patient record
            if (!$this->canAccessPatient($patient)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'errors' => null
                ], 403);
            }
            
            $timeline = $this->getRecentActivity($patient);

            // Group by date
            $groupedTimeline = collect($timeline)->groupBy('date')->map(function ($activities, $date) {
                return [
                    'date' => $date,
                    'activities' => $activities->sortBy('time')->values(),
                ];
            })->sortByDesc('date')->values();

            $radiology = RadiologyRequest::where('patient_id', $patient->id)
                ->with(['modality', 'study.report', 'doctor'])
                ->orderByDesc('requested_date')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'request_number' => $request->request_number,
                        'study_type' => $request->modality?->name,
                        'modality' => $request->modality?->name,
                        'status' => $request->status,
                        'requested_date' => $request->requested_date?->format('Y-m-d'),
                        'report_status' => $request->study?->report?->status,
                        'has_report' => (bool) $request->study?->report,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'timeline' => $groupedTimeline,
                    'radiology' => $radiology,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patient timeline',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search patients with comprehensive data
     */
    public function searchPatients(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $limit = $request->get('limit', 10);

            $patients = Patient::with(['branch', 'insurancePolicies.insuranceProvider'])
                ->search($search)
                ->limit($limit)
                ->get()
                ->map(function($patient) {
                    return [
                        'id' => $patient->id,
                        'patient_number' => $patient->patient_number,
                        'full_name' => $patient->full_name,
                        'age' => $patient->getAge(),
                        'gender' => $patient->gender,
                        'phone' => $patient->phone,
                        'nhis_number' => $patient->nhis_number,
                        'branch' => $patient->branch->name ?? 'N/A',
                        'has_insurance' => $patient->hasActiveInsurance(),
                        'insurance_type' => $patient->getInsuranceType(),
                        'last_visit' => $patient->consultations->max('consultation_date'),
                        'photo' => $patient->photo
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $patients
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ensure patient record exists for a user
     */
    public function ensurePatientRecord(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->user_id;
            $user = User::findOrFail($userId);

            // Check if patient record already exists
            $existingPatient = Patient::where('user_id', $userId)->first();
            if ($existingPatient) {
                return response()->json([
                    'success' => true,
                    'data' => $existingPatient,
                    'message' => 'Patient record already exists'
                ]);
            }

            // Create patient record from user data (mobile app flow: ensure patient record for existing user)
            $patient = Patient::create([
                'user_id' => $userId,
                'first_name' => $user->first_name,
                'other_names' => $user->other_names,
                'last_name' => $user->last_name,
                'gender' => $user->gender ?? 'Unknown',
                'date_of_birth' => $user->date_of_birth,
                'phone' => $user->phone,
                'email' => $user->email,
                'address' => $user->address,
                'branch_id' => $user->branch_id,
                'registration_source' => 'mobile_app', // Tag as registered from mobile app
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Registration fee is NOT applied to patients created via mobile app (ensurePatientRecord).

            return response()->json([
                'success' => true,
                'data' => $patient,
                'message' => 'Patient record created successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to ensure patient record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive financial summary for a patient (Mobile App).
     * 
     * @param Request $request
     * @param int $patientId
     * @return JsonResponse
     */
    public function getFinancialSummary(Request $request, $patientId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);
            
            // Check if user can access this patient record
            if (!$this->canAccessPatient($patient)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'errors' => null
                ], 403);
            }
            
            // Get all invoices
            $invoices = Invoice::where('patient_id', $patientId)
                ->with(['payments', 'branch', 'createdBy'])
                ->orderBy('invoice_date', 'desc')
                ->get();
            
            // Get all payments
            $payments = Payment::where('patient_id', $patientId)
                ->with(['invoice', 'processor', 'branch'])
                ->where('status', 'completed')
                ->orderBy('payment_date', 'desc')
                ->get();
            
            // Calculate totals
            $totalInvoiced = $invoices->sum('total_amount');
            $totalPaid = $invoices->sum('paid_amount');
            $totalOutstanding = $invoices->sum('balance_amount');
            
            // Get invoice status breakdown
            $invoicesByStatus = [
                'unpaid' => $invoices->where('payment_status', 'unpaid')->count(),
                'partial' => $invoices->where('payment_status', 'partial')->count(),
                'paid' => $invoices->where('payment_status', 'paid')->count(),
                'overdue' => $invoices->where('payment_status', 'overdue')->count()
            ];
            
            // Get payment method breakdown
            $paymentsByMethod = $payments->groupBy('payment_method')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount')
                ];
            });
            
            // Get revenue by service type
            $revenueByService = RevenueTransaction::where('patient_id', $patientId)
                ->where('status', 'completed')
                ->selectRaw('service_type, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('service_type')
                ->get()
                ->keyBy('service_type');
            
            // Get outstanding invoices
            $outstandingInvoices = $invoices->filter(function($invoice) {
                return in_array($invoice->payment_status, ['unpaid', 'partial', 'overdue']);
            })->values()->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'due_date' => $invoice->due_date,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'balance_amount' => $invoice->balance_amount,
                    'payment_status' => $invoice->payment_status,
                    'status' => $invoice->status,
                    'items' => $invoice->items
                ];
            });
            
            // Get recent payments
            $recentPayments = $payments->take(10)->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'invoice_number' => $payment->invoice->invoice_number ?? 'N/A',
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                    'status' => $payment->status,
                    'processed_by' => $payment->processor?->name ?? 'System'
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'patient_id' => $patient->id,
                    'patient_name' => $patient->full_name,
                    'patient_number' => $patient->patient_number,
                    'summary' => [
                        'total_invoiced' => round($totalInvoiced, 2),
                        'total_paid' => round($totalPaid, 2),
                        'total_outstanding' => round($totalOutstanding, 2),
                        'collection_rate' => $totalInvoiced > 0 ? round(($totalPaid / $totalInvoiced) * 100, 2) : 0
                    ],
                    'invoices' => [
                        'total_count' => $invoices->count(),
                        'by_status' => $invoicesByStatus,
                        'outstanding_count' => $outstandingInvoices->count(),
                        'outstanding_invoices' => $outstandingInvoices
                    ],
                    'payments' => [
                        'total_count' => $payments->count(),
                        'by_method' => $paymentsByMethod,
                        'recent_payments' => $recentPayments,
                        'last_payment_date' => $payments->first()?->payment_date
                    ],
                    'revenue_breakdown' => $revenueByService->map(function($item) {
                        return [
                            'service_type' => $item->service_type,
                            'total' => round($item->total, 2),
                            'count' => $item->count
                        ];
                    })->values()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Financial summary error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve financial summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
