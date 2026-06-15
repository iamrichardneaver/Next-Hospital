<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LabQueueService;
use App\Models\Queue;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for LabQueueService
 * 
 * Tests the lab queue management service that handles
 * automatic queue creation for lab requests
 */
class LabQueueServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LabQueueService $service;
    protected Branch $branch;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new LabQueueService();
        
        // Create test branch
        $this->branch = Branch::factory()->create([
            'name' => 'Test Branch',
            'code' => 'TB01',
        ]);
        
        // Create test patient
        $this->patient = Patient::factory()->create([
            'branch_id' => $this->branch->id,
        ]);
    }

    /**
     * Test auto-creates queue for pending lab request
     */
    public function test_auto_creates_queue_for_pending_lab_request()
    {
        // Create a lab request without a queue
        $labRequest = LabRequest::factory()->create([
            'patient_id' => $this->patient->id,
            'branch_id' => $this->branch->id,
            'status' => 'pending',
        ]);

        // Run auto-create function
        $this->service->autoCreateQueuesForPendingRequests($this->branch->id);

        // Check if queue was created
        $queue = Queue::where('patient_id', $this->patient->id)
            ->where('queue_type', 'Lab')
            ->where('branch_id', $this->branch->id)
            ->first();

        $this->assertNotNull($queue);
        $this->assertEquals('waiting', $queue->status);
    }

    /**
     * Test doesn't create duplicate queues
     */
    public function test_does_not_create_duplicate_queues()
    {
        $labRequest = LabRequest::factory()->create([
            'patient_id' => $this->patient->id,
            'branch_id' => $this->branch->id,
            'status' => 'pending',
        ]);

        // First run
        $this->service->autoCreateQueuesForPendingRequests($this->branch->id);
        
        // Second run
        $this->service->autoCreateQueuesForPendingRequests($this->branch->id);

        // Should only have one queue
        $queueCount = Queue::where('patient_id', $this->patient->id)
            ->where('queue_type', 'Lab')
            ->count();

        $this->assertEquals(1, $queueCount);
    }

    /**
     * Test gets lab queue with correct status
     */
    public function test_gets_lab_queue_with_status()
    {
        // Create multiple queues with different statuses
        Queue::factory()->create([
            'patient_id' => $this->patient->id,
            'branch_id' => $this->branch->id,
            'queue_type' => 'Lab',
            'status' => 'waiting',
        ]);

        Queue::factory()->create([
            'patient_id' => $this->patient->id,
            'branch_id' => $this->branch->id,
            'queue_type' => 'Lab',
            'status' => 'serving',
        ]);

        // Get waiting queues
        $waitingQueues = $this->service->getLabQueue($this->branch->id, 'waiting');

        $this->assertCount(1, $waitingQueues);
        $this->assertEquals('waiting', $waitingQueues->first()->status);
    }

    /**
     * Test calculates queue statistics correctly
     */
    public function test_calculates_queue_statistics()
    {
        // Create queues with different statuses
        Queue::factory()->count(5)->create([
            'branch_id' => $this->branch->id,
            'queue_type' => 'Lab',
            'status' => 'waiting',
        ]);

        Queue::factory()->count(2)->create([
            'branch_id' => $this->branch->id,
            'queue_type' => 'Lab',
            'status' => 'serving',
        ]);

        Queue::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'queue_type' => 'Lab',
            'status' => 'completed',
        ]);

        $stats = $this->service->getLabQueueStatistics($this->branch->id);

        $this->assertEquals(5, $stats['waiting']);
        $this->assertEquals(2, $stats['serving']);
        $this->assertEquals(3, $stats['completed_today']);
    }

    /**
     * Test gets currently serving queue
     */
    public function test_gets_currently_serving_queue()
    {
        $servingQueue = Queue::factory()->create([
            'patient_id' => $this->patient->id,
            'branch_id' => $this->branch->id,
            'queue_type' => 'Lab',
            'status' => 'serving',
        ]);

        $result = $this->service->getCurrentlyServing($this->branch->id);

        $this->assertNotNull($result);
        $this->assertEquals($servingQueue->id, $result->id);
        $this->assertEquals('serving', $result->status);
    }

    /**
     * Test respects priority ordering in queue
     */
    public function test_respects_priority_ordering()
    {
        // Create queues with different priorities
        $routineQueue = Queue::factory()->create([
            'branch_id' => $this->branch->id,
            'queue_type' => 'Lab',
            'status' => 'waiting',
            'priority' => 'routine',
            'position' => 1,
        ]);

        $urgentQueue = Queue::factory()->create([
            'branch_id' => $this->branch->id,
            'queue_type' => 'Lab',
            'status' => 'waiting',
            'priority' => 'urgent',
            'position' => 2,
        ]);

        $criticalQueue = Queue::factory()->create([
            'branch_id' => $this->branch->id,
            'queue_type' => 'Lab',
            'status' => 'waiting',
            'priority' => 'critical',
            'position' => 3,
        ]);

        $queues = $this->service->getLabQueue($this->branch->id, 'waiting');

        // Critical should be first, then urgent, then routine
        $this->assertEquals('critical', $queues->first()->priority);
        $this->assertEquals('urgent', $queues->skip(1)->first()->priority);
        $this->assertEquals('routine', $queues->last()->priority);
    }
}
