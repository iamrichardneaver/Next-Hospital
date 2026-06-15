<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PatientMedicalHistory;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PatientMedicalHistoryController extends Controller
{
    /**
     * Get medical history for a patient.
     */
    public function index(Request $request, $patientId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);
            
            $query = $patient->medicalHistory();

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('condition')) {
                $query->where('condition', 'like', '%' . $request->get('condition') . '%');
            }

            $history = $query->orderBy('diagnosis_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching medical history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new medical history entry.
     */
    public function store(Request $request, $patientId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'condition' => 'required|string|max:255',
            'diagnosis_date' => 'required|date',
            'status' => 'required|in:active,resolved,chronic',
            'notes' => 'nullable|string|max:2000'
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

            $history = PatientMedicalHistory::create([
                'patient_id' => $patient->id,
                'condition' => $request->condition,
                'diagnosis_date' => $request->diagnosis_date,
                'status' => $request->status,
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $history,
                'message' => 'Medical history recorded successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recording medical history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific medical history entry.
     */
    public function show($patientId, $historyId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);
            $history = $patient->medicalHistory()->findOrFail($historyId);

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Medical history entry not found'
            ], 404);
        }
    }

    /**
     * Update a medical history entry.
     */
    public function update(Request $request, $patientId, $historyId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'condition' => 'nullable|string|max:255',
            'diagnosis_date' => 'nullable|date',
            'status' => 'nullable|in:active,resolved,chronic',
            'notes' => 'nullable|string|max:2000'
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
            $history = $patient->medicalHistory()->findOrFail($historyId);

            $history->update(array_merge(
                $request->only(['condition', 'diagnosis_date', 'status', 'notes']),
                ['updated_by' => auth()->id()]
            ));

            return response()->json([
                'success' => true,
                'data' => $history->fresh(),
                'message' => 'Medical history updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating medical history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a medical history entry.
     */
    public function destroy($patientId, $historyId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);
            $history = $patient->medicalHistory()->findOrFail($historyId);

            $history->delete();

            return response()->json([
                'success' => true,
                'message' => 'Medical history entry deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting medical history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active conditions for a patient.
     */
    public function getActiveConditions($patientId): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($patientId);
            
            $activeConditions = $patient->medicalHistory()
                ->where('status', 'active')
                ->orWhere('status', 'chronic')
                ->orderBy('diagnosis_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $activeConditions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching active conditions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get medical history for current patient (mobile app).
     */
    public function getMyMedicalHistory(Request $request): JsonResponse
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

            $history = $patient->medicalHistory()
                ->orderBy('diagnosis_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching medical history: ' . $e->getMessage()
            ], 500);
        }
    }
}
