<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SyncQueue;
use App\Models\SyncLog;
use App\Models\Device;
use App\Jobs\SyncDataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SyncController extends Controller
{
    /**
     * Sync data from client to server.
     */
    public function syncToServer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'sync_data' => 'required|array',
            'sync_data.*.table' => 'required|string',
            'sync_data.*.action' => 'required|in:create,update,delete',
            'sync_data.*.data' => 'required|array',
            'sync_data.*.timestamp' => 'required|date',
            'sync_data.*.client_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $deviceId = $request->device_id;
        $syncData = $request->sync_data;
        $processedItems = [];
        $errors = [];

        // Register or update device
        $device = $this->registerDevice($deviceId, $request);

        foreach ($syncData as $item) {
            try {
                // Dispatch sync job to queue
                SyncDataJob::dispatch($item['data'], $item['table'], $item['action'], $deviceId);
                
                $result = [
                    'client_id' => $item['client_id'],
                    'table' => $item['table'],
                    'action' => $item['action'],
                    'status' => 'queued',
                    'processed_at' => now()->toISOString()
                ];
                $processedItems[] = $result;

                // Log successful sync
                SyncLog::create([
                    'device_id' => $deviceId,
                    'table_name' => $item['table'],
                    'action' => $item['action'],
                    'client_id' => $item['client_id'],
                    'status' => 'success',
                    'processed_at' => now()
                ]);

            } catch (\Exception $e) {
                $errors[] = [
                    'client_id' => $item['client_id'],
                    'table' => $item['table'],
                    'action' => $item['action'],
                    'error' => $e->getMessage()
                ];

                // Log failed sync
                SyncLog::create([
                    'device_id' => $deviceId,
                    'table_name' => $item['table'],
                    'action' => $item['action'],
                    'client_id' => $item['client_id'],
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at' => now()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'processed_items' => $processedItems,
                'errors' => $errors,
                'total_processed' => count($processedItems),
                'total_errors' => count($errors)
            ],
            'message' => 'Sync to server completed'
        ]);
    }

    /**
     * Get data from server for client sync.
     */
    public function syncFromServer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'last_sync' => 'required|date',
            'tables' => 'nullable|array',
            'tables.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $deviceId = $request->device_id;
        $lastSync = Carbon::parse($request->last_sync);
        $tables = $request->tables ?? $this->getSyncableTables();

        $syncData = [];
        $conflicts = [];

        foreach ($tables as $table) {
            try {
                $tableData = $this->getTableData($table, $lastSync);
                $syncData[$table] = $tableData;

                // Check for conflicts
                $tableConflicts = $this->checkConflicts($table, $deviceId, $lastSync);
                if (!empty($tableConflicts)) {
                    $conflicts[$table] = $tableConflicts;
                }

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => "Error syncing table {$table}: " . $e->getMessage()
                ], 500);
            }
        }

        // Update device last sync
        $device = Device::where('device_id', $deviceId)->first();
        if ($device) {
            $device->update(['last_sync' => now()]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sync_data' => $syncData,
                'conflicts' => $conflicts,
                'sync_timestamp' => now()->toISOString(),
                'tables_synced' => count($tables)
            ],
            'message' => 'Sync from server completed'
        ]);
    }

    /**
     * Resolve sync conflicts.
     */
    public function resolveConflicts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'conflicts' => 'required|array',
            'conflicts.*.table' => 'required|string',
            'conflicts.*.record_id' => 'required|string',
            'conflicts.*.resolution' => 'required|in:server,client,merge',
            'conflicts.*.data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $deviceId = $request->device_id;
        $conflicts = $request->conflicts;
        $resolvedItems = [];
        $errors = [];

        foreach ($conflicts as $conflict) {
            try {
                $result = $this->resolveConflict($conflict, $deviceId);
                $resolvedItems[] = $result;

                // Log conflict resolution
                SyncLog::create([
                    'device_id' => $deviceId,
                    'table_name' => $conflict['table'],
                    'action' => 'conflict_resolved',
                    'client_id' => $conflict['record_id'],
                    'status' => 'success',
                    'processed_at' => now()
                ]);

            } catch (\Exception $e) {
                $errors[] = [
                    'table' => $conflict['table'],
                    'record_id' => $conflict['record_id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'resolved_items' => $resolvedItems,
                'errors' => $errors,
                'total_resolved' => count($resolvedItems),
                'total_errors' => count($errors)
            ],
            'message' => 'Conflict resolution completed'
        ]);
    }

    /**
     * Get sync status.
     */
    public function getSyncStatus(Request $request)
    {
        $deviceId = $request->get('device_id');

        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device ID is required'
            ], 400);
        }

        $device = Device::where('device_id', $deviceId)->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        $pendingItems = SyncQueue::where('device_id', $deviceId)
            ->where('status', 'pending')
            ->count();

        $failedItems = SyncQueue::where('device_id', $deviceId)
            ->where('status', 'failed')
            ->count();

        $lastSync = $device->last_sync;
        $isOnline = $device->is_online;

        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $deviceId,
                'last_sync' => $lastSync,
                'is_online' => $isOnline,
                'pending_items' => $pendingItems,
                'failed_items' => $failedItems,
                'sync_status' => $this->getSyncStatusText($pendingItems, $failedItems)
            ],
            'message' => 'Sync status retrieved successfully'
        ]);
    }

    /**
     * Process sync item.
     */
    private function processSyncItem($item, $deviceId)
    {
        $table = $item['table'];
        $action = $item['action'];
        $data = $item['data'];
        $clientId = $item['client_id'];

        switch ($action) {
            case 'create':
                return $this->createRecord($table, $data, $clientId, $deviceId);
            
            case 'update':
                return $this->updateRecord($table, $data, $clientId, $deviceId);
            
            case 'delete':
                return $this->deleteRecord($table, $data, $clientId, $deviceId);
            
            default:
                throw new \Exception("Unknown action: {$action}");
        }
    }

    /**
     * Create record.
     */
    private function createRecord($table, $data, $clientId, $deviceId)
    {
        $model = $this->getModelForTable($table);
        
        // Add sync metadata
        $data['client_id'] = $clientId;
        $data['device_id'] = $deviceId;
        $data['sync_status'] = 'synced';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $record = $model::create($data);

        return [
            'action' => 'create',
            'table' => $table,
            'client_id' => $clientId,
            'server_id' => $record->id,
            'status' => 'success'
        ];
    }

    /**
     * Update record.
     */
    private function updateRecord($table, $data, $clientId, $deviceId)
    {
        $model = $this->getModelForTable($table);
        
        // Find record by client_id or server_id
        $record = $model::where('client_id', $clientId)
            ->orWhere('id', $data['id'] ?? null)
            ->first();

        if (!$record) {
            throw new \Exception("Record not found for client_id: {$clientId}");
        }

        // Check for conflicts
        if ($this->hasConflict($record, $data)) {
            throw new \Exception("Conflict detected for record: {$clientId}");
        }

        // Update record
        $data['updated_at'] = now();
        $record->update($data);

        return [
            'action' => 'update',
            'table' => $table,
            'client_id' => $clientId,
            'server_id' => $record->id,
            'status' => 'success'
        ];
    }

    /**
     * Delete record.
     */
    private function deleteRecord($table, $data, $clientId, $deviceId)
    {
        $model = $this->getModelForTable($table);
        
        // Find record by client_id or server_id
        $record = $model::where('client_id', $clientId)
            ->orWhere('id', $data['id'] ?? null)
            ->first();

        if (!$record) {
            throw new \Exception("Record not found for client_id: {$clientId}");
        }

        $record->delete();

        return [
            'action' => 'delete',
            'table' => $table,
            'client_id' => $clientId,
            'server_id' => $record->id,
            'status' => 'success'
        ];
    }

    /**
     * Register or update device.
     */
    private function registerDevice($deviceId, $request)
    {
        $device = Device::updateOrCreate(
            ['device_id' => $deviceId],
            [
                'user_id' => auth()->id(),
                'platform' => $request->get('platform', 'web'),
                'version' => $request->get('version', '1.0.0'),
                'is_online' => true,
                'last_sync' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]
        );

        return $device;
    }

    /**
     * Get syncable tables.
     */
    private function getSyncableTables()
    {
        return [
            'patients',
            'appointments',
            'consultations',
            'lab_requests',
            'lab_results',
            'prescriptions',
            'drug_orders',
            'invoices',
            'payments',
            'emergency_visits',
            'surgery_schedules',
            'notifications'
        ];
    }

    /**
     * Get table data for sync.
     */
    private function getTableData($table, $lastSync)
    {
        $model = $this->getModelForTable($table);
        
        return $model::where('updated_at', '>', $lastSync)
            ->orWhere('created_at', '>', $lastSync)
            ->get()
            ->toArray();
    }

    /**
     * Check for conflicts.
     */
    private function checkConflicts($table, $deviceId, $lastSync)
    {
        // This is a simplified conflict detection
        // In a real implementation, you would check for:
        // 1. Records modified on both client and server since last sync
        // 2. Records deleted on one side but modified on the other
        // 3. Records with different timestamps but same data
        
        return [];
    }

    /**
     * Resolve conflict.
     */
    private function resolveConflict($conflict, $deviceId)
    {
        $table = $conflict['table'];
        $recordId = $conflict['record_id'];
        $resolution = $conflict['resolution'];
        $data = $conflict['data'] ?? [];

        $model = $this->getModelForTable($table);
        $record = $model::findOrFail($recordId);

        switch ($resolution) {
            case 'server':
                // Keep server version, do nothing
                break;
            
            case 'client':
                // Use client version
                $record->update($data);
                break;
            
            case 'merge':
                // Merge both versions (implement merge logic)
                $this->mergeRecord($record, $data);
                break;
        }

        return [
            'table' => $table,
            'record_id' => $recordId,
            'resolution' => $resolution,
            'status' => 'resolved'
        ];
    }

    /**
     * Merge record data.
     */
    private function mergeRecord($record, $clientData)
    {
        // Implement merge logic based on your business rules
        // This is a simplified example
        $serverData = $record->toArray();
        $mergedData = array_merge($serverData, $clientData);
        
        // Keep the latest timestamp
        $mergedData['updated_at'] = now();
        
        $record->update($mergedData);
    }

    /**
     * Check if record has conflict.
     */
    private function hasConflict($record, $clientData)
    {
        // Implement conflict detection logic
        // This is a simplified example
        $serverUpdatedAt = $record->updated_at;
        $clientUpdatedAt = $clientData['updated_at'] ?? null;
        
        if ($clientUpdatedAt && $serverUpdatedAt) {
            $serverTime = Carbon::parse($serverUpdatedAt);
            $clientTime = Carbon::parse($clientUpdatedAt);
            
            // If both were updated within 5 minutes, consider it a conflict
            return $serverTime->diffInMinutes($clientTime) < 5;
        }
        
        return false;
    }

    /**
     * Get model for table.
     */
    private function getModelForTable($table)
    {
        $modelMap = [
            'patients' => \App\Models\Patient::class,
            'appointments' => \App\Models\Appointment::class,
            'consultations' => \App\Models\Consultation::class,
            'lab_requests' => \App\Models\LabRequest::class,
            'lab_results' => \App\Models\LabResult::class,
            'prescriptions' => \App\Models\Prescription::class,
            'drug_orders' => \App\Models\DrugOrder::class,
            'invoices' => \App\Models\Invoice::class,
            'payments' => \App\Models\Payment::class,
            'emergency_visits' => \App\Models\EmergencyVisit::class,
            'surgery_schedules' => \App\Models\SurgerySchedule::class,
            'notifications' => \App\Models\Notification::class
        ];

        if (!isset($modelMap[$table])) {
            throw new \Exception("Unknown table: {$table}");
        }

        return $modelMap[$table];
    }

    /**
     * Get sync status text.
     */
    private function getSyncStatusText($pendingItems, $failedItems)
    {
        if ($failedItems > 0) {
            return 'error';
        } elseif ($pendingItems > 0) {
            return 'syncing';
        } else {
            return 'up_to_date';
        }
    }
}
