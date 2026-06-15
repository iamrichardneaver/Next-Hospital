<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $table;
    protected $operation;
    protected $deviceId;

    /**
     * Create a new job instance.
     */
    public function __construct($data, $table, $operation, $deviceId = null)
    {
        $this->data = $data;
        $this->table = $table;
        $this->operation = $operation;
        $this->deviceId = $deviceId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting sync job for table: {$this->table}, operation: {$this->operation}");

            switch ($this->operation) {
                case 'create':
                    $this->handleCreate();
                    break;
                case 'update':
                    $this->handleUpdate();
                    break;
                case 'delete':
                    $this->handleDelete();
                    break;
                default:
                    Log::warning("Unknown sync operation: {$this->operation}");
            }

            Log::info("Sync job completed successfully for table: {$this->table}");
        } catch (\Exception $e) {
            Log::error("Sync job failed for table: {$this->table}, error: " . $e->getMessage());
            throw $e;
        }
    }

    private function handleCreate()
    {
        // Add sync metadata
        $this->data['sync_status'] = 'synced';
        $this->data['sync_timestamp'] = now();
        $this->data['device_id'] = $this->deviceId;

        DB::table($this->table)->insert($this->data);
    }

    private function handleUpdate()
    {
        $id = $this->data['id'];
        unset($this->data['id']);

        // Add sync metadata
        $this->data['sync_status'] = 'synced';
        $this->data['sync_timestamp'] = now();
        $this->data['device_id'] = $this->deviceId;

        DB::table($this->table)
            ->where('id', $id)
            ->update($this->data);
    }

    private function handleDelete()
    {
        $id = $this->data['id'];
        
        DB::table($this->table)
            ->where('id', $id)
            ->delete();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Sync job failed permanently for table: {$this->table}, error: " . $exception->getMessage());
    }
}