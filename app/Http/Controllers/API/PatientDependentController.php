<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PatientDependent;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientDependentController extends Controller
{
    /**
     * Get all dependents for authenticated patient
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $user->patient;
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }

            $dependents = PatientDependent::where('patient_id', $patient->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $dependents,
                'message' => 'Dependents retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dependents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new dependent
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $user->patient;
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'relationship' => 'required|string|max:100',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:Male,Female,Other',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'emergency_contact' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dependent = PatientDependent::create([
                'patient_id' => $patient->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'relationship' => $request->relationship,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'emergency_contact' => $request->emergency_contact ?? false,
            ]);

            return response()->json([
                'success' => true,
                'data' => $dependent,
                'message' => 'Dependent added successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating dependent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update dependent
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $user->patient;
            
            $dependent = PatientDependent::where('patient_id', $patient->id)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'relationship' => 'sometimes|required|string|max:100',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:Male,Female,Other',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'emergency_contact' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dependent->update($validator->validated());

            return response()->json([
                'success' => true,
                'data' => $dependent,
                'message' => 'Dependent updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating dependent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete dependent
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $user->patient;
            
            $dependent = PatientDependent::where('patient_id', $patient->id)
                ->findOrFail($id);

            $dependent->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dependent deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting dependent: ' . $e->getMessage()
            ], 500);
        }
    }
}

