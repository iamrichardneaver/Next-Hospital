<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\LabRequest;
use App\Models\Consultation;
use App\Models\Visit;
use App\Models\Queue;
use App\Models\Vital;
use App\Models\Diagnosis;
use App\Models\ConsultationIntervention;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConsultationController extends Controller
{
    use ResolvesUserBranch, WorkflowNavigation;
    /**
     * Get consultations with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id');
            if (!$branchId) {
                // Get user's first branch if no branch_id provided
                $userBranch = auth()->user()->branches()->first();
                $branchId = $userBranch ? $userBranch->id : 1;
            }
            
            $query = Consultation::with(['patient', 'doctor', 'branch'])
                ->where('branch_id', $branchId);

            if (auth()->user()->hasRole('doctor')) {
                $query->where('doctor_id', auth()->id());
            }

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('chief_complaint', 'like', "%{$search}%")
                      ->orWhere('consultation_number', 'like', "%{$search}%")
                      ->orWhereHas('patient', function ($patientQuery) use ($search) {
                          $patientQuery->where('first_name', 'like', "%{$search}%")
                                     ->orWhere('last_name', 'like', "%{$search}%")
                                     ->orWhere('patient_number', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status')) {
                $query->where('consultation_status', $request->get('status'));
            }

            if ($request->filled('doctor_id')) {
                $query->where('doctor_id', $request->get('doctor_id'));
            }

            if ($request->filled('consultation_type')) {
                $query->where('consultation_type', $request->get('consultation_type'));
            }

            if ($request->filled('date_from')) {
                $query->where('consultation_date', '>=', $request->get('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->where('consultation_date', '<=', $request->get('date_to'));
            }

            // Order by created_at desc
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $consultations = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $consultations->items(),
                'meta' => [
                    'current_page' => $consultations->currentPage(),
                    'last_page' => $consultations->lastPage(),
                    'per_page' => $consultations->perPage(),
                    'total' => $consultations->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching consultations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get consultation statistics.
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id');
            if (!$branchId) {
                // Get user's first branch if no branch_id provided
                $userBranch = auth()->user()->branches()->first();
                $branchId = $userBranch ? $userBranch->id : 1;
            }
            $dateFrom = $request->get('date_from', now()->startOfMonth());
            $dateTo = $request->get('date_to', now()->endOfMonth());

            $query = Consultation::where('branch_id', $branchId)
                ->whereBetween('consultation_date', [$dateFrom, $dateTo]);

            $stats = [
                'total_consultations' => $query->count(),
                'ongoing_consultations' => $query->clone()->where('consultation_status', 'ongoing')->count(),
                'completed_consultations' => $query->clone()->where('consultation_status', 'completed')->count(),
                'cancelled_consultations' => $query->clone()->where('consultation_status', 'cancelled')->count(),
                'in_person_consultations' => $query->clone()->where('consultation_type', 'in-person')->count(),
                'teleconsultations' => $query->clone()->where('consultation_type', 'teleconsultation')->count(),
                'today_consultations' => Consultation::where('branch_id', $branchId)
                    ->where('consultation_date', now()->toDateString())
                    ->count(),
                'this_week_consultations' => Consultation::where('branch_id', $branchId)
                    ->whereBetween('consultation_date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
                'this_month_consultations' => Consultation::where('branch_id', $branchId)
                    ->whereBetween('consultation_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
            ];

            // Top doctors by consultation count
            $topDoctors = Consultation::where('branch_id', $branchId)
                ->whereBetween('consultation_date', [$dateFrom, $dateTo])
                ->with('doctor')
                ->selectRaw('doctor_id, COUNT(*) as consultation_count')
                ->groupBy('doctor_id')
                ->orderBy('consultation_count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'doctor_id' => $item->doctor_id,
                        'doctor_name' => $item->doctor ? $item->doctor->first_name . ' ' . $item->doctor->last_name : 'Unknown',
                        'consultation_count' => $item->consultation_count
                    ];
                });

            $stats['top_doctors'] = $topDoctors;

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create consultation from OPD visit.
     */
    public function createFromOPDVisit(Request $request, $visitId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:users,id',
            'chief_complaint' => 'required|string|max:1000',
            'history_of_present_illness' => 'nullable|string|max:2000',
            'physical_examination' => 'nullable|string|max:2000',
            'vitals' => 'nullable|array',
            'diagnoses' => 'nullable|array',
            'treatment_plan' => 'nullable|string|max:2000',
            'icd_10_code' => 'nullable|string|max:20',
            'severity' => 'nullable|in:mild,moderate,severe',
            'urgency' => 'nullable|in:routine,urgent,critical',
            'requires_referral' => 'nullable|boolean',
            'referral_specialty' => 'nullable|string|max:100',
            'referral_reason' => 'nullable|string|max:500',
            'admit_patient' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $visit = Visit::findOrFail($visitId);
            
            if ($visit->visit_type !== 'OPD') {
                return response()->json([
                    'success' => false,
                    'message' => 'Visit is not an OPD visit'
                ], 400);
            }

            if ($visit->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Visit is not active'
                ], 400);
            }

            DB::beginTransaction();

            // Update queue status to serving
            $visit->queues()->where('queue_type', 'OPD')->update([
                'status' => 'serving',
                'serving_at' => now(),
                'served_by' => $request->doctor_id
            ]);

            // Create consultation
            $consultation = Consultation::create([
                'patient_id' => $visit->patient_id,
                'doctor_id' => $request->doctor_id,
                'branch_id' => $visit->branch_id,
                'consultation_date' => now()->toDateString(),
                'consultation_time' => now()->format('H:i'),
                'consultation_type' => 'in-person',
                'chief_complaint' => $request->chief_complaint,
                'history_of_present_illness' => $request->history_of_present_illness,
                'physical_examination' => $request->physical_examination,
                'vitals' => $request->vitals,
                'diagnoses' => $request->diagnoses,
                'treatment_plan' => $request->treatment_plan,
                'icd_10_code' => $request->icd_10_code,
                'severity' => $request->severity,
                'urgency' => $request->urgency ?? 'routine',
                'requires_referral' => $request->requires_referral ?? false,
                'referral_specialty' => $request->referral_specialty,
                'referral_reason' => $request->referral_reason,
                'consultation_status' => 'completed',
                'started_at' => now(),
                'completed_at' => now(),
                'created_by' => auth()->id()
            ]);

            // Update visit with consultation
            $visit->update([
                'assigned_doctor_id' => $request->doctor_id,
                'updated_by' => auth()->id()
            ]);

            // If patient needs admission, mark for IPD
            if ($request->admit_patient) {
                $visit->update(['visit_type' => 'IPD']);
            }

            DB::commit();

            // Initialize workflow for consultation if not already initialized
            if (!$consultation->workflowInstance) {
                $this->initializeWorkflowForEntity($consultation, 'OPD Consultation');
            }

            // Complete workflow step
            if ($consultation->workflowInstance) {
                $this->completeWorkflowStep($consultation, 'consultation', [
                    'consultation_id' => $consultation->id,
                    'diagnoses' => $request->diagnoses ?? [],
                ]);
            }

            // Get workflow next step suggestion
            $response = [
                'success' => true,
                'data' => $consultation->load(['patient', 'doctor', 'branch']),
                'message' => 'Consultation created successfully'
            ];

            if ($consultation->workflowInstance) {
                $workflowResponse = $this->getNextStepResponse($consultation, 'Consultation created successfully');
                $response['workflow'] = $workflowResponse->getData(true)['workflow'] ?? null;
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating consultation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete OPD consultation and update queue.
     */
    public function completeOPDConsultation(Request $request, $queueId): JsonResponse
    {
        try {
            $queue = Queue::findOrFail($queueId);
            
            if ($queue->queue_type !== 'OPD') {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue is not an OPD queue'
                ], 400);
            }

            if ($queue->status !== 'serving') {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue is not being served'
                ], 400);
            }

            DB::beginTransaction();

            // Update queue status to completed
            $queue->update([
                'status' => 'completed',
                'completed_at' => now(),
                'served_by' => auth()->id()
            ]);

            // Check if all queues for this visit are completed
            $visit = $queue->visit;
            $activeQueues = $visit->queues()->whereIn('status', ['waiting', 'called', 'serving'])->count();
            
            if ($activeQueues === 0) {
                $visit->update([
                    'status' => 'completed',
                    'check_out_time' => now(),
                    'updated_by' => auth()->id()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'OPD consultation completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error completing consultation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get consultation details.
     */
    public function show($id): JsonResponse
    {
        try {
            $consultation = Consultation::with([
                'patient',
                'doctor',
                'branch',
                'visit',
                'labRequests' => function ($query) {
                    $query->with([
                        'results' => fn ($q) => $q->orderBy('parameter_name'),
                        'testType',
                        'template',
                    ])->latest('updated_at');
                },
            ])->findOrFail($id);

            $this->assertConsultationAccess($consultation);

            $this->attachConsultationLabRequests($consultation);

            // Get workflow instance and progress if consultation has a visit
            $workflow = null;
            $workflowProgress = null;
            
            if ($consultation->visit) {
                try {
                    $this->initializeWorkflowServices();
                    $workflowInstance = $this->getWorkflowInstance($consultation->visit);
                    if ($workflowInstance) {
                        $navigationService = app(\App\Services\WorkflowNavigationService::class);
                        
                        // Get next step suggestion
                        $suggestion = $navigationService->getNextStepSuggestion($workflowInstance->id, auth()->id());
                        if ($suggestion) {
                            $workflow = $navigationService->formatSuggestionForResponse($suggestion);
                        }
                        
                        // Get workflow progress
                        $workflowProgress = $this->workflowService->getWorkflowProgress($workflowInstance->id);
                    }
                } catch (\Throwable $e) {
                    // Silently fail if workflow doesn't exist
                    \Log::debug('Workflow data not available for consultation', ['consultation_id' => $id, 'error' => $e->getMessage()]);
                }
            }

            $response = [
                'success' => true,
                'data' => $consultation,
                'message' => 'Consultation retrieved successfully'
            ];

            if ($workflow) {
                $response['workflow'] = $workflow;
            }

            if ($workflowProgress) {
                $response['workflow_progress'] = $workflowProgress;
            }

            return response()->json($response);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Access denied',
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store a new consultation.
     */
    public function store(Request $request): JsonResponse
    {
        if (auth()->user()->hasRole('doctor')) {
            $request->merge(['doctor_id' => auth()->id()]);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'visit_id' => 'nullable|exists:visits,id',
            'consultation_date' => 'nullable|date',
            'consultation_time' => 'nullable|date_format:H:i',
            'consultation_type' => 'nullable|in:in-person,teleconsultation',
            'chief_complaint' => 'required|string|max:1000',
            'urgency' => 'nullable|in:routine,urgent,critical',
            'history_of_present_illness' => 'nullable|string|max:2000',
            'on_direct_questioning' => 'nullable|string',
            'past_medical_history' => 'nullable|string',
            'family_history' => 'nullable|string',
            'social_history' => 'nullable|string',
            'drug_history' => 'nullable|string',
            'allergy_history' => 'nullable|string',
            'past_medical_history_others' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'drug_allergies' => 'nullable|string',
            'past_drug_usage' => 'nullable|string',
            'social_history_details' => 'nullable|string',
            'general_examination' => 'nullable|string',
            'cardiovascular_examination' => 'nullable|string',
            'respiratory_examination' => 'nullable|string',
            'abdominal_examination' => 'nullable|string',
            'neurological_examination' => 'nullable|string',
            'physical_examination' => 'nullable|string|max:2000',
            'blood_pressure_systolic' => 'nullable|numeric|min:50|max:300',
            'blood_pressure_diastolic' => 'nullable|numeric|min:30|max:200',
            'pulse_rate' => 'nullable|integer|min:30|max:300',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'respiratory_rate' => 'nullable|integer|min:5|max:60',
            'oxygen_saturation' => 'nullable|integer|min:50|max:100',
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:10|max:300',
            'bmi' => 'nullable|numeric|min:10|max:100',
            'vitals' => 'nullable|array',
            'diagnoses' => 'nullable|array',
            'doctors_impression' => 'nullable|string',
            'treatment_plan' => 'nullable|string|max:2000',
            'icd_10_code' => 'nullable|string|max:20',
            'clinical_notes' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'severity' => 'nullable|in:mild,moderate,severe',
            'requires_referral' => 'nullable|boolean',
            'referral_specialty' => 'nullable|string|max:100',
            'referral_reason' => 'nullable|string|max:500',
            'is_draft' => 'nullable|boolean',
            'next_stage' => 'nullable|string',
            // Prescription orders validation
            'prescription_orders' => 'nullable|array',
            'prescription_orders.*.drug_id' => 'required_with:prescription_orders|exists:drugs,id',
            'prescription_orders.*.quantity' => 'required_with:prescription_orders|integer|min:1',
            'prescription_orders.*.dosage_instructions' => 'required_with:prescription_orders|string|max:500',
            'prescription_orders.*.duration' => 'nullable|string|max:100',
            // Lab orders validation
            'lab_orders' => 'nullable|array',
            'lab_orders.*.category_id' => 'nullable|exists:lab_test_categories,id',
            'lab_orders.*.test_type_id' => 'required_with:lab_orders|exists:lab_test_types,id',
            'lab_orders.*.priority' => 'nullable|in:routine,urgent,stat',
            'lab_orders.*.specimen_type' => 'nullable|string|max:100',
            // Radiology orders validation
            'radiology_orders' => 'nullable|array',
            'radiology_orders.*.modality_id' => 'required_with:radiology_orders|exists:imaging_modalities,id',
            'radiology_orders.*.department_id' => 'required_with:radiology_orders|exists:radiology_departments,id',
            'radiology_orders.*.priority' => 'nullable|in:routine,urgent,stat',
            'radiology_orders.*.clinical_history' => 'nullable|string|max:1000',
            'radiology_orders.*.clinical_question' => 'nullable|string|max:500',
            'radiology_orders.*.indication' => 'nullable|string|max:500',
            'radiology_orders.*.scheduled_date' => 'nullable|date',
            'radiology_orders.*.scheduled_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate BMI if height and weight are provided
            $bmi = null;
            if ($request->has('height') && $request->has('weight') && $request->height && $request->weight) {
                $heightInMeters = $request->height / 100;
                $bmi = round($request->weight / ($heightInMeters * $heightInMeters), 2);
            }

            $consultationData = [
                'patient_id' => $request->patient_id,
                'doctor_id' => $request->doctor_id,
                'branch_id' => $request->branch_id,
                'visit_id' => $request->visit_id,
                'consultation_date' => $request->consultation_date ?? now()->toDateString(),
                'consultation_time' => $request->consultation_time ?? now()->format('H:i'),
                'consultation_type' => $request->consultation_type ?? 'in-person',
                'chief_complaint' => $request->chief_complaint,
                'urgency' => $request->urgency ?? 'routine',
                'history_of_present_illness' => $request->history_of_present_illness,
                'on_direct_questioning' => $request->on_direct_questioning,
                'past_medical_history' => $request->past_medical_history,
                'family_history' => $request->family_history,
                'social_history' => $request->social_history,
                'drug_history' => $request->drug_history,
                'allergy_history' => $request->allergy_history,
                'past_medical_history_others' => $request->past_medical_history_others,
                'current_medications' => $request->current_medications,
                'drug_allergies' => $request->drug_allergies,
                'past_drug_usage' => $request->past_drug_usage,
                'social_history_details' => $request->social_history_details,
                'general_examination' => $request->general_examination,
                'cardiovascular_examination' => $request->cardiovascular_examination,
                'respiratory_examination' => $request->respiratory_examination,
                'abdominal_examination' => $request->abdominal_examination,
                'neurological_examination' => $request->neurological_examination,
                'physical_examination' => $request->physical_examination,
                'blood_pressure_systolic' => $request->blood_pressure_systolic,
                'blood_pressure_diastolic' => $request->blood_pressure_diastolic,
                'pulse_rate' => $request->pulse_rate,
                'temperature' => $request->temperature,
                'respiratory_rate' => $request->respiratory_rate,
                'oxygen_saturation' => $request->oxygen_saturation,
                'height' => $request->height,
                'weight' => $request->weight,
                'bmi' => $bmi ?? $request->bmi,
                'vitals' => $request->vitals,
                'diagnoses' => $request->diagnoses,
                'doctors_impression' => $request->doctors_impression,
                'treatment_plan' => $request->treatment_plan,
                'icd_10_code' => $request->icd_10_code,
                'clinical_notes' => $request->clinical_notes,
                'follow_up_instructions' => $request->follow_up_instructions,
                'severity' => $request->severity,
                'requires_referral' => $request->requires_referral ?? false,
                'referral_specialty' => $request->referral_specialty,
                'referral_reason' => $request->referral_reason,
                'consultation_status' => $request->is_draft ? 'ongoing' : 'completed',
                'is_draft' => $request->is_draft ?? false,
                'started_at' => now(),
                'created_by' => auth()->id()
            ];

            if (!$request->is_draft) {
                $consultationData['completed_at'] = now();
            }

            $consultation = Consultation::create($consultationData);

            $isDraft = $request->boolean('is_draft');

            if ($request->has('prescription_orders') && is_array($request->prescription_orders) && count($request->prescription_orders) > 0) {
                if ($isDraft) {
                    $this->updateOrCreatePrescriptionOrders($consultation, $request->prescription_orders);
                } else {
                    $this->createPrescriptionOrders($consultation, $request->prescription_orders);
                }
            }

            if ($request->has('lab_orders') && is_array($request->lab_orders) && count($request->lab_orders) > 0) {
                if ($isDraft) {
                    $this->updateOrCreateLabOrders($consultation, $request->lab_orders);
                } else {
                    $this->createLabOrders($consultation, $request->lab_orders);
                }
            }

            if ($request->has('radiology_orders') && is_array($request->radiology_orders) && count($request->radiology_orders) > 0) {
                if ($isDraft) {
                    $this->updateOrCreateRadiologyOrders($consultation, $request->radiology_orders);
                } else {
                    $this->createRadiologyOrders($consultation, $request->radiology_orders);
                }
            }

            DB::commit();

            // Initialize workflow for consultation if not already initialized
            if (!$consultation->workflowInstance) {
                $this->initializeWorkflowForEntity($consultation, 'OPD Consultation');
            }

            // Get workflow next step suggestion
            $response = [
                'success' => true,
                'data' => $consultation->load(['patient', 'doctor', 'branch']),
                'message' => 'Consultation created successfully'
            ];

            if ($consultation->workflowInstance) {
                $workflowResponse = $this->getNextStepResponse($consultation, 'Consultation created successfully');
                $response['workflow'] = $workflowResponse->getData(true)['workflow'] ?? null;
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating consultation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get consultations by patient.
     */
    public function getByPatient(Request $request, $patientId): JsonResponse
    {
        try {
            $patientId = (int) $patientId;
            $portalPatient = $this->portalPatient();
            if ($portalPatient && (int) $portalPatient->id !== $patientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only view your own consultations.',
                ], 403);
            }

            $consultations = Consultation::with(['doctor', 'branch'])
                ->where('patient_id', $patientId)
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $consultations->items(),
                'meta' => [
                    'current_page' => $consultations->currentPage(),
                    'last_page' => $consultations->lastPage(),
                    'per_page' => $consultations->perPage(),
                    'total' => $consultations->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching consultations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified consultation.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($id);

            $this->assertConsultationAccess($consultation);

            if (auth()->user()->hasRole('doctor')) {
                $request->merge(['doctor_id' => auth()->id()]);
            }

            $wasCompleted = $consultation->consultation_status === 'completed';
            $isCompleting = $request->consultation_status === 'completed';

            $validator = Validator::make($request->all(), [
                'chief_complaint' => 'nullable|string|max:1000',
                'consultation_status' => 'nullable|in:ongoing,completed,cancelled',
                'history_of_present_illness' => 'nullable|string|max:2000',
                'on_direct_questioning' => 'nullable|string',
                'past_medical_history' => 'nullable|string',
                'family_history' => 'nullable|string',
                'social_history' => 'nullable|string',
                'drug_history' => 'nullable|string',
                'allergy_history' => 'nullable|string',
                'past_medical_history_others' => 'nullable|string',
                'current_medications' => 'nullable|string',
                'drug_allergies' => 'nullable|string',
                'past_drug_usage' => 'nullable|string',
                'social_history_details' => 'nullable|string',
                'general_examination' => 'nullable|string',
                'cardiovascular_examination' => 'nullable|string',
                'respiratory_examination' => 'nullable|string',
                'abdominal_examination' => 'nullable|string',
                'neurological_examination' => 'nullable|string',
                'physical_examination' => 'nullable|string|max:2000',
                'blood_pressure_systolic' => 'nullable|numeric|min:50|max:300',
                'blood_pressure_diastolic' => 'nullable|numeric|min:30|max:200',
                'pulse_rate' => 'nullable|integer|min:30|max:300',
                'temperature' => 'nullable|numeric|min:30|max:45',
                'respiratory_rate' => 'nullable|integer|min:5|max:60',
                'oxygen_saturation' => 'nullable|integer|min:50|max:100',
                'height' => 'nullable|numeric|min:50|max:250',
                'weight' => 'nullable|numeric|min:10|max:300',
                'bmi' => 'nullable|numeric|min:10|max:100',
                'vitals' => 'nullable|array',
                'diagnoses' => 'nullable|array',
                'doctors_impression' => 'nullable|string',
                'treatment_plan' => 'nullable|string|max:2000',
                'icd_10_code' => 'nullable|string|max:20',
                'clinical_notes' => 'nullable|string',
                'follow_up_instructions' => 'nullable|string',
                'severity' => 'nullable|in:mild,moderate,severe',
                'urgency' => 'nullable|in:routine,urgent,critical',
                'consultation_type' => 'nullable|in:in-person,teleconsultation',
                'consultation_date' => 'nullable|date',
                'consultation_time' => 'nullable|date_format:H:i',
                'is_draft' => 'nullable|boolean',
                'next_stage' => 'nullable|string',
                'completion_notes' => 'nullable|string|max:2000',
                'completion_type' => 'nullable|string|max:50',
                'amendment_notes' => 'nullable|string|max:2000',
                // Prescription orders validation
                'prescription_orders' => 'nullable|array',
                'prescription_orders.*.drug_id' => 'required_with:prescription_orders|exists:drugs,id',
                'prescription_orders.*.quantity' => 'required_with:prescription_orders|integer|min:1',
                'prescription_orders.*.dosage_instructions' => 'required_with:prescription_orders|string|max:500',
                'prescription_orders.*.duration' => 'nullable|string|max:100',
                // Lab orders validation
                'lab_orders' => 'nullable|array',
                'lab_orders.*.category_id' => 'nullable|exists:lab_test_categories,id',
                'lab_orders.*.test_type_id' => 'required_with:lab_orders|exists:lab_test_types,id',
                'lab_orders.*.priority' => 'nullable|in:routine,urgent,stat',
                'lab_orders.*.specimen_type' => 'nullable|string|max:100',
                'lab_orders.*.lab_request_id' => 'nullable|exists:lab_requests,id',
                // Radiology orders validation
                'radiology_orders' => 'nullable|array',
                'radiology_orders.*.radiology_request_id' => 'nullable|exists:radiology_requests,id',
                'radiology_orders.*.modality_id' => 'required_with:radiology_orders|exists:imaging_modalities,id',
                'radiology_orders.*.department_id' => 'required_with:radiology_orders|exists:radiology_departments,id',
                'radiology_orders.*.priority' => 'nullable|in:routine,urgent,stat',
                'radiology_orders.*.clinical_history' => 'nullable|string|max:1000',
                'radiology_orders.*.clinical_question' => 'nullable|string|max:500',
                'radiology_orders.*.indication' => 'nullable|string|max:500',
                'radiology_orders.*.scheduled_date' => 'nullable|date',
                'radiology_orders.*.scheduled_time' => 'nullable|date_format:H:i',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Calculate BMI if height and weight are provided
            $bmi = null;
            if ($request->has('height') && $request->has('weight') && $request->height && $request->weight) {
                $heightInMeters = $request->height / 100;
                $bmi = round($request->weight / ($heightInMeters * $heightInMeters), 2);
            }

            $updateData = $request->only([
                'chief_complaint',
                'history_of_present_illness',
                'on_direct_questioning',
                'past_medical_history',
                'family_history',
                'social_history',
                'drug_history',
                'allergy_history',
                'past_medical_history_others',
                'current_medications',
                'drug_allergies',
                'past_drug_usage',
                'social_history_details',
                'general_examination',
                'cardiovascular_examination',
                'respiratory_examination',
                'abdominal_examination',
                'neurological_examination',
                'physical_examination',
                'blood_pressure_systolic',
                'blood_pressure_diastolic',
                'pulse_rate',
                'temperature',
                'respiratory_rate',
                'oxygen_saturation',
                'height',
                'weight',
                'vitals',
                'diagnoses',
                'doctors_impression',
                'treatment_plan',
                'icd_10_code',
                'clinical_notes',
                'follow_up_instructions',
                'severity',
                'urgency',
                'consultation_status',
                'consultation_type',
                'consultation_date',
                'consultation_time',
                'is_draft',
                'next_stage',
            ]);

            if ($bmi) {
                $updateData['bmi'] = $bmi;
            }

            if ($isCompleting) {
                $updateData['completed_at'] = now();

                if ($request->has('completion_notes') && $request->completion_notes) {
                    $updateData['completion_notes'] = $request->completion_notes;
                }

                if ($request->has('completion_type')) {
                    $updateData['completion_type'] = $request->completion_type;
                }
            } elseif ($request->has('is_draft') && !$wasCompleted) {
                $updateData['is_draft'] = $request->boolean('is_draft');
            }

            if ($wasCompleted && !$isCompleting) {
                $updateData['consultation_status'] = 'completed';
                $updateData['is_draft'] = false;
                $updateData['amended_at'] = now();
                $updateData['amended_by'] = auth()->id();
                if ($request->filled('amendment_notes')) {
                    $updateData['amendment_notes'] = $request->amendment_notes;
                }
            }

            if ($consultation->is_draft && $isCompleting) {
                $updateData['doctor_id'] = auth()->id();
            }

            $updateData['updated_by'] = auth()->id();
            $consultation->update($updateData);
            $consultation->refresh();

            $isDraft = $wasCompleted ? false : ($request->has('is_draft') ? $request->boolean('is_draft') : ($consultation->is_draft ?? false));
            $hasRadiologyOrders = false;

            if ($request->has('prescription_orders') && is_array($request->prescription_orders) && count($request->prescription_orders) > 0) {
                $this->updateOrCreatePrescriptionOrders($consultation, $request->prescription_orders);
            }

            if ($request->has('lab_orders') && is_array($request->lab_orders) && count($request->lab_orders) > 0) {
                $this->updateOrCreateLabOrders($consultation, $request->lab_orders);
            }

            if ($request->has('radiology_orders') && is_array($request->radiology_orders) && count($request->radiology_orders) > 0) {
                $this->updateOrCreateRadiologyOrders($consultation, $request->radiology_orders);
                $hasRadiologyOrders = true;
            }

            if ($isCompleting && $hasRadiologyOrders) {
                $this->ensureRadiologyQueueForConsultation($consultation);
            }

            DB::commit();

            $message = $wasCompleted && !$isCompleting
                ? 'Consultation amended successfully'
                : ($isDraft ? 'Consultation draft saved successfully' : 'Consultation updated successfully');

            return response()->json([
                'success' => true,
                'data' => $consultation->load(['patient', 'doctor', 'branch']),
                'message' => $message,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error updating consultation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified consultation.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($id);

            // Check if consultation can be deleted (e.g., not completed)
            if ($consultation->consultation_status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete completed consultation'
                ], 422);
            }

            $consultation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Consultation deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting consultation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add vitals to a consultation.
     */
    public function addVitals(Request $request, $consultationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'blood_pressure_systolic' => 'nullable|integer|min:0|max:300',
            'blood_pressure_diastolic' => 'nullable|integer|min:0|max:200',
            'pulse_rate' => 'nullable|integer|min:0|max:300',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'oxygen_saturation' => 'nullable|integer|min:0|max:100',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:500',
            'recorded_at' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultation::findOrFail($consultationId);

            // Calculate BMI if height and weight provided
            $bmi = null;
            if ($request->has('height') && $request->has('weight') && $request->height > 0) {
                $heightInMeters = $request->height / 100; // Convert cm to meters
                $bmi = round($request->weight / ($heightInMeters * $heightInMeters), 2);
            }

            $vital = Vital::create([
                'consultation_id' => $consultation->id,
                'blood_pressure_systolic' => $request->blood_pressure_systolic,
                'blood_pressure_diastolic' => $request->blood_pressure_diastolic,
                'pulse_rate' => $request->pulse_rate,
                'respiratory_rate' => $request->respiratory_rate,
                'temperature' => $request->temperature,
                'oxygen_saturation' => $request->oxygen_saturation,
                'height' => $request->height,
                'weight' => $request->weight,
                'bmi' => $bmi,
                'recorded_at' => $request->recorded_at ?? now(),
                'recorded_by' => auth()->id()
            ]);

            // Update consultation vitals array
            $vitalsArray = $consultation->vitals ?? [];
            $vitalsArray[] = [
                'id' => $vital->id,
                'recorded_at' => $vital->recorded_at,
                'blood_pressure' => $vital->blood_pressure_systolic . '/' . $vital->blood_pressure_diastolic,
                'pulse_rate' => $vital->pulse_rate,
                'temperature' => $vital->temperature,
                'weight' => $vital->weight,
                'height' => $vital->height,
                'bmi' => $vital->bmi
            ];
            $consultation->update(['vitals' => $vitalsArray]);

            return response()->json([
                'success' => true,
                'data' => $vital->load(['recordedBy']),
                'message' => 'Vitals recorded successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recording vitals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add diagnosis to a consultation.
     */
    public function addDiagnosis(Request $request, $consultationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'icd_code' => 'required|string|max:20',
            'diagnosis_description' => 'required|string|max:1000',
            'diagnosis_type' => 'required|in:primary,secondary,differential',
            'confidence_level' => 'required|in:confirmed,probable,possible',
            'diagnosis_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultation::findOrFail($consultationId);

            DB::beginTransaction();

            // If this is marked as primary, unmark other primary diagnoses
            if ($request->get('is_primary', false)) {
                $consultation->consultationDiagnoses()
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $diagnosis = Diagnosis::create([
                'consultation_id' => $consultation->id,
                'icd_code' => $request->icd_code,
                'diagnosis_description' => $request->diagnosis_description,
                'diagnosis_type' => $request->diagnosis_type,
                'confidence_level' => $request->confidence_level,
                'diagnosis_date' => $request->diagnosis_date ?? now()->toDateString(),
                'notes' => $request->notes,
                'is_primary' => $request->get('is_primary', false),
                'is_active' => $request->get('is_active', true),
                'diagnosed_by' => auth()->id()
            ]);

            // Update consultation diagnoses array
            $diagnosesArray = $consultation->diagnoses ?? [];
            $diagnosesArray[] = [
                'id' => $diagnosis->id,
                'icd_code' => $diagnosis->icd_code,
                'description' => $diagnosis->diagnosis_description,
                'type' => $diagnosis->diagnosis_type,
                'is_primary' => $diagnosis->is_primary
            ];
            $consultation->update(['diagnoses' => $diagnosesArray]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $diagnosis->load(['diagnosedBy']),
                'message' => 'Diagnosis added successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error adding diagnosis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add intervention to a consultation.
     */
    public function addIntervention(Request $request, $consultationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'intervention_type' => 'required|in:medication,procedure,lab_order,imaging_order,referral,counseling,lifestyle_advice',
            'description' => 'required|string|max:1000',
            'medication_id' => 'nullable|exists:drugs,id',
            'dosage_instructions' => 'nullable|string|max:500',
            'frequency' => 'nullable|string|max:100',
            'duration' => 'nullable|string|max:100',
            'procedure_code' => 'nullable|string|max:50',
            'lab_test_id' => 'nullable|exists:lab_requests,id',
            'imaging_id' => 'nullable|exists:radiology_requests,id',
            'priority' => 'required|in:routine,urgent',
            'notes' => 'nullable|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultation::findOrFail($consultationId);

            $intervention = ConsultationIntervention::create([
                'consultation_id' => $consultation->id,
                'intervention_type' => $request->intervention_type,
                'description' => $request->description,
                'medication_id' => $request->medication_id,
                'dosage_instructions' => $request->dosage_instructions,
                'frequency' => $request->frequency,
                'duration' => $request->duration,
                'procedure_code' => $request->procedure_code,
                'lab_test_id' => $request->lab_test_id,
                'imaging_id' => $request->imaging_id,
                'priority' => $request->priority,
                'notes' => $request->notes,
                'status' => 'ordered',
                'ordered_by' => auth()->id(),
                'ordered_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $intervention->load(['medication', 'orderedBy']),
                'message' => 'Intervention added successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding intervention: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's consultations.
     */
    public function today(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id');
            if (!$branchId) {
                $userBranch = auth()->user()->branches()->first();
                $branchId = $userBranch ? $userBranch->id : 1;
            }

            $consultations = Consultation::with(['patient', 'doctor', 'branch'])
                ->where('branch_id', $branchId)
                ->where('consultation_date', now()->toDateString())
                ->orderBy('consultation_time', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $consultations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching today\'s consultations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ongoing consultations.
     */
    public function ongoing(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id');
            if (!$branchId) {
                $userBranch = auth()->user()->branches()->first();
                $branchId = $userBranch ? $userBranch->id : 1;
            }

            $consultations = Consultation::with(['patient', 'doctor', 'branch'])
                ->where('branch_id', $branchId)
                ->where('consultation_status', 'ongoing')
                ->orderBy('started_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $consultations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching ongoing consultations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get consultations by doctor.
     */
    public function getDoctorConsultations(Request $request, $doctorId): JsonResponse
    {
        try {
            $query = Consultation::with(['patient', 'branch'])
                ->where('doctor_id', $doctorId);

            if ($request->filled('date_from')) {
                $query->where('consultation_date', '>=', $request->get('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->where('consultation_date', '<=', $request->get('date_to'));
            }

            if ($request->filled('status')) {
                $query->where('consultation_status', $request->get('status'));
            }

            $consultations = $query->orderBy('consultation_date', 'desc')
                ->orderBy('consultation_time', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $consultations->items(),
                'meta' => [
                    'current_page' => $consultations->currentPage(),
                    'last_page' => $consultations->lastPage(),
                    'per_page' => $consultations->perPage(),
                    'total' => $consultations->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching doctor consultations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scans for a consultation.
     */
    public function getScans(Request $request, $consultationId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            
            $scans = $consultation->scans()
                ->with(['patient', 'doctor', 'technician', 'branch'])
                ->orderBy('scan_date', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $scans->items(),
                'meta' => [
                    'current_page' => $scans->currentPage(),
                    'last_page' => $scans->lastPage(),
                    'per_page' => $scans->perPage(),
                    'total' => $scans->total(),
                ],
                'message' => 'Scans retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving scans: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a scan for a consultation.
     */
    public function createScan(Request $request, $consultationId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);

            $validator = Validator::make($request->all(), [
                'scan_type' => 'required|string|max:255',
                'scan_date' => 'required|date',
                'scan_time' => 'nullable|date_format:H:i',
                'scan_description' => 'nullable|string',
                'scan_results' => 'nullable|array',
                'scan_images' => 'nullable|array',
                'technician_id' => 'nullable|exists:users,id',
                'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $scan = Scan::create([
                'consultation_id' => $consultation->id,
                'patient_id' => $consultation->patient_id,
                'doctor_id' => $consultation->doctor_id,
                'branch_id' => $consultation->branch_id,
                'scan_type' => $request->scan_type,
                'scan_date' => $request->scan_date,
                'scan_time' => $request->scan_time,
                'scan_description' => $request->scan_description,
                'scan_results' => $request->scan_results,
                'scan_images' => $request->scan_images,
                'technician_id' => $request->technician_id,
                'status' => $request->status ?? 'pending',
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $scan->load(['patient', 'doctor', 'technician', 'branch']),
                'message' => 'Scan created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating scan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a scan.
     */
    public function updateScan(Request $request, $consultationId, $scanId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $scan = Scan::where('consultation_id', $consultation->id)
                ->findOrFail($scanId);

            $validator = Validator::make($request->all(), [
                'scan_type' => 'sometimes|string|max:255',
                'scan_date' => 'sometimes|date',
                'scan_time' => 'nullable|date_format:H:i',
                'scan_description' => 'nullable|string',
                'scan_results' => 'nullable|array',
                'scan_images' => 'nullable|array',
                'technician_id' => 'nullable|exists:users,id',
                'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->has('status') && $request->status === 'completed') {
                $request->merge(['completed_at' => now()]);
            }

            $scan->update(array_merge(
                $request->only([
                    'scan_type',
                    'scan_date',
                    'scan_time',
                    'scan_description',
                    'scan_results',
                    'scan_images',
                    'technician_id',
                    'status',
                    'completed_at'
                ]),
                ['updated_by' => auth()->id()]
            ));

            return response()->json([
                'success' => true,
                'data' => $scan->fresh()->load(['patient', 'doctor', 'technician', 'branch']),
                'message' => 'Scan updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating scan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a scan.
     */
    public function deleteScan($consultationId, $scanId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $scan = Scan::where('consultation_id', $consultation->id)
                ->findOrFail($scanId);

            $scan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Scan deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting scan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create prescription orders from consultation
     */
    private function createPrescriptionOrders(Consultation $consultation, array $prescriptionOrders)
    {
        if (empty($prescriptionOrders)) {
            return;
        }

        // Check for drug interactions before creating prescription
        $this->checkDrugInteractions($prescriptionOrders);

        // Create prescription
        $prescription = \App\Models\Prescription::create([
            'patient_id' => $consultation->patient_id,
            'consultation_id' => $consultation->id,
            'doctor_id' => $consultation->doctor_id,
            'branch_id' => $consultation->branch_id,
            'prescription_date' => $consultation->consultation_date,
            'status' => 'active',
            'notes' => 'Generated from consultation',
            'created_by' => auth()->id()
        ]);

        // Create drug orders and check stock availability
        foreach ($prescriptionOrders as $orderData) {
            if (!empty($orderData['drug_id'])) {
                // Check stock availability
                $stockAvailable = $this->checkStockAvailability($orderData['drug_id'], $orderData['quantity'], $consultation->branch_id);
                
                $drugOrder = \App\Models\DrugOrder::create([
                    'prescription_id' => $prescription->id,
                    'drug_id' => $orderData['drug_id'],
                    'quantity' => $orderData['quantity'],
                    'dosage_instructions' => $orderData['dosage_instructions'],
                    'instructions' => $orderData['dosage_instructions'],
                    'frequency' => $orderData['frequency'] ?? 'As prescribed',
                    'duration' => $orderData['duration'] ?? 'Until finished',
                    'status' => $stockAvailable ? 'pending' : 'out_of_stock'
                ]);

                // Create consultation intervention for tracking
                \App\Models\ConsultationIntervention::create([
                    'consultation_id' => $consultation->id,
                    'intervention_type' => 'medication',
                    'description' => 'Prescription: ' . \App\Models\Drug::find($orderData['drug_id'])->name,
                    'medication_id' => $orderData['drug_id'],
                    'dosage_instructions' => $orderData['dosage_instructions'],
                    'frequency' => $orderData['frequency'] ?? 'As prescribed',
                    'duration' => $orderData['duration'] ?? 'Until finished',
                    'priority' => $orderData['priority'] ?? 'routine',
                    'status' => $stockAvailable ? 'ordered' : 'pending_stock',
                    'ordered_by' => auth()->id(),
                    'ordered_at' => now()
                ]);
            }
        }

        // Create notification for patient about new prescription
        \App\Models\PrescriptionNotification::create([
            'prescription_id' => $prescription->id,
            'patient_id' => $consultation->patient_id,
            'doctor_id' => $consultation->doctor_id,
            'notification_type' => 'prescription_ready',
            'title' => 'New Prescription Ready',
            'message' => 'Your prescription has been created and is ready for dispensing at the pharmacy.',
            'priority' => 'medium',
            'status' => 'pending'
        ]);

        // Create pharmacy visit if not exists
        $existingVisit = \App\Models\Visit::where('patient_id', $consultation->patient_id)
            ->where('visit_type', 'PharmacyOnly')
            ->where('status', 'active')
            ->first();

        if (!$existingVisit) {
            $pharmacyVisit = \App\Models\Visit::create([
                'patient_id' => $consultation->patient_id,
                'visit_token' => 'PHARM-' . strtoupper(uniqid()),
                'visit_type' => 'PharmacyOnly',
                'status' => 'active',
                'branch_id' => $consultation->branch_id,
                'created_by' => auth()->id()
            ]);

            // Add to pharmacy queue
            $nextPosition = \App\Models\Queue::where('queue_type', 'Pharmacy')
                ->where('branch_id', $consultation->branch_id)
                ->whereDate('queued_at', today())
                ->where('status', '!=', 'cancelled')
                ->max('position');
            
            $nextPosition = ($nextPosition ?? 0) + 1;
            
            \App\Models\Queue::create([
                'visit_id' => $pharmacyVisit->id,
                'patient_id' => $consultation->patient_id,
                'branch_id' => $consultation->branch_id,
                'queue_type' => 'Pharmacy',
                'position' => $nextPosition,
                'status' => 'waiting',
                'priority' => 'routine',
                'queued_at' => now()
            ]);
        }
    }

    /**
     * Update existing or create new prescription orders.
     */
    private function updateOrCreatePrescriptionOrders(Consultation $consultation, array $prescriptionOrders): void
    {
        if (empty($prescriptionOrders)) {
            return;
        }

        foreach ($prescriptionOrders as $orderData) {
            if (empty($orderData['drug_id'])) {
                continue;
            }

            if (!empty($orderData['prescription_id'])) {
                $prescription = \App\Models\Prescription::find($orderData['prescription_id']);
                if ($prescription) {
                    $prescription->update([
                        'quantity' => $orderData['quantity'],
                        'dosage_instructions' => $orderData['dosage_instructions'],
                        'duration' => $orderData['duration'] ?? $prescription->duration,
                        'updated_by' => auth()->id(),
                    ]);
                }
                continue;
            }

            $this->checkDrugInteractions([$orderData]);
            $drug = \App\Models\Drug::find($orderData['drug_id']);
            $prescription = \App\Models\Prescription::create([
                'patient_id' => $consultation->patient_id,
                'consultation_id' => $consultation->id,
                'doctor_id' => $consultation->doctor_id,
                'branch_id' => $consultation->branch_id,
                'prescription_date' => $consultation->consultation_date,
                'drug_id' => $orderData['drug_id'],
                'quantity' => $orderData['quantity'],
                'dosage_instructions' => $orderData['dosage_instructions'],
                'duration' => $orderData['duration'] ?? 'Until finished',
                'status' => 'active',
                'notes' => 'Generated from consultation',
                'created_by' => auth()->id(),
            ]);

            \App\Models\DrugOrder::create([
                'prescription_id' => $prescription->id,
                'drug_id' => $orderData['drug_id'],
                'quantity' => $orderData['quantity'],
                'dosage_instructions' => $orderData['dosage_instructions'],
                'instructions' => $orderData['dosage_instructions'],
                'frequency' => $orderData['frequency'] ?? 'As prescribed',
                'duration' => $orderData['duration'] ?? 'Until finished',
                'status' => 'pending',
            ]);

            \App\Models\ConsultationIntervention::create([
                'consultation_id' => $consultation->id,
                'intervention_type' => 'medication',
                'description' => 'Prescription: ' . ($drug->name ?? 'Medication'),
                'medication_id' => $orderData['drug_id'],
                'dosage_instructions' => $orderData['dosage_instructions'],
                'frequency' => $orderData['frequency'] ?? 'As prescribed',
                'duration' => $orderData['duration'] ?? 'Until finished',
                'priority' => $orderData['priority'] ?? 'routine',
                'status' => 'ordered',
                'ordered_by' => auth()->id(),
                'ordered_at' => now(),
            ]);
        }
    }

    /**
     * Update existing or create new lab orders.
     */
    private function updateOrCreateLabOrders(Consultation $consultation, array $labOrders): void
    {
        if (empty($labOrders)) {
            return;
        }

        foreach ($labOrders as $orderData) {
            if (empty($orderData['test_type_id'])) {
                continue;
            }

            if (!empty($orderData['lab_request_id'])) {
                $labRequest = LabRequest::find($orderData['lab_request_id']);
                $testType = \App\Models\LabTestType::find($orderData['test_type_id']);

                if ($labRequest && $testType) {
                    $updateData = [
                        'test_type_id' => $orderData['test_type_id'],
                        'test_type' => $testType->test_name,
                        'test_description' => $testType->test_name . ' (' . $testType->test_code . ')',
                        'priority' => $orderData['priority'] ?? $labRequest->priority,
                        'specimen_type' => $orderData['specimen_type'] ?? $testType->specimen_type,
                        'updated_by' => auth()->id(),
                    ];
                    $templateId = $testType->getResolvedTemplateId();
                    if ($templateId) {
                        $updateData['template_id'] = $templateId;
                        $labRequest->addTemplates([$templateId]);
                    }
                    $labRequest->update($updateData);
                }
                continue;
            }

            $testType = \App\Models\LabTestType::find($orderData['test_type_id']);
            if (!$testType) {
                continue;
            }

            $templateId = $testType->getResolvedTemplateId();
            $labRequest = LabRequest::create([
                'patient_id' => $consultation->patient_id,
                'consultation_id' => $consultation->id,
                'doctor_id' => $consultation->doctor_id,
                'branch_id' => $consultation->branch_id,
                'test_type_id' => $testType->id,
                'template_id' => $templateId,
                'test_type' => $testType->test_name,
                'test_description' => $testType->test_name . ' (' . $testType->test_code . ')',
                'clinical_notes' => $consultation->doctors_impression,
                'priority' => $orderData['priority'] ?? 'routine',
                'specimen_type' => $orderData['specimen_type'] ?? $testType->specimen_type,
                'collection_instructions' => $testType->collection_instructions,
                'special_instructions' => $testType->preparation_instructions,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            if ($templateId) {
                $labRequest->addTemplates([$templateId]);
            }
        }
    }

    /**
     * Update existing or create new radiology orders (queue created on completion only).
     */
    private function updateOrCreateRadiologyOrders(Consultation $consultation, array $radiologyOrders): void
    {
        if (empty($radiologyOrders)) {
            return;
        }

        foreach ($radiologyOrders as $orderData) {
            if (empty($orderData['modality_id']) || empty($orderData['department_id'])) {
                continue;
            }

            if (!empty($orderData['radiology_request_id'])) {
                $radiologyRequest = \App\Models\RadiologyRequest::find($orderData['radiology_request_id']);
                if ($radiologyRequest) {
                    $radiologyRequest->update([
                        'modality_id' => $orderData['modality_id'],
                        'department_id' => $orderData['department_id'],
                        'clinical_history' => $orderData['clinical_history'] ?? $radiologyRequest->clinical_history,
                        'clinical_question' => $orderData['clinical_question'] ?? $radiologyRequest->clinical_question,
                        'indication' => $orderData['indication'] ?? $radiologyRequest->indication,
                        'priority' => $orderData['priority'] ?? $radiologyRequest->priority,
                        'scheduled_date' => !empty($orderData['scheduled_date']) ? $orderData['scheduled_date'] : null,
                        'scheduled_time' => !empty($orderData['scheduled_time']) ? $orderData['scheduled_time'] : null,
                    ]);
                }
                continue;
            }

            $requestNumber = 'RAD-' . strtoupper(\Illuminate\Support\Str::random(8));
            $modality = \App\Models\ImagingModality::find($orderData['modality_id']);

            $radiologyRequest = \App\Models\RadiologyRequest::create([
                'request_number' => $requestNumber,
                'patient_id' => $consultation->patient_id,
                'consultation_id' => $consultation->id,
                'doctor_id' => $consultation->doctor_id,
                'branch_id' => $consultation->branch_id,
                'modality_id' => $orderData['modality_id'],
                'department_id' => $orderData['department_id'],
                'clinical_history' => $orderData['clinical_history'] ?? $consultation->chief_complaint ?? '',
                'clinical_question' => $orderData['clinical_question'] ?? '',
                'indication' => $orderData['indication'] ?? '',
                'priority' => $orderData['priority'] ?? 'routine',
                'scheduled_date' => !empty($orderData['scheduled_date']) ? $orderData['scheduled_date'] : null,
                'scheduled_time' => !empty($orderData['scheduled_time']) ? $orderData['scheduled_time'] : null,
                'requested_date' => now(),
                'status' => 'requested',
                'billing_status' => 'pending',
                'billing_amount' => $modality?->base_cost,
            ]);

            \App\Models\ConsultationIntervention::create([
                'consultation_id' => $consultation->id,
                'intervention_type' => 'imaging_order',
                'description' => 'Radiology Request: ' . ($modality->name ?? 'Unknown Modality'),
                'procedure_code' => $radiologyRequest->request_number,
                'priority' => $orderData['priority'] ?? 'routine',
                'status' => 'ordered',
                'ordered_by' => auth()->id(),
                'ordered_at' => now(),
            ]);
        }
    }

    /**
     * Ensure radiology queue exists for this consultation's visit (call on completion only).
     */
    private function ensureRadiologyQueueForConsultation(Consultation $consultation): void
    {
        $visit = $consultation->visit;

        if (!$visit) {
            $visit = Visit::create([
                'patient_id' => $consultation->patient_id,
                'visit_token' => 'RAD-' . strtoupper(uniqid()),
                'visit_type' => 'RadiologyOnly',
                'status' => 'active',
                'branch_id' => $consultation->branch_id,
                'check_in_time' => now(),
                'created_by' => auth()->id(),
            ]);
        }

        $existingQueue = Queue::where('visit_id', $visit->id)
            ->where('queue_type', 'Radiology')
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingQueue) {
            return;
        }

        $lastPosition = Queue::where('queue_type', 'Radiology')
            ->where('branch_id', $consultation->branch_id)
            ->where('status', '!=', 'cancelled')
            ->max('position') ?? 0;

        Queue::create([
            'visit_id' => $visit->id,
            'patient_id' => $consultation->patient_id,
            'branch_id' => $consultation->branch_id,
            'queue_type' => 'Radiology',
            'ticket_number' => Queue::generateTicketNumber('Radiology', $consultation->branch_id),
            'position' => $lastPosition + 1,
            'status' => 'waiting',
            'priority' => 'routine',
            'queued_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create lab orders from consultation
     */
    private function createLabOrders(Consultation $consultation, array $labOrders)
    {
        if (empty($labOrders)) {
            return;
        }

        foreach ($labOrders as $orderData) {
            if (!empty($orderData['test_type_id'])) {
                // Get the lab test type details
                $testType = \App\Models\LabTestType::find($orderData['test_type_id']);
                
                if ($testType) {
                    $templateId = $testType->getResolvedTemplateId();

                    $labRequest = \App\Models\LabRequest::create([
                        'patient_id' => $consultation->patient_id,
                        'consultation_id' => $consultation->id,
                        'doctor_id' => $consultation->doctor_id,
                        'branch_id' => $consultation->branch_id,
                        'test_type_id' => $testType->id,
                        'template_id' => $templateId,
                        'test_type' => $testType->test_name,
                        'test_description' => $testType->test_name . ' (' . $testType->test_code . ')',
                        'clinical_notes' => $consultation->doctors_impression,
                        'priority' => $orderData['priority'] ?? 'routine',
                        'specimen_type' => $orderData['specimen_type'] ?? $testType->specimen_type,
                        'collection_instructions' => $testType->collection_instructions,
                        'special_instructions' => $testType->preparation_instructions,
                        'status' => 'pending',
                        'created_by' => auth()->id()
                    ]);
                    
                    if ($templateId) {
                        $labRequest->addTemplates([$templateId]);
                    }
                }
            }
        }

        // Create lab visit if not exists
        $existingVisit = \App\Models\Visit::where('patient_id', $consultation->patient_id)
            ->where('visit_type', 'LabOnly')
            ->where('status', 'active')
            ->first();

        if (!$existingVisit) {
            $labVisit = \App\Models\Visit::create([
                'patient_id' => $consultation->patient_id,
                'visit_token' => 'LAB-' . strtoupper(uniqid()),
                'visit_type' => 'LabOnly',
                'status' => 'active',
                'branch_id' => $consultation->branch_id,
                'created_by' => auth()->id()
            ]);

            // Add to lab queue
            $nextPosition = \App\Models\Queue::where('queue_type', 'Lab')
                ->where('branch_id', $consultation->branch_id)
                ->whereDate('queued_at', today())
                ->where('status', '!=', 'cancelled')
                ->max('position');
            
            $nextPosition = ($nextPosition ?? 0) + 1;
            
            \App\Models\Queue::create([
                'visit_id' => $labVisit->id,
                'patient_id' => $consultation->patient_id,
                'branch_id' => $consultation->branch_id,
                'queue_type' => 'Lab',
                'position' => $nextPosition,
                'status' => 'waiting',
                'priority' => 'routine',
                'queued_at' => now()
            ]);
        }
    }

    /**
     * Create radiology orders from consultation (used when completing a new consultation).
     */
    private function createRadiologyOrders(Consultation $consultation, array $radiologyOrders): void
    {
        $this->updateOrCreateRadiologyOrders($consultation, $radiologyOrders);
        $this->ensureRadiologyQueueForConsultation($consultation);
    }

    /**
     * Check stock availability for a drug
     */
    private function checkStockAvailability($drugId, $quantity, $branchId): bool
    {
        $stock = \App\Models\DrugStock::where('drug_id', $drugId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();

        if (!$stock) {
            return false;
        }

        return $stock->current_stock >= $quantity;
    }

    /**
     * Check for drug interactions
     */
    private function checkDrugInteractions(array $prescriptionOrders)
    {
        $drugIds = collect($prescriptionOrders)->pluck('drug_id')->filter()->toArray();
        
        if (count($drugIds) < 2) {
            return; // No interactions possible with single drug
        }

        // Check for drug interactions using the database
        $interactions = \App\Models\DrugInteraction::checkMultipleDrugs($drugIds);
        
        if (!empty($interactions)) {
            // Log serious interactions for pharmacist review
            $seriousInteractions = collect($interactions)->whereIn('severity', ['major', 'severe']);
            
            if ($seriousInteractions->isNotEmpty()) {
                \Log::warning('Serious drug interactions detected', [
                    'drug_ids' => $drugIds,
                    'consultation_id' => request()->input('consultation_id'),
                    'doctor_id' => auth()->id(),
                    'interactions' => $seriousInteractions->map(function($interaction) {
                        return [
                            'drug1' => $interaction->drug1->name ?? 'Unknown',
                            'drug2' => $interaction->drug2->name ?? 'Unknown',
                            'severity' => $interaction->severity,
                            'description' => $interaction->description
                        ];
                    })->toArray()
                ]);
            }
        }

        // Log prescription creation for audit
        \Log::info('Prescription created with multiple drugs', [
            'drug_ids' => $drugIds,
            'consultation_id' => request()->input('consultation_id'),
            'doctor_id' => auth()->id(),
            'interactions_found' => count($interactions ?? [])
        ]);
    }

    /**
     * Get doctor's consultation queue
     */
    public function getDoctorQueue(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'branch_id' => 'nullable|exists:branches,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $branchId = $request->get('branch_id') ?? auth()->user()->staffProfile?->branch_id;

            if (!$branchId) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'queue' => [],
                        'current' => null,
                        'statistics' => [
                            'pending_consultations' => 0,
                            'in_progress' => 0,
                            'completed_today' => 0,
                            'avg_wait_time' => 0,
                        ],
                    ],
                    'message' => 'No branch assigned to your account. Contact admin to assign a branch.',
                ]);
            }

            $doctorId = auth()->id();

            $opdQueueVisitIds = Queue::where('queue_type', 'OPD')
                ->where('branch_id', $branchId)
                ->whereIn('status', ['waiting', 'called'])
                ->pluck('visit_id');
            
            // Draft consultations for this doctor, plus OPD-queue visits at branch without a specific doctor
            $consultationQueue = Consultation::with(['patient', 'visit', 'vitals'])
                ->where('branch_id', $branchId)
                ->where('consultation_status', 'ongoing')
                ->whereNotIn('consultation_status', ['completed', 'cancelled', 'transferred'])
                ->where('is_draft', true)
                ->where(function ($query) use ($doctorId, $opdQueueVisitIds) {
                    $query->where('doctor_id', $doctorId);
                    if ($opdQueueVisitIds->isNotEmpty()) {
                        $query->orWhere(function ($q) use ($doctorId, $opdQueueVisitIds) {
                            $q->whereIn('visit_id', $opdQueueVisitIds)
                                ->whereHas('visit', function ($visitQuery) use ($doctorId) {
                                    $visitQuery->where('status', 'active')
                                        ->where(function ($inner) use ($doctorId) {
                                            $inner->whereNull('assigned_doctor_id')
                                                ->orWhere('assigned_doctor_id', $doctorId);
                                        });
                                });
                        });
                    }
                })
                ->orderByRaw("CASE WHEN urgency = 'critical' THEN 1 WHEN urgency = 'urgent' THEN 2 ELSE 3 END")
                ->orderBy('created_at', 'asc')
                ->get();
            
            $visitsWithoutConsultations = Visit::with(['patient'])
                ->where('branch_id', $branchId)
                ->where('status', 'active')
                ->where(function ($query) use ($doctorId, $opdQueueVisitIds) {
                    $query->where('assigned_doctor_id', $doctorId);
                    if ($opdQueueVisitIds->isNotEmpty()) {
                        $query->orWhere(function ($q) use ($doctorId, $opdQueueVisitIds) {
                            $q->whereIn('id', $opdQueueVisitIds)
                                ->where(function ($inner) use ($doctorId) {
                                    $inner->whereNull('assigned_doctor_id')
                                        ->orWhere('assigned_doctor_id', $doctorId);
                                });
                        });
                    }
                })
                ->whereDoesntHave('consultation', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId)
                        ->whereNotIn('consultation_status', ['cancelled']);
                })
                ->orderByRaw("CASE WHEN priority = 'critical' THEN 1 WHEN priority = 'urgent' THEN 2 ELSE 3 END")
                ->orderBy('created_at', 'asc')
                ->get();
            
            // Create draft consultations for visits that don't have consultations yet
            $consultationService = app(\App\Services\ConsultationService::class);
            
            foreach ($visitsWithoutConsultations as $visit) {
                $draftConsultation = $consultationService->createDraftConsultationForVisit($visit, $doctorId);
                
                if ($draftConsultation) {
                    $draftConsultation->load(['patient', 'visit', 'vitals']);
                    $consultationQueue->push($draftConsultation);
                }
            }
            
            // Re-sort the queue
            $consultationQueue = $consultationQueue->sortBy(function($consultation) {
                $priorityOrder = ['critical' => 1, 'urgent' => 2, 'routine' => 3];
                return [
                    $priorityOrder[$consultation->urgency ?? 'routine'] ?? 3,
                    $consultation->created_at->timestamp
                ];
            })->values();
            
            // Get current consultation being worked on
            $currentConsultation = Consultation::with(['patient', 'visit', 'vitals'])
                ->where('branch_id', $branchId)
                ->where('consultation_status', 'ongoing')
                ->whereNotIn('consultation_status', ['completed', 'cancelled', 'transferred'])
                ->where('is_draft', false)
                ->where('doctor_id', $doctorId)
                ->first();
            
            // Calculate statistics
            $stats = [
                'pending_consultations' => $consultationQueue->count(),
                'in_progress' => $currentConsultation ? 1 : 0,
                'completed_today' => Consultation::where('branch_id', $branchId)
                    ->where('doctor_id', $doctorId)
                    ->where('consultation_status', 'completed')
                    ->whereDate('updated_at', today())
                    ->count(),
                'avg_wait_time' => 15
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'queue' => $consultationQueue,
                    'current' => $currentConsultation,
                    'statistics' => $stats
                ],
                'message' => 'Doctor consultation queue retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve doctor queue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get doctor's completed consultations
     */
    public function getCompletedConsultations(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'branch_id' => 'nullable|exists:branches,id',
                'date_filter' => 'nullable|in:today,week,month,all'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $branchId = $request->get('branch_id') ?? auth()->user()->staffProfile?->branch_id;

            if (!$branchId) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No branch assigned to your account.',
                ]);
            }
            $dateFilter = $request->get('date_filter', 'today');
            $doctorId = auth()->id();
            
            $query = Consultation::with(['patient', 'visit', 'vitals', 'diagnoses', 'interventions'])
                ->where('branch_id', $branchId)
                ->where('doctor_id', $doctorId)
                ->where('consultation_status', 'completed');
            
            // Apply date filter
            switch ($dateFilter) {
                case 'today':
                    $query->whereDate('updated_at', today());
                    break;
                case 'week':
                    $query->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('updated_at', now()->month)
                          ->whereYear('updated_at', now()->year);
                    break;
                // 'all' - no additional filter
            }
            
            $consultations = $query->orderBy('updated_at', 'desc')->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => $consultations->items(),
                'meta' => [
                    'current_page' => $consultations->currentPage(),
                    'last_page' => $consultations->lastPage(),
                    'per_page' => $consultations->perPage(),
                    'total' => $consultations->total()
                ],
                'message' => 'Completed consultations retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve completed consultations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call next consultation in queue
     */
    public function callNextConsultation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required|exists:branches,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $branchId = $request->branch_id;
            $doctorId = auth()->id();

            // Find next consultation in queue (priority-based)
            $nextConsultation = Consultation::with(['patient', 'visit'])
                ->where('branch_id', $branchId)
                ->where('doctor_id', $doctorId)
                ->where('consultation_status', 'ongoing')
                ->where('is_draft', true)
                ->orderByRaw("CASE WHEN urgency = 'critical' THEN 1 WHEN urgency = 'urgent' THEN 2 ELSE 3 END")
                ->orderBy('created_at', 'asc')
                ->first();

            if (!$nextConsultation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No consultations waiting in queue'
                ], 404);
            }

            try {
                app(\App\Services\PaymentPolicyService::class)->assertCanProceedWithConsultation(
                    $nextConsultation->visit,
                    (int) $nextConsultation->patient_id,
                    $nextConsultation->branch_id
                );
            } catch (\App\Exceptions\PaymentGateException $e) {
                return response()->json(array_merge([
                    'success' => false,
                ], $e->toArray()), 402);
            }

            // Update consultation status to indicate it's being called
            $nextConsultation->update([
                'called_at' => now(),
                'called_by' => auth()->id()
            ]);

            // Prepare data for audio announcement
            $audioData = [
                'consultation_id' => $nextConsultation->id,
                'patient' => [
                    'first_name' => $nextConsultation->patient->first_name,
                    'last_name' => $nextConsultation->patient->last_name,
                    'patient_number' => $nextConsultation->patient->patient_number
                ],
                'visit_token' => $nextConsultation->visit->visit_token ?? 'N/A',
                'priority' => $nextConsultation->urgency ?? 'routine',
                'chief_complaint' => $nextConsultation->chief_complaint
            ];

            return response()->json([
                'success' => true,
                'data' => $audioData,
                'message' => 'Patient called successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to call next consultation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lab requests and results for a consultation (doctor mobile/API).
     */
    public function getLabRequests($id): JsonResponse
    {
        try {
            $consultation = Consultation::with(['patient', 'doctor'])->findOrFail($id);

            $this->assertConsultationAccess($consultation);

            $consultation->load([
                'labRequests' => function ($query) {
                    $query->with([
                        'results' => fn ($q) => $q->orderBy('parameter_name'),
                        'testType',
                        'template',
                    ])->latest('updated_at');
                },
            ]);

            $this->attachConsultationLabRequests($consultation);

            return response()->json([
                'success' => true,
                'data' => $consultation->labRequests,
                'message' => 'Consultation lab requests retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve consultation lab requests',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Ensure doctors and patients can only access consultations they own.
     */
    private function assertConsultationAccess(Consultation $consultation): void
    {
        $user = auth()->user();

        if ($user->hasRole('doctor') && (int) $consultation->doctor_id !== (int) $user->id) {
            abort(403, 'You can only access your own consultations.');
        }

        $portalPatient = $this->portalPatient();
        if ($portalPatient && (int) $consultation->patient_id !== (int) $portalPatient->id) {
            abort(403, 'You can only view your own consultations.');
        }
    }

    /**
     * Load lab requests for a consultation, including legacy rows missing consultation_id.
     */
    private function attachConsultationLabRequests(Consultation $consultation): void
    {
        if ($consultation->labRequests->isNotEmpty()) {
            return;
        }

        if (!$consultation->patient_id || !$consultation->doctor_id) {
            return;
        }

        $legacyLabRequests = LabRequest::with([
            'results' => fn ($q) => $q->orderBy('parameter_name'),
            'testType',
            'template',
        ])
            ->where('patient_id', $consultation->patient_id)
            ->where('doctor_id', $consultation->doctor_id)
            ->whereNull('consultation_id')
            ->whereDate('created_at', $consultation->consultation_date)
            ->latest('updated_at')
            ->get();

        $consultation->setRelation('labRequests', $legacyLabRequests);
    }
}