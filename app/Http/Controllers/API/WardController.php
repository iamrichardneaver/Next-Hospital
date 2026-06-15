<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\BedAssignment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WardController extends Controller
{
    /**
     * Display a listing of wards.
     */
    public function index(Request $request)
    {
        $query = Ward::withCount(['beds', 'beds as occupied_beds_count' => function($q) {
            $q->where('status', 'occupied');
        }])->orderBy('name');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by floor
        if ($request->has('floor')) {
            $query->where('floor', $request->floor);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $wards = $query->paginate(20);

        // Transform the data to match frontend expectations
        $transformedWards = $wards->getCollection()->map(function ($ward) {
            return [
                'id' => $ward->id,
                'name' => $ward->name,
                'type' => $ward->type,
                'floor' => $ward->floor,
                'total_beds' => $ward->beds_count,
                'occupied_beds' => $ward->occupied_beds_count,
                'available_beds' => $ward->beds_count - $ward->occupied_beds_count,
                'description' => $ward->description,
                'is_active' => $ward->is_active,
                'created_at' => $ward->created_at,
                'updated_at' => $ward->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedWards,
            'meta' => [
                'current_page' => $wards->currentPage(),
                'last_page' => $wards->lastPage(),
                'per_page' => $wards->perPage(),
                'total' => $wards->total(),
            ],
            'message' => 'Wards retrieved successfully'
        ]);
    }

    /**
     * Store a newly created ward.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255|unique:wards,code',
                'type' => 'required|string|max:100',
                'total_beds' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $ward = Ward::create([
                'name' => $request->name,
                'code' => $request->code,
                'type' => $request->type,
                'total_beds' => $request->total_beds,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'branch_id' => 1 // Default branch ID
            ]);

            return response()->json([
                'success' => true,
                'data' => $ward,
                'message' => 'Ward created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating ward: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating ward: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified ward.
     */
    public function show($id)
    {
        $ward = Ward::withCount(['beds', 'beds as occupied_beds_count' => function($q) {
            $q->where('status', 'occupied');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ward,
            'message' => 'Ward retrieved successfully'
        ]);
    }

    /**
     * Update the specified ward.
     */
    public function update(Request $request, $id)
    {
        try {
            $ward = Ward::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'code' => 'sometimes|string|max:255|unique:wards,code,' . $id,
                'type' => 'sometimes|string|max:100',
                'total_beds' => 'sometimes|integer|min:0',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $ward->update($request->only(['name', 'code', 'type', 'total_beds', 'description', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => $ward,
                'message' => 'Ward updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ward not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating ward: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'ward_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating ward: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified ward.
     */
    public function destroy($id)
    {
        try {
            $ward = Ward::findOrFail($id);

            // Check if ward has beds
            if ($ward->beds()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete ward with existing beds'
                ], 400);
            }

            $ward->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ward deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ward not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting ward: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'ward_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting ward: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ward statistics.
     */
    public function getStatistics()
    {
        $totalWards = Ward::count();
        $totalBeds = Bed::count();
        $occupiedBeds = Bed::where('status', 'occupied')->count();
        $availableBeds = Bed::where('status', 'available')->count();
        $maintenanceBeds = Bed::where('status', 'maintenance')->count();
        $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 2) : 0;

        $wardUtilization = Ward::withCount(['beds', 'beds as occupied_beds_count' => function($q) {
            $q->where('status', 'occupied');
        }])->get()->map(function ($ward) {
            $occupancyRate = $ward->beds_count > 0 ? round(($ward->occupied_beds_count / $ward->beds_count) * 100, 2) : 0;
            return [
                'ward_id' => $ward->id,
                'ward_name' => $ward->name,
                'total_beds' => $ward->beds_count,
                'occupied_beds' => $ward->occupied_beds_count,
                'occupancy_rate' => $occupancyRate
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_wards' => $totalWards,
                'total_beds' => $totalBeds,
                'occupied_beds' => $occupiedBeds,
                'available_beds' => $availableBeds,
                'maintenance_beds' => $maintenanceBeds,
                'occupancy_rate' => $occupancyRate,
                'ward_utilization' => $wardUtilization
            ],
            'message' => 'Ward statistics retrieved successfully'
        ]);
    }
}