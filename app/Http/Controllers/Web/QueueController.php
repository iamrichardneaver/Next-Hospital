<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Http\Controllers\Concerns\WorkflowNavigation;
use App\Models\Queue;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\User;
use App\Services\LabQueueService;
use App\Services\PaymentPolicyService;
use App\Exceptions\PaymentGateException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QueueController extends Controller
{
    use ExportsListData, ResolvesUserBranch, WorkflowNavigation;

    protected PaymentPolicyService $paymentPolicyService;

    public function __construct(PaymentPolicyService $paymentPolicyService)
    {
        $this->paymentPolicyService = $paymentPolicyService;
    }

    /**
     * Display unified queue dashboard.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('view_queues')) {
            abort(403, 'Unauthorized access to queues');
        }

        $branchId = $request->get('branch_id') ?? $this->resolveUserBranchId('view_queues');
        $branches = Branch::select('id', 'name')->where('is_active', true)->get();
        $stats = $this->getAllQueueStatistics($branchId);

        return view('queues.index', compact('stats', 'branches', 'branchId'));
    }

    /**
     * Display OPD Queue.
     */
    public function opd(Request $request)
    {
        if (!auth()->user()->can('view_queues')) {
            abort(403, 'Unauthorized access to OPD queue');
        }

        $branchId = $request->get('branch_id') ?? $this->resolveUserBranchId('view_queues');
        
        $branches = Branch::select('id', 'name')->where('is_active', true)->get();
        
        // Get waiting queues
        $waitingQueues = Queue::with(['patient:id,patient_number,first_name,last_name,other_names,gender,date_of_birth,phone', 'visit:id,visit_token,priority,visit_type,patient_id,branch_id'])
            ->where('queue_type', 'OPD')
            ->where('branch_id', $branchId)
            ->where('status', 'waiting')
            ->whereHas('patient')
            ->orderByPriority()
            ->orderBy('position')
            ->get();

        $waitingQueues->each(function ($queue) {
            $queue->payment_summary = $this->paymentPolicyService->getPaymentStatusSummary(
                (int) $queue->patient_id,
                (int) $queue->branch_id,
                $queue->visit
            );
        });

        // Get currently serving
        $servingQueue = Queue::with(['patient:id,patient_number,first_name,last_name,other_names,gender,date_of_birth,phone', 'visit:id,visit_token,priority', 'servedBy:id,first_name,last_name'])
            ->where('queue_type', 'OPD')
            ->where('branch_id', $branchId)
            ->where('status', 'serving')
            ->whereHas('patient')
            ->first();

        // Get recently called
        $calledQueues = Queue::with(['patient:id,patient_number,first_name,last_name,other_names', 'calledBy:id,first_name,last_name'])
            ->where('queue_type', 'OPD')
            ->where('branch_id', $branchId)
            ->where('status', 'called')
            ->whereHas('patient')
            ->orderBy('called_at', 'desc')
            ->limit(5)
            ->get();

        // Get statistics
        $stats = $this->getQueueStatistics('OPD', $branchId);

        // Get branches for filter
        $branches = Branch::select('id', 'name')->where('is_active', true)->get();

        // Get available doctors
        $doctors = User::whereHas('roles', function($q) {
            $q->where('name', 'doctor');
        })->whereHas('staffProfile', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->select('id', 'first_name', 'last_name')
          ->get();

        return view('queues.opd', compact('waitingQueues', 'servingQueue', 'calledQueues', 'stats', 'branches', 'branchId', 'doctors'));
    }

    /**
     * Display Lab Queue.
     */
    public function lab(Request $request)
    {
        if (!auth()->user()->can('view_lab_queue') && !auth()->user()->can('view_queues')) {
            abort(403, 'Unauthorized access to Lab queue');
        }

        $branches = Branch::select('id', 'name')->where('is_active', true)->get();
        $branchId = $request->filled('branch_id')
            ? (int) $request->get('branch_id')
            : $this->resolveUserBranchId('view_lab_queue');

        // Initialize lab queue service
        $labQueueService = new LabQueueService();
        
        // Auto-create queues for pending lab requests
        $labQueueService->autoCreateQueuesForPendingRequests($branchId);
        
        // Get waiting queues with lab request details
        $waitingQueues = $labQueueService->getLabQueue($branchId, 'waiting');

        // Get currently serving
        $servingQueue = $labQueueService->getCurrentlyServing($branchId);

        // Get statistics
        $stats = $labQueueService->getLabQueueStatistics($branchId);

        // Get branches
        $branches = Branch::select('id', 'name')->where('is_active', true)->get();

        return view('queues.lab', compact('waitingQueues', 'servingQueue', 'stats', 'branches', 'branchId'));
    }

    /**
     * Display Pharmacy Queue.
     */
    public function pharmacy(Request $request)
    {
        if (!auth()->user()->can('view_queues') && !auth()->user()->can('view_pharmacy_queue')) {
            abort(403, 'Unauthorized access to Pharmacy queue');
        }

        $branchId = $request->get('branch_id', $this->resolveUserBranchId(['view_pharmacy_queue', 'manage_pharmacy_queue', 'view_queues']));
        
        // Ensure we have a valid branch ID
        if (!$branchId) {
            abort(403, 'User not assigned to any branch');
        }
        
        // Get waiting queues
        $waitingQueues = Queue::with(['patient:id,patient_number,first_name,last_name,other_names,gender,phone', 'visit:id,visit_token,priority'])
            ->where('queue_type', 'Pharmacy')
            ->where('branch_id', $branchId)
            ->where('status', 'waiting')
            ->whereHas('patient')
            ->orderByPriority()
            ->orderBy('position')
            ->get();

        // Get currently serving
        $servingQueue = Queue::with(['patient:id,patient_number,first_name,last_name,other_names', 'servedBy:id,first_name,last_name'])
            ->where('queue_type', 'Pharmacy')
            ->where('branch_id', $branchId)
            ->where('status', 'serving')
            ->whereHas('patient')
            ->first();

        // Get statistics
        $stats = $this->getQueueStatistics('Pharmacy', $branchId);

        // Get branches
        $branches = Branch::select('id', 'name')->where('is_active', true)->get();

        return view('queues.pharmacy', compact('waitingQueues', 'servingQueue', 'stats', 'branches', 'branchId'));
    }

    /**
     * Display Emergency Queue.
     */
    public function emergency(Request $request)
    {
        if (!auth()->user()->can('view_queues') && !auth()->user()->can('view_emergency_queue')) {
            abort(403, 'Unauthorized access to Emergency queue');
        }

        $branchId = $request->get('branch_id') ?? $this->resolveUserBranchId(['view_queues', 'view_emergency_queue']);
        $branches = Branch::select('id', 'name')->where('is_active', true)->get();
        
        // Get waiting queues (priority-sorted)
        $waitingQueues = Queue::with(['patient:id,patient_number,first_name,last_name,other_names,gender,date_of_birth,phone', 'visit:id,visit_token,priority'])
            ->where('queue_type', 'Emergency')
            ->where('branch_id', $branchId)
            ->where('status', 'waiting')
            ->whereHas('patient')
            ->orderByPriority()
            ->orderBy('position')
            ->get();

        // Get currently serving
        $servingQueue = Queue::with(['patient:id,patient_number,first_name,last_name,other_names', 'servedBy:id,first_name,last_name'])
            ->where('queue_type', 'Emergency')
            ->where('branch_id', $branchId)
            ->where('status', 'serving')
            ->whereHas('patient')
            ->first();

        // Get statistics
        $stats = $this->getQueueStatistics('Emergency', $branchId);

        // Get branches
        $branches = Branch::select('id', 'name')->where('is_active', true)->get();

        return view('queues.emergency', compact('waitingQueues', 'servingQueue', 'stats', 'branches', 'branchId'));
    }

    /**
     * Call next patient in queue (Web action).
     */
    public function callNext(Request $request)
    {
        $queueType = $request->input('queue_type');

        if (!$this->userCanManageQueueType($queueType)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'queue_type' => 'required|in:OPD,Lab,Pharmacy,Emergency,Radiology',
            'branch_id' => 'required|exists:branches,id'
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
            // Find next patient in queue (priority-based)
            $nextQueue = Queue::where('queue_type', $request->queue_type)
                            ->where('branch_id', $request->branch_id)
                            ->where('status', 'waiting')
                            ->orderByPriority()
                            ->orderBy('position')
                            ->first();

            if (!$nextQueue) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No patients waiting in queue'
                ], 404);
            }

            $nextQueue->load(['patient', 'visit']);

            try {
                $this->paymentPolicyService->assertCanProceedWithQueue($nextQueue);
            } catch (PaymentGateException $e) {
                DB::rollBack();
                return response()->json(array_merge([
                    'success' => false,
                ], $e->toArray()), 402);
            }

            // Update queue status
            $nextQueue->update([
                'status' => 'called',
                'called_at' => now(),
                'called_by' => auth()->id()
            ]);

            DB::commit();

            // Load relationships for response
            $nextQueue->load(['patient', 'visit']);

            // Prepare data for audio announcement
            $audioData = [
                'ticket_number' => $nextQueue->ticket_number,
                'short_ticket' => $nextQueue->short_ticket,
                'position' => $nextQueue->position,
                'patient' => [
                    'first_name' => $nextQueue->patient->first_name,
                    'last_name' => $nextQueue->patient->last_name,
                    'patient_number' => $nextQueue->patient->patient_number
                ],
                'queue_type' => $nextQueue->queue_type,
                'priority' => $nextQueue->priority
            ];

            return response()->json([
                'success' => true,
                'data' => $audioData,
                'message' => 'Patient called successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error calling patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start serving a patient.
     */
    public function startServing(Request $request, $id)
    {
        $queue = Queue::findOrFail($id);

        if (!$this->userCanManageQueueType($queue->queue_type)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!in_array($queue->status, ['called', 'waiting'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid queue status for serving'
            ], 400);
        }

        $queue->load(['patient', 'visit']);

        try {
            $this->paymentPolicyService->assertCanProceedWithQueue($queue);
        } catch (PaymentGateException $e) {
            return response()->json(array_merge([
                'success' => false,
            ], $e->toArray()), 402);
        }

        // Use LabQueueService for lab queues
        if ($queue->queue_type === 'Lab') {
            $labQueueService = new LabQueueService();
            $success = $labQueueService->startServing($queue, auth()->id());
            
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start lab service'
                ], 500);
            }
        } else {
            // Standard queue handling for other types
            $queue->update([
                'status' => 'serving',
                'serving_at' => now(),
                'served_by' => auth()->id()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $queue->load(['patient', 'visit', 'labRequests']),
            'message' => 'Patient service started'
        ]);
    }

    /**
     * Complete serving a patient.
     */
    public function completeServing(Request $request, $id)
    {
        $queue = Queue::findOrFail($id);

        if (!$this->userCanManageQueueType($queue->queue_type)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($queue->status !== 'serving') {
            return response()->json([
                'success' => false,
                'message' => 'Patient must be serving to complete'
            ], 400);
        }

        // Use LabQueueService for lab queues
        if ($queue->queue_type === 'Lab') {
            $labQueueService = new LabQueueService();
            $success = $labQueueService->completeServing($queue);
            
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to complete lab service'
                ], 500);
            }
        } else {
            // Standard queue handling for other types
            $queue->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            if ($queue->queue_type === 'OPD' && $queue->visit) {
                $this->completeWorkflowStep($queue->visit, 'queue_assignment', [
                    'queue_id' => $queue->id,
                    'completed_at' => now()->toIso8601String(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $queue->load(['patient', 'visit', 'labRequests']),
            'message' => 'Patient service completed'
        ]);
    }

    /**
     * Mark patient as no-show.
     */
    public function markNoShow(Request $request, $id)
    {
        $queue = Queue::findOrFail($id);

        if (!$this->userCanManageQueueType($queue->queue_type)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        DB::beginTransaction();

        try {

            // Use LabQueueService for lab queues
            if ($queue->queue_type === 'Lab') {
                $labQueueService = new LabQueueService();
                $success = $labQueueService->markNoShow($queue);
                
                if (!$success) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to mark lab patient as no-show'
                    ], 500);
                }
            } else {
                // Standard queue handling for other types
                $queue->update([
                    'status' => 'no_show',
                    'completed_at' => now()
                ]);
            }

            // Move other patients up in queue
            Queue::where('queue_type', $queue->queue_type)
                ->where('branch_id', $queue->branch_id)
                ->where('status', 'waiting')
                ->where('position', '>', $queue->position)
                ->decrement('position');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Patient marked as no-show'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue statistics.
     */
    public function statistics(Request $request)
    {
        $branchId = $request->get('branch_id', auth()->user()->staffProfile?->branch_id);
        $queueType = $request->get('queue_type');

        // If no branch_id is provided or user doesn't have a branch, use the first available branch
        $branches = Branch::select('id', 'name')->where('is_active', true)->get();
        if (!$branchId && $branches->count() > 0) {
            $branchId = $branches->first()->id;
        }

        // Ensure we have a valid branch ID
        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'No active branches found. Please contact administrator.'
            ], 404);
        }

        $stats = $this->getQueueStatistics($queueType, $branchId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get statistics for a specific queue type.
     */
    private function getQueueStatistics(?string $queueType, int $branchId): array
    {
        $query = Queue::where('branch_id', $branchId);
        
        if ($queueType) {
            $query->where('queue_type', $queueType);
        }

        $today = now()->toDateString();

        return [
            'total_waiting' => (clone $query)->where('status', 'waiting')->count(),
            'total_called' => (clone $query)->where('status', 'called')->count(),
            'total_serving' => (clone $query)->where('status', 'serving')->count(),
            'total_completed_today' => (clone $query)->where('status', 'completed')->whereDate('completed_at', $today)->count(),
            'total_no_show_today' => (clone $query)->where('status', 'no_show')->whereDate('completed_at', $today)->count(),
            'avg_wait_time' => $this->calculateAverageWaitTime($queueType, $branchId),
            'avg_service_time' => $this->calculateAverageServiceTime($queueType, $branchId),
            'estimated_wait_time' => $this->calculateEstimatedWaitTime($queueType, $branchId),
        ];
    }

    /**
     * Display Radiology Queue.
     */
    public function radiology(Request $request)
    {
        if (!auth()->user()->can('view_radiology_requests')
            && !auth()->user()->can('perform_radiology_studies')
            && !auth()->user()->can('complete_radiology_studies')
            && !auth()->user()->can('process_radiology_requests')
            && !auth()->user()->can('manage_radiology_setup')) {
            abort(403, 'Unauthorized access to Radiology queue');
        }

        $branchId = $request->get('branch_id')
            ?: $this->resolveUserBranchId(['view_radiology_requests', 'perform_radiology_studies', 'complete_radiology_studies', 'process_radiology_requests', 'manage_radiology_setup']);

        $branches = Branch::select('id', 'name')->where('is_active', true)->get();
        
        // Get waiting queues
        $waitingQueues = Queue::with(['patient:id,patient_number,first_name,last_name,other_names,gender,phone', 'visit:id,visit_token,priority'])
            ->where('queue_type', 'Radiology')
            ->where('branch_id', $branchId)
            ->where('status', 'waiting')
            ->whereHas('patient')
            ->orderByPriority()
            ->orderBy('position')
            ->get();

        // Get currently serving
        $servingQueue = Queue::with(['patient:id,patient_number,first_name,last_name,other_names', 'servedBy:id,first_name,last_name'])
            ->where('queue_type', 'Radiology')
            ->where('branch_id', $branchId)
            ->where('status', 'serving')
            ->whereHas('patient')
            ->first();

        // Get statistics
        $stats = $this->getQueueStatistics('Radiology', $branchId);

        return view('queues.radiology', compact('waitingQueues', 'servingQueue', 'stats', 'branches', 'branchId'));
    }

    /**
     * Get statistics for all queue types.
     */
    private function getAllQueueStatistics(int $branchId): array
    {
        return [
            'opd' => $this->getQueueStatistics('OPD', $branchId),
            'lab' => $this->getQueueStatistics('Lab', $branchId),
            'pharmacy' => $this->getQueueStatistics('Pharmacy', $branchId),
            'emergency' => $this->getQueueStatistics('Emergency', $branchId),
            'radiology' => $this->getQueueStatistics('Radiology', $branchId),
        ];
    }

    /**
     * Calculate average wait time.
     */
    private function calculateAverageWaitTime(?string $queueType, int $branchId): float
    {
        $query = Queue::where('branch_id', $branchId)
                     ->whereNotNull('called_at')
                     ->whereNotNull('queued_at')
                     ->whereDate('queued_at', now()->toDateString());

        if ($queueType) {
            $query->where('queue_type', $queueType);
        }

        return round($query->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, queued_at, called_at)) as avg_wait')
                    ->value('avg_wait') ?? 0);
    }

    /**
     * Calculate average service time.
     */
    private function calculateAverageServiceTime(?string $queueType, int $branchId): float
    {
        $query = Queue::where('branch_id', $branchId)
                     ->where('status', 'completed')
                     ->whereNotNull('serving_at')
                     ->whereNotNull('completed_at')
                     ->whereDate('completed_at', now()->toDateString());

        if ($queueType) {
            $query->where('queue_type', $queueType);
        }

        return round($query->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, serving_at, completed_at)) as avg_service')
                    ->value('avg_service') ?? 0);
    }

    /**
     * Calculate estimated wait time for new patients.
     */
    private function calculateEstimatedWaitTime(?string $queueType, int $branchId): int
    {
        $avgServiceTime = $this->calculateAverageServiceTime($queueType, $branchId) ?: 15;
        
        $query = Queue::where('branch_id', $branchId)
                     ->where('status', 'waiting');
        
        if ($queueType) {
            $query->where('queue_type', $queueType);
        }
        
        $patientsAhead = $query->count();

        return $patientsAhead * $avgServiceTime;
    }

    /**
     * Print queue ticket (thermal printer format).
     */
    public function printTicket($id)
    {
        if (!auth()->user()->can('view_queues')) {
            abort(403, 'Unauthorized');
        }

        $queue = Queue::with(['patient', 'visit', 'branch'])
            ->findOrFail($id);

        return view('queues.ticket', compact('queue'));
    }

    /**
     * Reprint ticket for existing queue entry.
     */
    public function reprintTicket($id)
    {
        if (!auth()->user()->can('view_queues')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $queue = Queue::findOrFail($id);

        return response()->json([
            'success' => true,
            'print_url' => route('queues.print-ticket', $id),
            'ticket_number' => $queue->ticket_number,
            'short_ticket' => $queue->short_ticket
        ]);
    }

    /**
     * Get lab details for a queue entry.
     */
    public function labDetails(Request $request, $id)
    {
        if (!auth()->user()->can('view_lab_queue')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to lab queue details'
            ], 403);
        }

        $queue = Queue::findOrFail($id);
        
        // Load relationships with proper filtering
        $queue->load([
            'patient:id,patient_number,first_name,last_name,other_names,gender,phone,date_of_birth',
            'labRequests' => function($query) use ($queue) {
                $query->select('id', 'patient_id', 'branch_id', 'test_type', 'test_type_name', 'test_category_name', 'test_description', 'specimen_type', 'priority', 'status', 'technician_id', 'collected_at', 'completed_at', 'lab_request_number')
                      ->where('branch_id', $queue->branch_id)
                      ->whereIn('status', ['pending', 'in_progress', 'completed'])
                      ->with(['technician:id,first_name,last_name'])
                      ->orderBy('created_at', 'desc');
            }
        ]);

        if ($queue->queue_type !== 'Lab') {
            return response()->json([
                'success' => false,
                'message' => 'This is not a lab queue entry'
            ], 400);
        }

        $html = view('queues.partials.lab-details', compact('queue'))->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }

    /**
     * Whether the current user can manage actions for a queue type.
     */
    private function userCanManageQueueType(?string $queueType): bool
    {
        $user = auth()->user();

        return match ($queueType) {
            'Lab' => $user->can('manage_lab_queue') || $user->can('manage_queues'),
            'Pharmacy' => $user->can('manage_pharmacy_queue') || $user->can('manage_queues'),
            'OPD' => $user->can('manage_opd_queue') || $user->can('manage_queues'),
            'Emergency' => $user->can('manage_emergency_queue') || $user->can('manage_queues'),
            'Radiology' => $user->can('manage_queues')
                || $user->can('perform_radiology_studies')
                || $user->can('complete_radiology_studies'),
            default => $user->can('manage_queues'),
        };
    }

    public function export(Request $request)
    {
        if (! auth()->user()->can('view_queues')) {
            abort(403, 'Unauthorized access to queues');
        }

        $branchId = $request->get('branch_id') ?? $this->resolveUserBranchId('view_queues');

        $query = Queue::with(['patient', 'visit'])
            ->where('branch_id', $branchId)
            ->whereHas('patient')
            ->orderBy('queue_type')
            ->orderBy('position');

        if ($request->filled('queue_type')) {
            $query->where('queue_type', $request->queue_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->exportFromQuery($request, $query, [
            'Queue Type' => 'queue_type',
            'Position' => 'position',
            'Patient' => fn ($q) => $q->patient?->full_name ?? '',
            'Patient Number' => fn ($q) => $q->patient?->patient_number ?? '',
            'Visit Token' => fn ($q) => $q->visit?->visit_token ?? '',
            'Status' => 'status',
            'Priority' => 'priority',
            'Queued At' => fn ($q) => $this->formatExportDate($q->queued_at, 'Y-m-d H:i'),
        ], 'queues', 'view_queues');
    }
}

