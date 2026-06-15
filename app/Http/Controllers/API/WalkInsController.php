<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WalkIn;
use App\Models\Patient;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WalkInsController extends Controller
{
    /**
     * Display a listing of walk-ins.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WalkIn::with(['patient', 'branch', 'creator'])
                ->orderBy('id', 'desc');

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by branch
            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $query->whereHas('patient', function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->search . '%')
                      ->orWhere('last_name', 'like', '%' . $request->search . '%')
                      ->orWhere('patient_id', 'like', '%' . $request->search . '%');
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 20);
            $walkIns = $query->paginate($perPage);

            // Transform the data
            $transformedWalkIns = $walkIns->getCollection()->map(function ($walkIn) {
                return [
                    'id' => $walkIn->id,
                    'walk_in_number' => $walkIn->walk_in_number,
                    'patient_id' => $walkIn->patient_id,
                    'patient_name' => $walkIn->patient ? $walkIn->patient->first_name . ' ' . $walkIn->patient->last_name : 'Unknown',
                    'patient_id_number' => $walkIn->patient ? $walkIn->patient->patient_id : null,
                    'branch_id' => $walkIn->branch_id,
                    'branch_name' => $walkIn->branch ? $walkIn->branch->name : null,
                    'service_type' => $walkIn->service_type,
                    'visit_type' => $walkIn->visit_type,
                    'status' => $walkIn->status,
                    'check_in_time' => $walkIn->check_in_time,
                    'check_out_time' => $walkIn->check_out_time,
                    'notes' => $walkIn->notes,
                    'created_at' => $walkIn->created_at,
                    'updated_at' => $walkIn->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedWalkIns,
                'meta' => [
                    'current_page' => $walkIns->currentPage(),
                    'last_page' => $walkIns->lastPage(),
                    'per_page' => $walkIns->perPage(),
                    'total' => $walkIns->total(),
                ],
                'message' => 'Walk-ins retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve walk-ins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created walk-in.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'service_type' => 'required|string|max:255',
            'visit_type' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $walkIn = WalkIn::create([
                'patient_id' => $request->patient_id,
                'branch_id' => $request->branch_id,
                'service_type' => $request->service_type,
                'visit_type' => $request->visit_type,
                'status' => 'active',
                'check_in_time' => now(),
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $walkIn,
                'message' => 'Walk-in created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create walk-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified walk-in.
     */
    public function show(WalkIn $walkIn): JsonResponse
    {
        try {
            $walkIn->load(['patient', 'branch', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $walkIn,
                'message' => 'Walk-in retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve walk-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified walk-in.
     */
    public function update(Request $request, WalkIn $walkIn): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type' => 'sometimes|string|max:255',
            'visit_type' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $walkIn->update([
                'service_type' => $request->service_type ?? $walkIn->service_type,
                'visit_type' => $request->visit_type ?? $walkIn->visit_type,
                'status' => $request->status ?? $walkIn->status,
                'notes' => $request->notes ?? $walkIn->notes,
                'updated_by' => auth()->id(),
            ]);

            if ($request->status === 'completed') {
                $walkIn->update(['check_out_time' => now()]);
            }

            return response()->json([
                'success' => true,
                'data' => $walkIn,
                'message' => 'Walk-in updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update walk-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified walk-in.
     */
    public function destroy(WalkIn $walkIn): JsonResponse
    {
        try {
            $walkIn->delete();

            return response()->json([
                'success' => true,
                'message' => 'Walk-in deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete walk-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get walk-ins statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id');
            $dateFrom = $request->get('date_from', now()->startOfMonth());
            $dateTo = $request->get('date_to', now()->endOfMonth());

            $query = WalkIn::whereBetween('created_at', [$dateFrom, $dateTo]);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $statistics = [
                'total' => $query->count(),
                'active' => $query->where('status', 'active')->count(),
                'completed' => $query->where('status', 'completed')->count(),
                'cancelled' => $query->where('status', 'cancelled')->count(),
                'today' => $query->whereDate('created_at', today())->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Walk-ins statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve walk-ins statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
