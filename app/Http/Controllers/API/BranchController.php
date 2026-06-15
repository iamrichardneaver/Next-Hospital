<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    /**
     * Display a listing of branches.
     */
    public function index(Request $request)
    {
        $query = Branch::query();

        // Search by name or location
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $branches = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $branches,
            'message' => 'Branches retrieved successfully'
        ]);
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'address' => 'nullable|string',
                'phone' => 'nullable|string',
                'email' => 'nullable|email',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $branch = Branch::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $branch,
                'message' => 'Branch created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating branch: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating branch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified branch.
     */
    public function show($id)
    {
        $branch = Branch::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $branch,
            'message' => 'Branch retrieved successfully'
        ]);
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, $id)
    {
        try {
            $branch = Branch::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'location' => 'sometimes|string|max:255',
                'address' => 'nullable|string',
                'phone' => 'nullable|string',
                'email' => 'nullable|email',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Use only validated data for security (prevent mass assignment vulnerabilities)
            $branch->update($validator->validated());

            return response()->json([
                'success' => true,
                'data' => $branch,
                'message' => 'Branch updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating branch: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'branch_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating branch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified branch.
     */
    public function destroy($id)
    {
        try {
            $branch = Branch::findOrFail($id);
            $branch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Branch deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting branch: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'branch_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting branch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active branches (public endpoint for branch switcher)
     */
    public function getActiveBranches()
    {
        $branches = Branch::where('is_active', true)
            ->select('id', 'name', 'code', 'address', 'phone', 'email', 'timezone', 'is_active', 'created_at', 'updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $branches,
            'message' => 'Active branches retrieved successfully'
        ]);
    }
}
