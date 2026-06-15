<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use App\Models\EmergencyVisit;
use App\Events\EmergencyAlertSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmergencyAlertController extends Controller
{
    /**
     * Display a listing of emergency alerts.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = EmergencyAlert::with(['emergencyVisit.patient', 'acknowledgedBy', 'resolvedBy']);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by priority
            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            // Filter by alert type
            if ($request->has('alert_type')) {
                $query->where('alert_type', $request->alert_type);
            }

            // Filter by branch
            if ($request->has('branch_id')) {
                $query->whereHas('emergencyVisit', function($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                });
            }

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                      ->orWhere('alert_type', 'like', "%{$search}%")
                      ->orWhereHas('emergencyVisit.patient', function($q) use ($search) {
                          $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_number', 'like', "%{$search}%");
                      });
                });
            }

            // Sort by priority and created_at
            $query->orderByRaw("CASE priority 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END")
            ->orderBy('created_at', 'desc');

            $alerts = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $alerts->items(),
                'meta' => [
                    'current_page' => $alerts->currentPage(),
                    'last_page' => $alerts->lastPage(),
                    'per_page' => $alerts->perPage(),
                    'total' => $alerts->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching emergency alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created emergency alert.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'emergency_visit_id' => 'required|exists:emergency_visits,id',
            'alert_type' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $alert = EmergencyAlert::create([
                'emergency_visit_id' => $request->emergency_visit_id,
                'alert_type' => $request->alert_type,
                'message' => $request->message,
                'priority' => $request->priority,
                'status' => 'active',
                'created_by' => auth()->id()
            ]);

            $alert->load(['emergencyVisit.patient', 'acknowledgedBy', 'resolvedBy']);

            // Dispatch real-time event
            broadcast(new EmergencyAlertSent($alert))->toOthers();

            return response()->json([
                'success' => true,
                'data' => $alert,
                'message' => 'Emergency alert created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating emergency alert: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified emergency alert.
     */
    public function show($id): JsonResponse
    {
        try {
            $alert = EmergencyAlert::with(['emergencyVisit.patient', 'acknowledgedBy', 'resolvedBy'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $alert
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency alert not found'
            ], 404);
        }
    }

    /**
     * Acknowledge an emergency alert.
     */
    public function acknowledge($id): JsonResponse
    {
        try {
            $alert = EmergencyAlert::findOrFail($id);

            if ($alert->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Alert is not active and cannot be acknowledged'
                ], 400);
            }

            $alert->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now(),
                'acknowledged_by' => auth()->id()
            ]);

            $alert->load(['emergencyVisit.patient', 'acknowledgedBy', 'resolvedBy']);

            return response()->json([
                'success' => true,
                'data' => $alert,
                'message' => 'Emergency alert acknowledged successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error acknowledging emergency alert: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve an emergency alert.
     */
    public function resolve($id): JsonResponse
    {
        try {
            $alert = EmergencyAlert::findOrFail($id);

            if ($alert->status === 'resolved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Alert is already resolved'
                ], 400);
            }

            $alert->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => auth()->id()
            ]);

            $alert->load(['emergencyVisit.patient', 'acknowledgedBy', 'resolvedBy']);

            return response()->json([
                'success' => true,
                'data' => $alert,
                'message' => 'Emergency alert resolved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resolving emergency alert: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get emergency alert statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = EmergencyAlert::query();

            // Filter by branch if provided
            if ($request->has('branch_id')) {
                $query->whereHas('emergencyVisit', function($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                });
            }

            $stats = [
                'total_alerts' => $query->count(),
                'active_alerts' => $query->where('status', 'active')->count(),
                'acknowledged_alerts' => $query->where('status', 'acknowledged')->count(),
                'resolved_alerts' => $query->where('status', 'resolved')->count(),
                'critical_alerts' => $query->where('priority', 'critical')->where('status', 'active')->count(),
                'high_alerts' => $query->where('priority', 'high')->where('status', 'active')->count(),
                'medium_alerts' => $query->where('priority', 'medium')->where('status', 'active')->count(),
                'low_alerts' => $query->where('priority', 'low')->where('status', 'active')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching alert statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active emergency alerts
     */
    public function getActiveAlerts(Request $request): JsonResponse
    {
        try {
            $query = EmergencyAlert::with(['emergencyVisit.patient', 'acknowledgedBy', 'resolvedBy'])
                ->where('status', 'active')
                ->orderBy('created_at', 'desc');

            // Filter by branch
            if ($request->has('branch_id')) {
                $query->whereHas('emergencyVisit', function($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                });
            }

            // Filter by priority
            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            $alerts = $query->get();

            return response()->json([
                'success' => true,
                'data' => $alerts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching active alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get emergency alert statistics
     */
    public function getAlertStatistics(Request $request): JsonResponse
    {
        try {
            $query = EmergencyAlert::query();

            // Filter by branch
            if ($request->has('branch_id')) {
                $query->whereHas('emergencyVisit', function($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                });
            }

            $stats = [
                'total_alerts' => $query->count(),
                'active_alerts' => $query->where('status', 'active')->count(),
                'acknowledged_alerts' => $query->where('status', 'acknowledged')->count(),
                'resolved_alerts' => $query->where('status', 'resolved')->count(),
                'critical_alerts' => $query->where('priority', 'urgent')->where('status', 'active')->count(),
                'high_alerts' => $query->where('priority', 'high')->where('status', 'active')->count(),
                'medium_alerts' => $query->where('priority', 'medium')->where('status', 'active')->count(),
                'low_alerts' => $query->where('priority', 'low')->where('status', 'active')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching alert statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}