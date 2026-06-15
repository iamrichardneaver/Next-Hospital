<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Services\PaymentPolicyService;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\Queue;
use App\Models\Consultation;
use App\Models\EmergencyVisit;
use App\Models\EmergencyAlert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VisitController extends Controller
{
    use ResolvesUserBranch, WorkflowNavigation;
    /**
     * Display a listing of visits.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Visit::with(['patient', 'branch', 'assignedDoctor', 'assignedNurse', 'queues']);

        // Role-based data filtering
        if ($user->hasRole('patient')) {
            // Patients can only see their own visits
            $query->where('patient_id', $user->id);
        } elseif ($user->hasRole(['doctor', 'nurse', 'pharmacist', 'receptionist', 'lab_technician'])) {
            // Medical staff can see visits from their branch
            if ($user->staffProfile && $user->staffProfile->branch_id) {
                $query->where('branch_id', $user->staffProfile->branch_id);
            }
        }
        // Super admin and other roles can see all visits

        // Filter by visit type
        if ($request->has('visit_type')) {
            $query->where('visit_type', $request->visit_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by branch (only for non-patient roles)
        if (!$user->hasRole('patient') && $request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('check_in_time', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('check_in_time', '<=', $request->date_to);
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Search by patient name or visit token
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('visit_token', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($patientQuery) use ($search) {
                      $patientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('patient_number', 'like', "%{$search}%");
                  });
            });
        }

        $visits = $query->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $visits,
            'message' => 'Visits retrieved successfully'
        ]);
    }

    /**
     * Store a newly created visit.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'visit_type' => 'required|in:OPD,IPD,Emergency,LabOnly,PharmacyOnly,RadiologyOnly',
            'chief_complaint' => 'nullable|string|max:1000',
            'priority' => 'nullable|in:routine,urgent,critical',
            'referral_source' => 'nullable|string|max:255',
            'referral_notes' => 'nullable|string|max:1000',
            'assigned_doctor_id' => 'nullable|exists:users,id',
            'assigned_nurse_id' => 'nullable|exists:users,id',
            'vital_signs' => 'nullable|array',
            'visit_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create visit
            $visit = Visit::create([
                'patient_id' => $request->patient_id,
                'branch_id' => $request->branch_id,
                'visit_type' => $request->visit_type,
                'chief_complaint' => $request->chief_complaint,
                'priority' => $request->priority ?? 'routine',
                'referral_source' => $request->referral_source,
                'referral_notes' => $request->referral_notes,
                'assigned_doctor_id' => $request->assigned_doctor_id,
                'assigned_nurse_id' => $request->assigned_nurse_id,
                'vital_signs' => $request->vital_signs,
                'visit_notes' => $request->visit_notes,
                'check_in_time' => now(),
                'status' => 'active',
                'created_by' => auth()->id()
            ]);

            // Add to appropriate queue based on visit type (IPD does not get a queue; parity with web)
            $this->addToQueue($visit, $request->visit_type);

            // If emergency visit, create emergency visit record
            if ($request->visit_type === 'Emergency') {
                $this->createEmergencyVisit($visit, $request);
            }

            // Only OPD, IPD, and Emergency go through consultation flow (parity with web Flow 2)
            $consultationVisitTypes = ['OPD', 'IPD', 'Emergency'];
            if (in_array($visit->visit_type, $consultationVisitTypes)) {
                // Default doctor when not provided (parity with web)
                if (!$visit->assigned_doctor_id) {
                    $defaultDoctor = \App\Models\User::role('doctor')
                        ->whereHas('staffProfile', fn($q) => $q->where('branch_id', $visit->branch_id))
                        ->where('is_active', true)
                        ->first();
                    if (!$defaultDoctor) {
                        $defaultDoctor = \App\Models\User::role('doctor')->where('is_active', true)->first();
                    }
                    if ($defaultDoctor) {
                        $visit->update(['assigned_doctor_id' => $defaultDoctor->id]);
                        $visit->refresh();
                    }
                }
                if ($visit->assigned_doctor_id) {
                    $consultationService = app(\App\Services\ConsultationService::class);
                    $consultationService->createDraftConsultationForVisit($visit);
                }
                // Initialize workflow only for consultation-path visits
                $workflow = null;
                try {
                    $workflowName = $this->determineWorkflowName($visit);
                    $instance = $this->initializeWorkflowForEntity($visit, $workflowName);
                    if ($instance) {
                        $this->initializeWorkflowServices();
                        $suggestion = $this->navigationService->getNextStepSuggestion($instance->id, auth()->id());
                        $workflow = $this->navigationService->formatSuggestionForResponse($suggestion);
                    }
                } catch (\Throwable $workflowException) {
                    \Log::warning('API Visit workflow initialization failed', [
                        'error' => $workflowException->getMessage(),
                        'patient_id' => $request->patient_id ?? null,
                        'visit_type' => $request->visit_type ?? null,
                    ]);
                }
            } else {
                $workflow = null;
            }

            DB::commit();

            $paymentSummary = app(PaymentPolicyService::class)->getPaymentStatusSummary(
                (int) $visit->patient_id,
                (int) $visit->branch_id,
                $visit
            );

            return response()->json([
                'success' => true,
                'data' => $visit->load(['patient', 'branch', 'assignedDoctor', 'assignedNurse', 'queues']),
                'workflow' => $workflow,
                'payment_summary' => $paymentSummary,
                'payment_required' => $paymentSummary['payment_required'] ?? false,
                'can_proceed' => $paymentSummary['can_proceed'] ?? true,
                'message' => 'Visit created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating visit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified visit.
     */
    public function show($id): JsonResponse
    {
        $visit = Visit::with([
            'patient', 
            'branch', 
            'assignedDoctor', 
            'assignedNurse', 
            'queues',
            'consultation',
            'emergencyVisit',
            'bedAssignment'
        ])->findOrFail($id);

        // Get workflow instance and progress if exists
        $workflow = null;
        $workflowProgress = null;
        
        try {
            $this->initializeWorkflowServices();
            $workflowInstance = $this->getWorkflowInstance($visit);
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
            // Silently fail if workflow doesn't exist - not all visits have workflows
            \Log::debug('Workflow data not available for visit', ['visit_id' => $id, 'error' => $e->getMessage()]);
        }

        $response = [
            'success' => true,
            'data' => $visit,
            'message' => 'Visit retrieved successfully'
        ];

        if ($workflow) {
            $response['workflow'] = $workflow;
        }

        if ($workflowProgress) {
            $response['workflow_progress'] = $workflowProgress;
        }

        return response()->json($response);
    }

    /**
     * Update the specified visit.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $visit = Visit::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'visit_type' => 'sometimes|in:OPD,IPD,Emergency,LabOnly,PharmacyOnly,RadiologyOnly',
            'status' => 'sometimes|in:active,completed,cancelled,transferred',
            'chief_complaint' => 'nullable|string|max:1000',
            'priority' => 'sometimes|in:routine,urgent,critical',
            'assigned_doctor_id' => 'nullable|exists:users,id',
            'assigned_nurse_id' => 'nullable|exists:users,id',
            'vital_signs' => 'nullable|array',
            'visit_notes' => 'nullable|string|max:1000',
            'check_out_time' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only([
            'visit_type', 'status', 'chief_complaint', 'priority',
            'assigned_doctor_id', 'assigned_nurse_id', 'vital_signs', 'visit_notes'
        ]);

        if ($request->has('check_out_time')) {
            $updateData['check_out_time'] = $request->check_out_time;
        }

        $updateData['updated_by'] = auth()->id();

        $visit->update($updateData);

        return response()->json([
            'success' => true,
            'data' => $visit->load(['patient', 'branch', 'assignedDoctor', 'assignedNurse', 'queues']),
            'message' => 'Visit updated successfully'
        ]);
    }

    /**
     * Complete a visit.
     */
    public function complete(Request $request, $id): JsonResponse
    {
        $visit = Visit::findOrFail($id);

        if ($visit->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is already completed'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update visit status
            $visit->update([
                'status' => 'completed',
                'check_out_time' => now(),
                'updated_by' => auth()->id()
            ]);

            // Complete all associated queues
            $visit->queues()->where('status', '!=', 'completed')->update([
                'status' => 'completed',
                'completed_at' => now(),
                'served_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $visit->load(['patient', 'branch', 'queues']),
                'message' => 'Visit completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error completing visit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a visit.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $visit = Visit::findOrFail($id);

        if ($visit->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a completed visit'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update visit status
            $visit->update([
                'status' => 'cancelled',
                'check_out_time' => now(),
                'updated_by' => auth()->id()
            ]);

            // Cancel all associated queues
            $visit->queues()->where('status', '!=', 'completed')->update([
                'status' => 'cancelled',
                'completed_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $visit->load(['patient', 'branch', 'queues']),
                'message' => 'Visit cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling visit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get visit statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $branchId = $request->get('branch_id');
        $dateFrom = $request->get('date_from', now()->startOfDay());
        $dateTo = $request->get('date_to', now()->endOfDay());

        $query = Visit::whereBetween('check_in_time', [$dateFrom, $dateTo]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stats = [
            'total_visits' => $query->count(),
            'active_visits' => $query->where('status', 'active')->count(),
            'completed_visits' => $query->where('status', 'completed')->count(),
            'cancelled_visits' => $query->where('status', 'cancelled')->count(),
            'by_type' => $query->selectRaw('visit_type, COUNT(*) as count')
                              ->groupBy('visit_type')
                              ->pluck('count', 'visit_type'),
            'by_priority' => $query->selectRaw('priority, COUNT(*) as count')
                                  ->groupBy('priority')
                                  ->pluck('count', 'priority'),
            'average_duration' => $query->whereNotNull('check_out_time')
                                       ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, check_in_time, check_out_time)) as avg_duration')
                                       ->value('avg_duration')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Visit statistics retrieved successfully'
        ]);
    }

    /**
     * Add visit to appropriate queue (parity with web).
     * IPD does not get a queue; one active queue per visit per queue type (no duplicates).
     */
    private function addToQueue(Visit $visit, string $visitType): void
    {
        $queueType = match ($visitType) {
            'OPD' => 'OPD',
            'Emergency' => 'Emergency',
            'LabOnly' => 'Lab',
            'PharmacyOnly' => 'Pharmacy',
            'RadiologyOnly' => 'Radiology',
            'IPD' => null, // IPD goes to bed assignment, no queue (parity with web)
            default => 'OPD',
        };

        if ($queueType === null) {
            return;
        }

        // Prevent duplicate: one active queue per visit per queue type
        $existing = Queue::where('visit_id', $visit->id)
            ->where('queue_type', $queueType)
            ->whereIn('status', ['waiting', 'called', 'serving'])
            ->exists();
        if ($existing) {
            return;
        }

        $lastPosition = Queue::where('queue_type', $queueType)
            ->where('branch_id', $visit->branch_id)
            ->where('status', '!=', 'cancelled')
            ->max('position') ?? 0;

        $ticketNumber = Queue::generateTicketNumber($queueType, $visit->branch_id);

        Queue::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'branch_id' => $visit->branch_id,
            'queue_type' => $queueType,
            'ticket_number' => $ticketNumber,
            'position' => $lastPosition + 1,
            'status' => 'waiting',
            'queued_at' => now(),
            'priority' => $visit->priority ?? 'routine',
            'estimated_wait_time' => $this->calculateEstimatedWaitTime($queueType, $visit->branch_id),
        ]);
    }

    /**
     * Create emergency visit record.
     */
    private function createEmergencyVisit(Visit $visit, Request $request): void
    {
        $emergencyVisit = EmergencyVisit::create([
            'patient_id' => $visit->patient_id,
            'branch_id' => $visit->branch_id,
            'visit_number' => $visit->visit_token,
            'arrival_time' => $visit->check_in_time,
            'chief_complaint' => $visit->chief_complaint ?? 'Emergency visit',
            'arrival_mode' => $request->get('arrival_mode', 'walk-in'),
            'vital_signs' => $visit->vital_signs ?? [],
            'triage_level' => $request->get('triage_level', 3),
            'priority' => $visit->priority,
            'status' => 'active',
            'created_by' => auth()->id()
        ]);

        // Create emergency alert
        $this->createEmergencyAlert($emergencyVisit, $request);
    }

    /**
     * Create emergency alert for emergency visit.
     */
    private function createEmergencyAlert(EmergencyVisit $emergencyVisit, Request $request): void
    {
        $triageLevel = $request->get('triage_level', 3);
        $priority = $triageLevel <= 2 ? 'critical' : ($triageLevel <= 3 ? 'high' : 'medium');
        
        $alertMessage = "Emergency patient arrived: {$emergencyVisit->visit_number} - {$emergencyVisit->chief_complaint}";
        if ($triageLevel <= 2) {
            $alertMessage .= " (CRITICAL - Immediate attention required)";
        }

        EmergencyAlert::create([
            'emergency_visit_id' => $emergencyVisit->id,
            'alert_type' => 'patient_arrival',
            'message' => $alertMessage,
            'priority' => $priority,
            'status' => 'active',
            'created_by' => auth()->id()
        ]);
    }

    /**
     * Calculate estimated wait time for queue.
     */
    private function calculateEstimatedWaitTime(string $queueType, int $branchId): int
    {
        // Get average service time for this queue type
        $avgServiceTime = Queue::where('queue_type', $queueType)
                              ->where('branch_id', $branchId)
                              ->where('status', 'completed')
                              ->whereNotNull('serving_at')
                              ->whereNotNull('completed_at')
                              ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, serving_at, completed_at)) as avg_time')
                              ->value('avg_time') ?? 15; // Default 15 minutes

        // Get number of patients ahead in queue
        $patientsAhead = Queue::where('queue_type', $queueType)
                             ->where('branch_id', $branchId)
                             ->where('status', 'waiting')
                             ->count();

        return $patientsAhead * $avgServiceTime;
    }

    /**
     * Remove the specified visit.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $visit = Visit::findOrFail($id);

            // Prevent deletion of active visits
            if ($visit->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete an active visit. Please complete or cancel it first.'
                ], 400);
            }

            // Check for associated records
            $hasQueues = $visit->queues()->count() > 0;
            $hasConsultations = $visit->consultations()->count() > 0;
            $hasLabRequests = $visit->labRequests()->count() > 0;
            $hasPrescriptions = $visit->prescriptions()->count() > 0;

            if ($hasQueues || $hasConsultations || $hasLabRequests || $hasPrescriptions) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete visit with associated records (queues, consultations, lab requests, or prescriptions).'
                ], 400);
            }

            $visit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Visit deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Visit not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting visit: ' . $e->getMessage()
            ], 500);
        }
    }
}
