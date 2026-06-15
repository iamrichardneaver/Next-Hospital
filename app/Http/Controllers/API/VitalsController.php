<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\Vital;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VitalsController extends Controller
{
    use ResolvesUserBranch, WorkflowNavigation;
    /**
     * Get all vitals with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Vital::with(['consultation.patient', 'recordedBy'])
                ->orderBy('recorded_at', 'desc');

            // Filter by patient - query through consultation relationship (only if consultation exists)
            if ($request->has('patient_id') && $request->patient_id) {
                $query->whereHas('consultation', function($q) use ($request) {
                    $q->where('patient_id', $request->patient_id);
                });
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('recorded_at', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('recorded_at', '<=', $request->end_date);
            }

            // Filter by recorded_by
            if ($request->has('recorded_by') && $request->recorded_by) {
                $query->where('recorded_by', $request->recorded_by);
            }

            // Search by patient name - query through consultation relationship (only if consultation exists)
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->whereHas('consultation.patient', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('other_names', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $vitals = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $vitals
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vitals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record new vitals
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'consultation_id' => 'nullable|exists:consultations,id',
                'blood_pressure_systolic' => 'nullable|integer|min:50|max:300',
                'blood_pressure_diastolic' => 'nullable|integer|min:30|max:200',
                'pulse_rate' => 'nullable|integer|min:30|max:300',
                'respiratory_rate' => 'nullable|integer|min:5|max:60',
                'temperature' => 'nullable|numeric|min:30|max:45',
                'oxygen_saturation' => 'nullable|integer|min:50|max:100',
                'height' => 'nullable|numeric|min:50|max:250',
                'weight' => 'nullable|numeric|min:10|max:300',
                'recorded_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculate BMI if height and weight are provided
            $bmi = null;
            if ($request->filled('height') && $request->filled('weight') && $request->height > 0) {
                $heightInMeters = $request->height / 100;
                $bmi = round($request->weight / ($heightInMeters * $heightInMeters), 2);
            }

            // Get patient_id before creating vital (vitals table doesn't store patient_id)
            $patientId = $request->patient_id;
            $consultationId = $request->consultation_id ?? null;

            // Create vital record (remove patient_id and notes as they don't exist in vitals table)
            $vitalData = [
                'consultation_id' => $consultationId,
                'blood_pressure_systolic' => $request->blood_pressure_systolic,
                'blood_pressure_diastolic' => $request->blood_pressure_diastolic,
                'pulse_rate' => $request->pulse_rate,
                'respiratory_rate' => $request->respiratory_rate,
                'temperature' => $request->temperature,
                'oxygen_saturation' => $request->oxygen_saturation,
                'height' => $request->height,
                'weight' => $request->weight,
                'bmi' => $bmi,
                'recorded_by' => Auth::id(),
                'recorded_at' => $request->recorded_at ?? now(),
            ];

            $vital = Vital::create($vitalData);

            // Determine which visit to link vitals to (parity with Web: when visit_id is sent, use that visit so multiple visits per patient each get their own vitals)
            $visitIdFromRequest = $request->input('visit_id');
            $activeVisit = null;
            if ($visitIdFromRequest) {
                $activeVisit = Visit::find($visitIdFromRequest);
                if ($activeVisit && (int) $activeVisit->patient_id !== (int) $patientId) {
                    $activeVisit = null; // Security: visit must belong to this patient
                }
            }
            if (!$activeVisit) {
                $activeVisit = Visit::where('patient_id', $patientId)
                    ->where('status', 'active')
                    ->whereIn('visit_type', ['OPD', 'IPD', 'Emergency'])
                    ->latest()
                    ->first();
            }
                
            $consultation = null;
            
            // If no active visit exists, create one automatically to ensure vitals can be linked
            if (!$activeVisit) {
                try {
                    $patient = Patient::findOrFail($patientId);
                    $userBranch = $this->resolveUserBranchId('record_vitals');
                    
                    // Try to find an available doctor from the branch to assign to the visit
                    $availableDoctor = User::role('doctor')
                        ->whereHas('staffProfile', function($q) use ($userBranch) {
                            $q->where('branch_id', $userBranch);
                        })
                        ->where('is_active', true)
                        ->first();
                    
                    // Create a visit automatically for vitals recording
                    $activeVisit = Visit::create([
                        'patient_id' => $patientId,
                        'branch_id' => $userBranch,
                        'visit_type' => 'OPD', // Default to OPD for vitals recording
                        'status' => 'active',
                        'assigned_doctor_id' => $availableDoctor ? $availableDoctor->id : null,
                        'check_in_time' => now(),
                        'created_by' => auth()->id(),
                    ]);
                    
                    \Log::info('Auto-created visit for vitals recording (API)', [
                        'visit_id' => $activeVisit->id,
                        'patient_id' => $patientId,
                        'assigned_doctor_id' => $activeVisit->assigned_doctor_id,
                        'created_by' => auth()->id(),
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to auto-create visit for vitals (API)', [
                        'patient_id' => $patientId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            if ($activeVisit) {
                // Use ConsultationService to get or create consultation
                $consultationService = app(\App\Services\ConsultationService::class);
                
                // Prepare vitals data for consultation
                $vitalsData = [];
                $vitalsFields = [
                    'blood_pressure_systolic', 'blood_pressure_diastolic', 'pulse_rate',
                    'temperature', 'respiratory_rate', 'oxygen_saturation', 'height', 'weight', 'bmi'
                ];
                
                foreach ($vitalsFields as $field) {
                    if (isset($vitalData[$field]) && $vitalData[$field] !== null) {
                        $vitalsData[$field] = $vitalData[$field];
                    }
                }
                
                // Get or create consultation for vitals
                $consultation = $consultationService->getOrCreateConsultationForVitals(
                    $activeVisit,
                    $vitalsData,
                    $consultationId
                );
                
                // Link the vital record to the consultation (if not already linked)
                if ($consultation && !$vital->consultation_id) {
                    $vital->update(['consultation_id' => $consultation->id]);
                }
            }

            // Load relationships - patient through consultation if consultation exists
            $vital->load(['consultation.patient', 'recordedBy']);
            
            // Also try to load patient directly (for backwards compatibility)
            if ($vital->consultation_id) {
                try {
                    $vital->load('patient');
                } catch (\Exception $e) {
                    // If patient relationship fails, continue without it
                }
            }

            // Use the same $activeVisit from above (line 131) for workflow step
            // No need to query again - reuse the existing $activeVisit variable

            // Complete workflow step if visit has workflow
            if ($activeVisit && $activeVisit->workflowInstance) {
                $this->completeWorkflowStep($activeVisit, 'vitals_recording', $vital->toArray());
            }

            // Get workflow next step suggestion
            $response = [
                'success' => true,
                'message' => 'Vitals recorded successfully',
                'data' => $vital
            ];

            if ($activeVisit && $activeVisit->workflowInstance) {
                $workflowResponse = $this->getNextStepResponse($activeVisit, 'Vitals recorded successfully');
                $response['workflow'] = $workflowResponse->getData(true)['workflow'] ?? null;
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record vitals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single vital record
     */
    public function show($id): JsonResponse
    {
        try {
            // Load relationships - patient through consultation if consultation exists
            $vital = Vital::with(['consultation.patient', 'recordedBy'])
                ->findOrFail($id);
            
            // Also try to load patient directly (for backwards compatibility)
            if ($vital->consultation_id) {
                try {
                    $vital->load('patient');
                } catch (\Exception $e) {
                    // If patient relationship fails, continue without it
                }
            }

            return response()->json([
                'success' => true,
                'data' => $vital
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vital record not found'
            ], 404);
        }
    }

    /**
     * Update vital record
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $vital = Vital::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'blood_pressure_systolic' => 'nullable|integer|min:50|max:300',
                'blood_pressure_diastolic' => 'nullable|integer|min:30|max:200',
                'pulse_rate' => 'nullable|integer|min:30|max:300',
                'respiratory_rate' => 'nullable|integer|min:5|max:60',
                'temperature' => 'nullable|numeric|min:30|max:45',
                'oxygen_saturation' => 'nullable|integer|min:50|max:100',
                'height' => 'nullable|numeric|min:50|max:250',
                'weight' => 'nullable|numeric|min:10|max:300',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Recalculate BMI if height or weight changed
            if ($request->has('height') || $request->has('weight')) {
                $height = $request->filled('height') ? $request->height : $vital->height;
                $weight = $request->filled('weight') ? $request->weight : $vital->weight;
                
                if ($height > 0 && $weight > 0) {
                    $heightInMeters = $height / 100;
                    $bmi = round($weight / ($heightInMeters * $heightInMeters), 2);
                    $request->merge(['bmi' => $bmi]);
                }
            }

            $vital->update($request->only([
                'blood_pressure_systolic',
                'blood_pressure_diastolic',
                'pulse_rate',
                'respiratory_rate',
                'temperature',
                'oxygen_saturation',
                'height',
                'weight',
                'bmi'
            ]));

            // Reload relationships - patient through consultation if consultation exists
            $vital->load(['consultation.patient', 'recordedBy']);
            
            // Also try to load patient directly (for backwards compatibility)
            if ($vital->consultation_id) {
                try {
                    $vital->load('patient');
                } catch (\Exception $e) {
                    // If patient relationship fails, continue without it
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Vitals updated successfully',
                'data' => $vital
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vitals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete vital record
     */
    public function destroy($id): JsonResponse
    {
        try {
            $vital = Vital::findOrFail($id);
            $vital->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vital record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vital record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient's vital history
     */
    public function getPatientVitals(Request $request, $patientId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);

            $query = Vital::with(['recordedBy', 'consultation.patient'])
                ->whereHas('consultation', function($q) use ($patientId) {
                    $q->where('patient_id', $patientId);
                })
                ->orderBy('recorded_at', 'desc');

            // Optional date range filter
            if ($request->has('start_date')) {
                $query->whereDate('recorded_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('recorded_at', '<=', $request->end_date);
            }

            $limit = $request->get('limit', 10);
            $vitals = $query->limit($limit)->get();

            // Get vital trends
            $trends = [
                'temperature' => $this->calculateTrend($patientId, 'temperature'),
                'blood_pressure' => $this->calculateBPTrend($patientId),
                'pulse_rate' => $this->calculateTrend($patientId, 'pulse_rate'),
                'weight' => $this->calculateTrend($patientId, 'weight'),
                'bmi' => $this->calculateTrend($patientId, 'bmi'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'patient' => $patient,
                    'vitals' => $vitals,
                    'trends' => $trends,
                    'latest' => $vitals->first()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patient vitals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated patient's own vitals (for mobile app)
     */
    public function getMyVitals(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get patient record for the authenticated user
            $patient = $user->patient;
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found for this user'
                ], 404);
            }
            
            $query = Vital::with(['recordedBy', 'consultation.patient'])
                ->whereHas('consultation', function($q) use ($patient) {
                    $q->where('patient_id', $patient->id);
                })
                ->orderBy('recorded_at', 'desc');

            // Optional date range filter
            if ($request->has('start_date')) {
                $query->whereDate('recorded_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('recorded_at', '<=', $request->end_date);
            }

            $limit = $request->get('limit', 50);
            $vitals = $query->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $vitals,
                'latest' => $vitals->first()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vitals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vitals statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', now()->subDays(30));
            $endDate = $request->get('end_date', now());

            $stats = [
                'total_records' => Vital::whereBetween('recorded_at', [$startDate, $endDate])->count(),
                'unique_patients' => Vital::whereBetween('recorded_at', [$startDate, $endDate])
                    ->whereHas('consultation')
                    ->with('consultation:id,patient_id')
                    ->get()
                    ->pluck('consultation.patient_id')
                    ->unique()
                    ->count(),
                'by_recorder' => Vital::whereBetween('recorded_at', [$startDate, $endDate])
                    ->select('recorded_by', DB::raw('count(*) as count'))
                    ->with('recordedBy:id,name,first_name,last_name')
                    ->groupBy('recorded_by')
                    ->get(),
                'daily_count' => Vital::whereBetween('recorded_at', [$startDate, $endDate])
                    ->select(DB::raw('DATE(recorded_at) as date'), DB::raw('count(*) as count'))
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->limit(30)
                    ->get(),
                'abnormal_vitals' => [
                    'high_temperature' => Vital::whereBetween('recorded_at', [$startDate, $endDate])
                        ->where('temperature', '>', 38)
                        ->count(),
                    'high_bp' => Vital::whereBetween('recorded_at', [$startDate, $endDate])
                        ->where('blood_pressure_systolic', '>', 140)
                        ->count(),
                    'low_oxygen' => Vital::whereBetween('recorded_at', [$startDate, $endDate])
                        ->where('oxygen_saturation', '<', 95)
                        ->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate trend for a vital sign
     */
    private function calculateTrend($patientId, $field)
    {
        $vitals = Vital::whereHas('consultation', function($q) use ($patientId) {
                $q->where('patient_id', $patientId);
            })
            ->whereNotNull($field)
            ->orderBy('recorded_at', 'desc')
            ->limit(5)
            ->get();

        if ($vitals->count() < 2) {
            return 'stable';
        }

        $latest = $vitals->first()->$field;
        $previous = $vitals->skip(1)->first()->$field;

        if ($latest > $previous * 1.05) {
            return 'increasing';
        } elseif ($latest < $previous * 0.95) {
            return 'decreasing';
        }

        return 'stable';
    }

    /**
     * Calculate blood pressure trend
     */
    private function calculateBPTrend($patientId)
    {
        $vitals = Vital::whereHas('consultation', function($q) use ($patientId) {
                $q->where('patient_id', $patientId);
            })
            ->whereNotNull('blood_pressure_systolic')
            ->whereNotNull('blood_pressure_diastolic')
            ->orderBy('recorded_at', 'desc')
            ->limit(5)
            ->get();

        if ($vitals->count() < 2) {
            return 'stable';
        }

        $latest = $vitals->first()->blood_pressure_systolic + $vitals->first()->blood_pressure_diastolic;
        $previous = $vitals->skip(1)->first()->blood_pressure_systolic + $vitals->skip(1)->first()->blood_pressure_diastolic;

        if ($latest > $previous * 1.05) {
            return 'increasing';
        } elseif ($latest < $previous * 0.95) {
            return 'decreasing';
        }

        return 'stable';
    }
}
