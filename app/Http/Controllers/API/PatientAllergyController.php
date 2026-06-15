<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PatientAllergy;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PatientAllergyController extends Controller
{
    /**
     * Get allergies for a patient.
     */
    public function index(Request $request, $patientId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);
            
            $query = $patient->allergies();

            if ($request->filled('severity')) {
                $query->where('severity', $request->get('severity'));
            }

            $allergies = $query->orderBy('recorded_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $allergies
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching allergies: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new allergy.
     */
    public function store(Request $request, $patientId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'allergen' => 'required|string|max:255',
            'reaction' => 'required|string|max:500',
            'severity' => 'required|in:mild,moderate,severe',
            'recorded_at' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $patient = Patient::findOrFail($patientId);

            // Check if allergy already exists
            $existing = $patient->allergies()
                ->where('allergen', $request->allergen)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Allergy already recorded for this patient'
                ], 422);
            }

            $allergy = PatientAllergy::create([
                'patient_id' => $patient->id,
                'allergen' => $request->allergen,
                'reaction' => $request->reaction,
                'severity' => $request->severity,
                'recorded_at' => $request->recorded_at ?? now(),
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $allergy,
                'message' => 'Allergy recorded successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recording allergy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific allergy.
     */
    public function show($patientId, $allergyId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);
            $allergy = $patient->allergies()->findOrFail($allergyId);

            return response()->json([
                'success' => true,
                'data' => $allergy
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Allergy not found'
            ], 404);
        }
    }

    /**
     * Update an allergy.
     */
    public function update(Request $request, $patientId, $allergyId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'allergen' => 'nullable|string|max:255',
            'reaction' => 'nullable|string|max:500',
            'severity' => 'nullable|in:mild,moderate,severe',
            'recorded_at' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $patient = Patient::findOrFail($patientId);
            $allergy = $patient->allergies()->findOrFail($allergyId);

            // Check if updating allergen name would create duplicate
            if ($request->has('allergen') && $request->allergen !== $allergy->allergen) {
                $existing = $patient->allergies()
                    ->where('id', '!=', $allergy->id)
                    ->where('allergen', $request->allergen)
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Allergy already exists for this patient'
                    ], 422);
                }
            }

            $allergy->update(array_merge(
                $request->only(['allergen', 'reaction', 'severity', 'recorded_at']),
                ['updated_by' => auth()->id()]
            ));

            return response()->json([
                'success' => true,
                'data' => $allergy->fresh(),
                'message' => 'Allergy updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating allergy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an allergy.
     */
    public function destroy($patientId, $allergyId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);
            $allergy = $patient->allergies()->findOrFail($allergyId);

            $allergy->delete();

            return response()->json([
                'success' => true,
                'message' => 'Allergy deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting allergy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get allergies for current patient (mobile app).
     */
    public function getMyAllergies(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $patient = $user->patient;

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }

            $allergies = $patient->allergies()
                ->orderBy('recorded_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $allergies
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching allergies: ' . $e->getMessage()
            ], 500);
        }
    }
}
