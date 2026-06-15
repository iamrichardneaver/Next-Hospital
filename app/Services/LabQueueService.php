<?php

namespace App\Services;

use App\Models\Queue;
use App\Models\LabRequest;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LabQueueService
{
    /**
     * Create a queue entry for a lab request
     */
    public function createQueueForLabRequest(LabRequest $labRequest): ?Queue
    {
        try {
            // Check if queue already exists for this lab request
            $existingQueue = Queue::where('patient_id', $labRequest->patient_id)
                ->where('queue_type', 'Lab')
                ->where('branch_id', $labRequest->branch_id)
                ->where('status', 'waiting')
                ->first();

            if ($existingQueue) {
                return $existingQueue;
            }

            // Get the visit for this lab request through consultation
            $visit = null;
            
            // Load consultation relationship if not already loaded
            if (!$labRequest->relationLoaded('consultation')) {
                $labRequest->load('consultation');
            }
            
            if ($labRequest->consultation_id && $labRequest->consultation) {
                // Load visit relationship if not already loaded
                if (!$labRequest->consultation->relationLoaded('visit')) {
                    $labRequest->consultation->load('visit');
                }
                
                if ($labRequest->consultation->visit) {
                    $visit = $labRequest->consultation->visit;
                }
            }
            
            // Fallback: try to find visit by patient_id and branch_id
            if (!$visit) {
                $visit = Visit::where('patient_id', $labRequest->patient_id)
                    ->where('branch_id', $labRequest->branch_id)
                    ->where('status', 'active')
                    ->latest()
                    ->first();
            }
            
            if (!$visit) {
                \Log::warning('No visit found for lab request', [
                    'lab_request_id' => $labRequest->id, 
                    'patient_id' => $labRequest->patient_id,
                    'consultation_id' => $labRequest->consultation_id
                ]);
                return null;
            }

            // Get the next position in the lab queue
            $lastPosition = Queue::where('queue_type', 'Lab')
                ->where('branch_id', $labRequest->branch_id)
                ->where('status', 'waiting')
                ->max('position') ?? 0;

            // Generate ticket number
            $ticketNumber = $this->generateTicketNumber($labRequest->branch_id);

            // Determine priority based on lab request priority
            $priority = $this->mapLabPriorityToQueuePriority($labRequest->priority);

            // Create the queue entry
            $queue = Queue::create([
                'visit_id' => $visit->id,
                'patient_id' => $labRequest->patient_id,
                'branch_id' => $labRequest->branch_id,
                'queue_type' => 'Lab',
                'ticket_number' => $ticketNumber,
                'position' => $lastPosition + 1,
                'status' => 'waiting',
                'priority' => $priority,
                'queued_at' => now(),
                'estimated_wait_time' => $this->calculateEstimatedWaitTime($labRequest->branch_id),
                'notes' => "Lab Request: {$labRequest->test_type} - {$labRequest->test_description}",
            ]);

            return $queue;

        } catch (\Exception $e) {
            \Log::error('Failed to create lab queue: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get lab queue with detailed information
     */
    public function getLabQueue(int $branchId, string $status = 'waiting')
    {
        return Queue::with([
                'patient:id,patient_number,first_name,last_name,other_names,gender,phone,date_of_birth',
                'visit:id,visit_token,priority',
                'servedBy:id,first_name,last_name',
                'labRequests' => function($query) use ($branchId) {
                    $query->select('id', 'patient_id', 'branch_id', 'test_type', 'test_type_name', 'test_category_name', 'test_description', 'specimen_type', 'priority', 'status', 'technician_id', 'collected_at', 'completed_at', 'lab_request_number')
                          ->where('branch_id', $branchId)
                          ->whereIn('status', ['pending', 'in_progress'])
                          ->with(['technician:id,first_name,last_name']);
                }
            ])
            ->where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->where('status', $status)
            ->whereHas('patient')
            ->orderByPriority()
            ->orderBy('position')
            ->get();
    }

    /**
     * Get currently serving lab queue
     */
    public function getCurrentlyServing(int $branchId)
    {
        return Queue::with([
                'patient:id,patient_number,first_name,last_name,other_names,gender,phone',
                'servedBy:id,first_name,last_name',
                'labRequests' => function($query) use ($branchId) {
                    $query->select('id', 'patient_id', 'branch_id', 'test_type', 'test_type_name', 'test_category_name', 'test_description', 'specimen_type', 'priority', 'status', 'technician_id', 'collected_at', 'completed_at', 'lab_request_number')
                          ->where('branch_id', $branchId)
                          ->whereIn('status', ['pending', 'in_progress', 'completed'])
                          ->with([
                              'technician:id,first_name,last_name',
                              'results' => function($resultsQuery) {
                                  $resultsQuery->select('id', 'lab_request_id', 'result_entered_at', 'result_verified_at', 'result_approved_at', 'verified_by', 'approved_by')
                                               ->orderBy('id', 'asc');
                              }
                          ]);
                }
            ])
            ->where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->where('status', 'serving')
            ->whereHas('patient')
            ->first();
    }

    /**
     * Start serving a lab queue entry
     */
    public function startServing(Queue $queue, int $technicianId): bool
    {
        try {
            DB::beginTransaction();

            // Update queue status
            $queue->update([
                'status' => 'serving',
                'serving_at' => now(),
                'served_by' => $technicianId,
            ]);

            // Update lab requests to in_progress
            LabRequest::where('patient_id', $queue->patient_id)
                ->where('branch_id', $queue->branch_id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'in_progress',
                    'technician_id' => $technicianId,
                    'collected_at' => now(),
                ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to start serving lab queue: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if all lab results for a queue are entered, verified, and approved
     */
    public function areAllResultsReady(Queue $queue): array
    {
        if (!$queue->labRequests || $queue->labRequests->isEmpty()) {
            return [
                'ready' => false,
                'message' => 'No lab requests found',
                'needs_entry' => true,
                'needs_verification' => false,
                'needs_approval' => false,
                'first_lab_request_id' => null
            ];
        }

        $needsEntry = [];
        $needsVerification = [];
        $needsApproval = [];

        foreach ($queue->labRequests as $labRequest) {
            // Load results if not already loaded
            if (!$labRequest->relationLoaded('results')) {
                $labRequest->load('results');
            }

            // If no results exist, needs entry
            if ($labRequest->results->isEmpty()) {
                $needsEntry[] = $labRequest;
                continue;
            }

            // Check each result
            foreach ($labRequest->results as $result) {
                // Check if entered
                if (!$result->result_entered_at) {
                    $needsEntry[] = $labRequest;
                    break; // Move to next lab request
                }

                // Check if verified
                if (!$result->result_verified_at || !$result->verified_by) {
                    $needsVerification[] = $labRequest;
                    break; // Move to next lab request
                }

                // Check if approved
                if (!$result->result_approved_at || !$result->approved_by) {
                    $needsApproval[] = $labRequest;
                    break; // Move to next lab request
                }
            }
        }

        $isReady = empty($needsEntry) && empty($needsVerification) && empty($needsApproval);

        return [
            'ready' => $isReady,
            'message' => $isReady 
                ? 'All results are entered, verified, and approved' 
                : ($needsEntry ? 'Results need to be entered' : ($needsVerification ? 'Results need verification' : 'Results need approval')),
            'needs_entry' => !empty($needsEntry),
            'needs_verification' => !empty($needsVerification),
            'needs_approval' => !empty($needsApproval),
            'first_lab_request_id' => !empty($needsEntry) 
                ? $needsEntry[0]->id 
                : (!empty($needsVerification) ? $needsVerification[0]->id : (!empty($needsApproval) ? $needsApproval[0]->id : null))
        ];
    }

    /**
     * Complete serving a lab queue entry
     */
    public function completeServing(Queue $queue): bool
    {
        try {
            DB::beginTransaction();

            // Update queue status
            $queue->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Update lab requests to completed
            LabRequest::where('patient_id', $queue->patient_id)
                ->where('branch_id', $queue->branch_id)
                ->where('status', 'in_progress')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to complete serving lab queue: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark lab queue as no-show
     */
    public function markNoShow(Queue $queue): bool
    {
        try {
            DB::beginTransaction();

            // Update queue status
            $queue->update([
                'status' => 'no_show',
                'completed_at' => now(),
            ]);

            // Update lab requests to cancelled
            LabRequest::where('patient_id', $queue->patient_id)
                ->where('branch_id', $queue->branch_id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'completed_at' => now(),
                ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to mark lab queue as no-show: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get lab queue statistics
     */
    public function getLabQueueStatistics(int $branchId): array
    {
        $today = now()->toDateString();

        $totalWaiting = Queue::where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->where('status', 'waiting')
            ->count();

        $totalServing = Queue::where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->where('status', 'serving')
            ->count();

        $totalCompletedToday = Queue::where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->count();

        $totalNoShowToday = Queue::where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->where('status', 'no_show')
            ->whereDate('completed_at', $today)
            ->count();

        $avgWaitTime = $this->calculateAverageWaitTime($branchId);
        $avgServiceTime = $this->calculateAverageServiceTime($branchId);

        return [
            'total_waiting' => $totalWaiting,
            'total_serving' => $totalServing,
            'total_completed_today' => $totalCompletedToday,
            'total_no_show_today' => $totalNoShowToday,
            'avg_wait_time' => $avgWaitTime,
            'avg_service_time' => $avgServiceTime,
            'estimated_wait_time' => $this->calculateEstimatedWaitTime($branchId),
        ];
    }

    /**
     * Generate ticket number for lab queue
     */
    private function generateTicketNumber(int $branchId): string
    {
        $branch = Branch::find($branchId);
        $branchCode = $branch ? strtoupper(substr($branch->name, 0, 3)) : 'LAB';
        $date = now()->format('Ymd');
        $sequence = str_pad(Queue::where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->count() + 1, 4, '0', STR_PAD_LEFT);

        return "{$branchCode}L{$date}{$sequence}";
    }

    /**
     * Map lab request priority to queue priority
     */
    private function mapLabPriorityToQueuePriority(string $labPriority): string
    {
        return match($labPriority) {
            'stat' => 'critical',
            'urgent' => 'urgent',
            'routine' => 'routine',
            default => 'routine'
        };
    }

    /**
     * Calculate estimated wait time
     */
    private function calculateEstimatedWaitTime(int $branchId): int
    {
        $avgServiceTime = $this->calculateAverageServiceTime($branchId);
        $waitingCount = Queue::where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->where('status', 'waiting')
            ->count();

        return $avgServiceTime * $waitingCount;
    }

    /**
     * Calculate average wait time
     */
    private function calculateAverageWaitTime(int $branchId): float
    {
        $completedQueues = Queue::where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereNotNull('serving_at')
            ->whereNotNull('completed_at')
            ->get();

        if ($completedQueues->isEmpty()) {
            return 0;
        }

        $totalWaitTime = $completedQueues->sum(function($queue) {
            return $queue->serving_at->diffInMinutes($queue->queued_at);
        });

        return round($totalWaitTime / $completedQueues->count(), 1);
    }

    /**
     * Calculate average service time
     */
    private function calculateAverageServiceTime(int $branchId): float
    {
        $completedQueues = Queue::where('queue_type', 'Lab')
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereNotNull('serving_at')
            ->whereNotNull('completed_at')
            ->get();

        if ($completedQueues->isEmpty()) {
            return 15; // Default 15 minutes
        }

        $totalServiceTime = $completedQueues->sum(function($queue) {
            return $queue->completed_at->diffInMinutes($queue->serving_at);
        });

        return round($totalServiceTime / $completedQueues->count(), 1);
    }

    /**
     * Auto-create queues for pending lab requests
     */
    public function autoCreateQueuesForPendingRequests(int $branchId): int
    {
        $pendingLabRequests = LabRequest::where('branch_id', $branchId)
            ->where('status', 'pending')
            ->whereDoesntHave('queues', function($query) {
                $query->where('queue_type', 'Lab')
                      ->whereIn('status', ['waiting', 'serving']);
            })
            ->get();

        $createdCount = 0;
        foreach ($pendingLabRequests as $labRequest) {
            if ($this->createQueueForLabRequest($labRequest)) {
                $createdCount++;
            }
        }

        return $createdCount;
    }
}
