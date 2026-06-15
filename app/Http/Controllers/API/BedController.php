<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Ward;
use App\Models\BedAssignment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BedController extends Controller
{
    /**
     * Display a listing of beds.
     */
    public function index(Request $request)
    {
        $query = Bed::with(['ward', 'assignments.patient'])->orderBy('ward_id')->orderBy('bed_number');

        // Filter by ward
        if ($request->has('ward_id')) {
            $query->where('ward_id', $request->ward_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by bed type
        if ($request->has('bed_type')) {
            $query->where('bed_type', $request->bed_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search by bed number or ward name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bed_number', 'like', "%{$search}%")
                  ->orWhereHas('ward', function($wardQuery) use ($search) {
                      $wardQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $beds = $query->paginate(20);

        // Transform the data to match frontend expectations
        $transformedBeds = $beds->getCollection()->map(function ($bed) {
            // Get active assignment for this bed
            $activeAssignment = $bed->assignments->where('status', 'active')->first();
            
            return [
                'id' => $bed->id,
                'ward_id' => $bed->ward_id,
                'ward_name' => $bed->ward ? $bed->ward->name : 'Unknown Ward',
                'bed_number' => $bed->bed_number,
                'bed_type' => $bed->bed_type,
                'status' => $bed->status,
                'patient_id' => $activeAssignment ? $activeAssignment->patient_id : null,
                'patient_name' => $activeAssignment && $activeAssignment->patient ? 
                    $activeAssignment->patient->first_name . ' ' . $activeAssignment->patient->last_name : null,
                'admission_date' => $activeAssignment ? $activeAssignment->admission_date : null,
                'is_active' => $bed->is_active,
                'created_at' => $bed->created_at,
                'updated_at' => $bed->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedBeds,
            'meta' => [
                'current_page' => $beds->currentPage(),
                'last_page' => $beds->lastPage(),
                'per_page' => $beds->perPage(),
                'total' => $beds->total(),
            ],
            'message' => 'Beds retrieved successfully'
        ]);
    }

    /**
     * Store a newly created bed.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ward_id' => 'required|exists:wards,id',
                'bed_number' => 'required|string|max:50',
                'bed_type' => 'required|string|max:100',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if bed number already exists in the ward
            $existingBed = Bed::where('ward_id', $request->ward_id)
                ->where('bed_number', $request->bed_number)
                ->first();

            if ($existingBed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bed number already exists in this ward'
                ], 400);
            }

            $bed = Bed::create([
                'ward_id' => $request->ward_id,
                'bed_number' => $request->bed_number,
                'bed_type' => $request->bed_type,
                'status' => 'vacant',
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'success' => true,
                'data' => $bed,
                'message' => 'Bed created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating bed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating bed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified bed.
     */
    public function show($id)
    {
        $bed = Bed::with(['ward', 'patient'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bed,
            'message' => 'Bed retrieved successfully'
        ]);
    }

    /**
     * Update the specified bed.
     */
    public function update(Request $request, $id)
    {
        try {
            $bed = Bed::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'ward_id' => 'sometimes|exists:wards,id',
                'bed_number' => 'sometimes|string|max:50',
                'bed_type' => 'sometimes|string|max:100',
                'status' => 'sometimes|in:vacant,occupied,maintenance,reserved',
                'is_active' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if bed number already exists in the ward (if ward_id or bed_number is being updated)
            if ($request->has('ward_id') || $request->has('bed_number')) {
                $wardId = $request->ward_id ?? $bed->ward_id;
                $bedNumber = $request->bed_number ?? $bed->bed_number;
                
                $existingBed = Bed::where('ward_id', $wardId)
                    ->where('bed_number', $bedNumber)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingBed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bed number already exists in this ward'
                    ], 400);
                }
            }

            $bed->update($request->only(['ward_id', 'bed_number', 'bed_type', 'status', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => $bed,
                'message' => 'Bed updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bed not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating bed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'bed_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating bed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified bed.
     */
    public function destroy($id)
    {
        try {
            $bed = Bed::findOrFail($id);

            // Check if bed is occupied
            if ($bed->status === 'occupied') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete occupied bed'
                ], 400);
            }

            $bed->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bed deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bed not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting bed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'bed_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting bed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bed availability by ward.
     */
    public function getAvailability(Request $request)
    {
        $wardId = $request->ward_id;

        $query = Ward::with(['beds' => function($q) {
            $q->where('is_active', true);
        }]);

        if ($wardId) {
            $query->where('id', $wardId);
        }

        $wards = $query->get();

        $availability = $wards->map(function ($ward) {
            $availableBeds = $ward->beds->where('status', 'available')->count();
            
            return [
                'ward_id' => $ward->id,
                'ward_name' => $ward->name,
                'available_beds' => $availableBeds,
                'beds' => $ward->beds->map(function ($bed) {
                    return [
                        'id' => $bed->id,
                        'bed_number' => $bed->bed_number,
                        'bed_type' => $bed->bed_type,
                        'status' => $bed->status
                    ];
                })
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $availability,
            'message' => 'Bed availability retrieved successfully'
        ]);
    }

    /**
     * Get active assignment for a bed
     */
    public function getActiveAssignment($id)
    {
        $bed = Bed::findOrFail($id);
        
        $activeAssignment = BedAssignment::where('bed_id', $id)
            ->where('status', 'active')
            ->with(['patient', 'assignedBy'])
            ->first();

        if (!$activeAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'No active assignment found for this bed'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $activeAssignment,
            'message' => 'Active assignment retrieved successfully'
        ]);
    }
}
