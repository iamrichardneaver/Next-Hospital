<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\Queue;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Branch;
use App\Services\PaymentPolicyService;
use App\Exceptions\PaymentGateException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QueueController extends Controller
{
    use ResolvesUserBranch;

    protected PaymentPolicyService $paymentPolicyService;

    public function __construct(PaymentPolicyService $paymentPolicyService)
    {
        $this->paymentPolicyService = $paymentPolicyService;
    }

    /**
     * Display a listing of queues.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Queue::with(['patient', 'visit', 'branch', 'calledBy', 'servedBy']);

        // Role-based data filtering
        if ($user->hasRole('patient')) {
            // Patients can only see their own queue entries
            $query->where('patient_id', $user->id);
        } elseif ($user->hasRole(['doctor', 'nurse', 'pharmacist', 'receptionist', 'lab_technician'])) {
            // Medical staff can see queues from their branch
            if ($user->staffProfile && $user->staffProfile->branch_id) {
                $query->where('branch_id', $user->staffProfile->branch_id);
            }
        }
        // Super admin and other roles can see all queues

        // Filter by queue type
        if ($request->has('queue_type')) {
            $query->where('queue_type', $request->queue_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by branch (only for non-patient roles)
        if (!$user->hasRole('patient') && $request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Search by patient name
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('patient', function($patientQuery) use ($search) {
                $patientQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_number', 'like', "%{$search}%");
            });
        }

        $queues = $query->orderBy('position')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $queues,
            'message' => 'Queues retrieved successfully'
        ]);
    }

    /**
     * Get current queue status for a specific queue type.
     */
    public function getQueueStatus(Request $request, string $queueType): JsonResponse
    {
        $branchId = $request->get('branch_id') ?? $this->resolveUserBranchId('view_queues');

        $queues = Queue::with(['patient', 'visit'])
                      ->where('queue_type', $queueType)
                      ->where('branch_id', $branchId)
                      ->whereIn('status', ['waiting', 'called', 'serving'])
                      ->orderBy('position')
                      ->get();

        if ($queueType === 'OPD') {
            $queues->each(function ($queue) {
                $queue->payment_summary = $this->paymentPolicyService->getPaymentStatusSummary(
                    (int) $queue->patient_id,
                    (int) $queue->branch_id,
                    $queue->visit
                );
            });
        }

        $stats = [
            'total_waiting' => $queues->where('status', 'waiting')->count(),
            'total_called' => $queues->where('status', 'called')->count(),
            'total_serving' => $queues->where('status', 'serving')->count(),
            'current_serving' => $queues->where('status', 'serving')->first(),
            'next_in_line' => $queues->where('status', 'waiting')->first(),
            'average_wait_time' => $this->calculateAverageWaitTime($queueType, $branchId),
            'estimated_wait_time' => $this->calculateEstimatedWaitTime($queueType, $branchId)
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'queues' => $queues,
                'stats' => $stats
            ],
            'message' => 'Queue status retrieved successfully'
        ]);
    }

    /**
     * Call the next patient in queue.
     */
    public function callNext(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'queue_type' => 'required|in:OPD,Lab,Pharmacy,Emergency,Radiology',
            'branch_id' => 'required|exists:branches,id',
            'called_by' => 'required|exists:users,id'
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
            // Find next patient in queue
            $nextQueue = Queue::where('queue_type', $request->queue_type)
                            ->where('branch_id', $request->branch_id)
                            ->where('status', 'waiting')
                            ->orderByPriority()
                            ->orderBy('position')
                            ->first();

            if (!$nextQueue) {
                return response()->json([
                    'success' => false,
                    'message' => 'No patients waiting in queue'
                ], 404);
            }

            $nextQueue->load(['patient', 'visit']);

            try {
                $this->paymentPolicyService->assertCanProceedWithQueue($nextQueue);
            } catch (PaymentGateException $e) {
                return response()->json(array_merge([
                    'success' => false,
                ], $e->toArray()), 402);
            }

            // Update queue status
            $nextQueue->update([
                'status' => 'called',
                'called_at' => now(),
                'called_by' => $request->called_by
            ]);

            // Update all other queues to move them up
            Queue::where('queue_type', $request->queue_type)
                ->where('branch_id', $request->branch_id)
                ->where('status', 'waiting')
                ->where('position', '>', $nextQueue->position)
                ->decrement('position');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $nextQueue->load(['patient', 'visit']),
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
    public function startServing(Request $request, $id): JsonResponse
    {
        $queue = Queue::findOrFail($id);

        if ($queue->status !== 'called') {
            return response()->json([
                'success' => false,
                'message' => 'Patient must be called before serving'
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

        $queue->update([
            'status' => 'serving',
            'serving_at' => now(),
            'served_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $queue->load(['patient', 'visit']),
            'message' => 'Patient service started'
        ]);
    }

    /**
     * Complete serving a patient.
     */
    public function completeServing(Request $request, $id): JsonResponse
    {
        $queue = Queue::findOrFail($id);

        if ($queue->status !== 'serving') {
            return response()->json([
                'success' => false,
                'message' => 'Patient must be serving to complete'
            ], 400);
        }

        $queue->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $queue->load(['patient', 'visit']),
            'message' => 'Patient service completed'
        ]);
    }

    /**
     * Mark patient as no-show.
     */
    public function markNoShow(Request $request, $id): JsonResponse
    {
        $queue = Queue::findOrFail($id);

        if ($queue->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot mark completed patient as no-show'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Mark as no-show
            $queue->update([
                'status' => 'no_show',
                'completed_at' => now()
            ]);

            // Move other patients up in queue
            Queue::where('queue_type', $queue->queue_type)
                ->where('branch_id', $queue->branch_id)
                ->where('status', 'waiting')
                ->where('position', '>', $queue->position)
                ->decrement('position');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $queue->load(['patient', 'visit']),
                'message' => 'Patient marked as no-show'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error marking patient as no-show: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a queue entry.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $queue = Queue::findOrFail($id);

        if ($queue->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed queue entry'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Cancel the queue entry
            $queue->update([
                'status' => 'cancelled',
                'completed_at' => now()
            ]);

            // Move other patients up in queue
            Queue::where('queue_type', $queue->queue_type)
                ->where('branch_id', $queue->branch_id)
                ->where('status', 'waiting')
                ->where('position', '>', $queue->position)
                ->decrement('position');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $queue->load(['patient', 'visit']),
                'message' => 'Queue entry cancelled'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling queue entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder queue positions.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'queue_type' => 'required|in:OPD,Lab,Pharmacy,Emergency,Radiology',
            'branch_id' => 'required|exists:branches,id',
            'queue_ids' => 'required|array',
            'queue_ids.*' => 'exists:queues,id'
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
            $queueIds = $request->queue_ids;
            
            foreach ($queueIds as $index => $queueId) {
                Queue::where('id', $queueId)
                    ->where('queue_type', $request->queue_type)
                    ->where('branch_id', $request->branch_id)
                    ->update(['position' => $index + 1]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Queue reordered successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error reordering queue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $branchId = $request->get('branch_id') ?? $this->resolveUserBranchId('view_queues');
        $queueType = $request->get('queue_type');

        $query = Queue::where('branch_id', $branchId);
        
        if ($queueType) {
            $query->where('queue_type', $queueType);
        }

        $stats = [
            'total_entries' => $query->count(),
            'waiting' => $query->where('status', 'waiting')->count(),
            'called' => $query->where('status', 'called')->count(),
            'serving' => $query->where('status', 'serving')->count(),
            'completed' => $query->where('status', 'completed')->count(),
            'cancelled' => $query->where('status', 'cancelled')->count(),
            'no_show' => $query->where('status', 'no_show')->count(),
            'average_wait_time' => $this->calculateAverageWaitTime($queueType, $branchId),
            'average_service_time' => $this->calculateAverageServiceTime($queueType, $branchId)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Queue statistics retrieved successfully'
        ]);
    }

    /**
     * Calculate average wait time.
     */
    private function calculateAverageWaitTime(?string $queueType, int $branchId): float
    {
        $query = Queue::where('branch_id', $branchId)
                     ->whereNotNull('called_at')
                     ->whereNotNull('queued_at');

        if ($queueType) {
            $query->where('queue_type', $queueType);
        }

        return $query->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, queued_at, called_at)) as avg_wait')
                    ->value('avg_wait') ?? 0;
    }

    /**
     * Calculate average service time.
     */
    private function calculateAverageServiceTime(?string $queueType, int $branchId): float
    {
        $query = Queue::where('branch_id', $branchId)
                     ->where('status', 'completed')
                     ->whereNotNull('serving_at')
                     ->whereNotNull('completed_at');

        if ($queueType) {
            $query->where('queue_type', $queueType);
        }

        return $query->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, serving_at, completed_at)) as avg_service')
                    ->value('avg_service') ?? 0;
    }

    /**
     * Calculate estimated wait time for new patients.
     */
    private function calculateEstimatedWaitTime(string $queueType, int $branchId): int
    {
        $avgServiceTime = $this->calculateAverageServiceTime($queueType, $branchId) ?: 15;
        $patientsAhead = Queue::where('queue_type', $queueType)
                             ->where('branch_id', $branchId)
                             ->where('status', 'waiting')
                             ->count();

        return $patientsAhead * $avgServiceTime;
    }

    /**
     * Get OPD queue status and statistics.
     */
    public function getOPDQueueStatus(Request $request): JsonResponse
    {
        return $this->getQueueStatus($request, 'OPD');
    }

    /**
     * Call next OPD patient.
     */
    public function callNextOPDPatient(Request $request): JsonResponse
    {
        $request->merge(['queue_type' => 'OPD']);
        return $this->callNext($request);
    }

    /**
     * Start serving OPD patient.
     */
    public function startOPDServing(Request $request, $queueId): JsonResponse
    {
        return $this->startServing($request, $queueId);
    }

    /**
     * Complete OPD serving.
     */
    public function completeOPDServing(Request $request, $queueId): JsonResponse
    {
        return $this->completeServing($request, $queueId);
    }

    /**
     * Get lab details for a queue entry.
     */
    public function labDetails(Request $request, $id): JsonResponse
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

        return response()->json([
            'success' => true,
            'data' => [
                'queue' => $queue,
                'lab_requests' => $queue->labRequests
            ],
            'message' => 'Lab queue details retrieved successfully'
        ]);
    }

    /**
     * Reprint ticket for existing queue entry.
     */
    public function reprintTicket($id): JsonResponse
    {
        if (!auth()->user()->can('view_queues')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $queue = Queue::with(['patient', 'visit', 'branch'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'queue_id' => $queue->id,
                'ticket_number' => $queue->ticket_number,
                'short_ticket' => $queue->short_ticket,
                'queue_type' => $queue->queue_type,
                'patient' => [
                    'id' => $queue->patient->id ?? null,
                    'name' => $queue->patient ? ($queue->patient->first_name . ' ' . $queue->patient->last_name) : null,
                    'patient_number' => $queue->patient->patient_number ?? null,
                ],
                'visit' => [
                    'id' => $queue->visit->id ?? null,
                    'visit_token' => $queue->visit->visit_token ?? null,
                ],
                'branch' => [
                    'id' => $queue->branch->id ?? null,
                    'name' => $queue->branch->name ?? null,
                ]
            ],
            'message' => 'Ticket information retrieved successfully'
        ]);
    }

    /**
     * Get print ticket data for a queue entry.
     */
    public function printTicket($id): JsonResponse
    {
        if (!auth()->user()->can('view_queues')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $queue = Queue::with(['patient', 'visit', 'branch'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'queue_id' => $queue->id,
                'ticket_number' => $queue->ticket_number,
                'short_ticket' => $queue->short_ticket,
                'position' => $queue->position,
                'queue_type' => $queue->queue_type,
                'status' => $queue->status,
                'created_at' => $queue->created_at,
                'patient' => [
                    'id' => $queue->patient->id ?? null,
                    'name' => $queue->patient ? trim($queue->patient->first_name . ' ' . $queue->patient->last_name) : null,
                    'patient_number' => $queue->patient->patient_number ?? null,
                    'gender' => $queue->patient->gender ?? null,
                    'date_of_birth' => $queue->patient->date_of_birth ?? null,
                ],
                'visit' => [
                    'id' => $queue->visit->id ?? null,
                    'visit_token' => $queue->visit->visit_token ?? null,
                ],
                'branch' => [
                    'id' => $queue->branch->id ?? null,
                    'name' => $queue->branch->name ?? null,
                ]
            ],
            'message' => 'Print ticket data retrieved successfully'
        ]);
    }
}
