<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\RealTimeDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RealTimeDataController extends Controller
{
    protected $realTimeDataService;

    public function __construct(RealTimeDataService $realTimeDataService)
    {
        $this->realTimeDataService = $realTimeDataService;
    }

    /**
     * Get real-time data for a specific module
     */
    public function getModuleData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'module' => 'required|string|in:patients,appointments,queue,lab_results,prescriptions,billing,emergency_alerts,wards,pharmacy',
            'filters' => 'sometimes|array',
            'last_check' => 'sometimes|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('web')->user();
            
            // Debug logging
            \Log::info('RealTimeDataController Debug', [
                'sanctum_user' => Auth::guard('sanctum')->user() ? Auth::guard('sanctum')->user()->id : null,
                'web_user' => Auth::guard('web')->user() ? Auth::guard('web')->user()->id : null,
                'session_id' => session()->getId(),
                'session_data' => session()->all(),
                'request_headers' => request()->headers->all()
            ]);
            
            if (!$user) {
                return response()->json([
                    'success' => true,
                    'has_changes' => false,
                    'data' => [],
                    'message' => 'User not authenticated - returning empty data',
                    'timestamp' => now()->toISOString(),
                    'module' => $request->input('module')
                ]);
            }
            
            $branchId = $user->facilityUsers()->where('is_active', true)->first()?->branch_id;
            
            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active branch found'
                ], 400);
            }

            $module = $request->input('module');
            $filters = $request->input('filters', []);
            $lastCheck = $request->input('last_check');

            // Check if data has changed since last check
            if ($lastCheck) {
                $hasChanged = $this->realTimeDataService->hasDataChanged(
                    $module, 
                    $user->id, 
                    $branchId, 
                    $lastCheck, 
                    $filters
                );

                if (!$hasChanged) {
                    return response()->json([
                        'success' => true,
                        'has_changes' => false,
                        'message' => 'No changes since last check',
                        'timestamp' => now()->toISOString()
                    ]);
                }
            }

            // Get fresh data
            $data = $this->realTimeDataService->getModuleData(
                $module, 
                $user->id, 
                $branchId, 
                $filters
            );

            return response()->json([
                'success' => true,
                'has_changes' => true,
                'data' => $data,
                'timestamp' => now()->toISOString(),
                'module' => $module
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch module data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data change summary for multiple modules
     */
    public function getDataChangeSummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'last_check' => 'required|date',
            'modules' => 'sometimes|array',
            'modules.*' => 'string|in:patients,appointments,queue,lab_results,prescriptions,billing,emergency_alerts,wards,pharmacy'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('web')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $branchId = $user->facilityUsers()->where('is_active', true)->first()?->branch_id;
            
            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active branch found'
                ], 400);
            }

            $lastCheck = $request->input('last_check');
            $requestedModules = $request->input('modules');

            // Get change summary
            $changes = $this->realTimeDataService->getDataChangeSummary(
                $user->id, 
                $branchId, 
                $lastCheck
            );

            // Filter by requested modules if provided
            if ($requestedModules) {
                $changes = array_intersect_key($changes, array_flip($requestedModules));
            }

            return response()->json([
                'success' => true,
                'changes' => $changes,
                'timestamp' => now()->toISOString(),
                'has_any_changes' => !empty(array_filter($changes, fn($change) => $change['has_changes']))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data change summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get intelligent polling interval for user
     */
    public function getPollingInterval(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'module' => 'sometimes|string|in:patients,appointments,queue,lab_results,prescriptions,billing,emergency_alerts,wards,pharmacy'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = Auth::user();
            $module = $request->input('module');

            $interval = $this->realTimeDataService->getPollingInterval($user->id, $module);

            return response()->json([
                'success' => true,
                'interval' => $interval,
                'interval_seconds' => $interval / 1000,
                'module' => $module,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get polling interval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invalidate cache for specific module
     */
    public function invalidateCache(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'module' => 'required|string|in:patients,appointments,queue,lab_results,prescriptions,billing,emergency_alerts,wards,pharmacy'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('web')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $branchId = $user->facilityUsers()->where('is_active', true)->first()?->branch_id;
            
            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active branch found'
                ], 400);
            }

            $module = $request->input('module');

            $this->realTimeDataService->invalidateModuleCache($module, $user->id, $branchId);

            return response()->json([
                'success' => true,
                'message' => 'Cache invalidated successfully',
                'module' => $module,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to invalidate cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's active modules
     */
    public function getActiveModules(): JsonResponse
    {
        try {
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('web')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            $modules = [];

            // Check permissions and add modules
            if ($user->can('view_patients')) {
                $modules[] = 'patients';
            }
            if ($user->can('view_appointments')) {
                $modules[] = 'appointments';
            }
            if ($user->can('view_queue')) {
                $modules[] = 'queue';
            }
            if ($user->can('view_lab_results')) {
                $modules[] = 'lab_results';
            }
            if ($user->can('view_prescriptions')) {
                $modules[] = 'prescriptions';
            }
            if ($user->can('view_billing')) {
                $modules[] = 'billing';
            }
            if ($user->can('view_emergency_alerts')) {
                $modules[] = 'emergency_alerts';
            }
            if ($user->can('view_wards')) {
                $modules[] = 'wards';
            }
            if ($user->can('view_pharmacy')) {
                $modules[] = 'pharmacy';
            }

            return response()->json([
                'success' => true,
                'modules' => $modules,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get active modules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user activity timestamp
     */
    public function updateActivity(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('web')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Update user activity timestamp
            cache()->put("user_activity_{$user->id}", now()->toISOString(), 3600); // 1 hour

            return response()->json([
                'success' => true,
                'message' => 'Activity updated',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
