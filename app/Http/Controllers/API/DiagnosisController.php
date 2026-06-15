<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DiagnosisController extends Controller
{
    /**
     * Get diagnoses for a consultation.
     */
    public function index(Request $request, $consultationId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            
            $query = $consultation->consultationDiagnoses()
                ->with(['diagnosedBy']);

            if ($request->filled('type')) {
                $query->where('diagnosis_type', $request->get('type'));
            }

            if ($request->filled('confidence_level')) {
                $query->where('confidence_level', $request->get('confidence_level'));
            }

            if ($request->filled('is_primary')) {
                $query->where('is_primary', $request->get('is_primary') === 'true');
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->get('is_active') === 'true');
            }

            $diagnoses = $query->orderBy('is_primary', 'desc')
                ->orderBy('diagnosis_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $diagnoses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching diagnoses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new diagnosis.
     */
    public function store(Request $request, $consultationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'icd_code' => 'required|string|max:20',
            'diagnosis_description' => 'required|string|max:1000',
            'diagnosis_type' => 'required|in:primary,secondary,differential',
            'confidence_level' => 'required|in:confirmed,probable,possible',
            'diagnosis_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultation::findOrFail($consultationId);

            DB::beginTransaction();

            // If this is marked as primary, unmark other primary diagnoses
            if ($request->get('is_primary', false)) {
                $consultation->consultationDiagnoses()
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $diagnosis = Diagnosis::create([
                'consultation_id' => $consultation->id,
                'icd_code' => $request->icd_code,
                'diagnosis_description' => $request->diagnosis_description,
                'diagnosis_type' => $request->diagnosis_type,
                'confidence_level' => $request->confidence_level,
                'diagnosis_date' => $request->diagnosis_date ?? now()->toDateString(),
                'notes' => $request->notes,
                'is_primary' => $request->get('is_primary', false),
                'is_active' => $request->get('is_active', true),
                'diagnosed_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $diagnosis->load(['diagnosedBy']),
                'message' => 'Diagnosis created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating diagnosis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific diagnosis.
     */
    public function show($consultationId, $diagnosisId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $diagnosis = $consultation->consultationDiagnoses()
                ->with(['diagnosedBy'])
                ->findOrFail($diagnosisId);

            return response()->json([
                'success' => true,
                'data' => $diagnosis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Diagnosis not found'
            ], 404);
        }
    }

    /**
     * Update a diagnosis.
     */
    public function update(Request $request, $consultationId, $diagnosisId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'icd_code' => 'nullable|string|max:20',
            'diagnosis_description' => 'nullable|string|max:1000',
            'diagnosis_type' => 'nullable|in:primary,secondary,differential',
            'confidence_level' => 'nullable|in:confirmed,probable,possible',
            'diagnosis_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultation::findOrFail($consultationId);
            $diagnosis = $consultation->consultationDiagnoses()->findOrFail($diagnosisId);

            DB::beginTransaction();

            // If marking as primary, unmark other primary diagnoses
            if ($request->has('is_primary') && $request->is_primary) {
                $consultation->consultationDiagnoses()
                    ->where('id', '!=', $diagnosis->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $diagnosis->update($request->only([
                'icd_code',
                'diagnosis_description',
                'diagnosis_type',
                'confidence_level',
                'diagnosis_date',
                'notes',
                'is_primary',
                'is_active'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $diagnosis->fresh()->load(['diagnosedBy']),
                'message' => 'Diagnosis updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating diagnosis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a diagnosis.
     */
    public function destroy($consultationId, $diagnosisId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $diagnosis = $consultation->consultationDiagnoses()->findOrFail($diagnosisId);

            $diagnosis->delete();

            return response()->json([
                'success' => true,
                'message' => 'Diagnosis deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting diagnosis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark diagnosis as primary.
     */
    public function markAsPrimary($consultationId, $diagnosisId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $diagnosis = $consultation->consultationDiagnoses()->findOrFail($diagnosisId);

            DB::beginTransaction();

            // Unmark other primary diagnoses
            $consultation->consultationDiagnoses()
                ->where('id', '!=', $diagnosis->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $diagnosis->markAsPrimary();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $diagnosis->fresh(),
                'message' => 'Diagnosis marked as primary'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error marking diagnosis as primary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get diagnoses by patient.
     */
    public function getByPatient(Request $request, $patientId): JsonResponse
    {
        try {
            $query = Diagnosis::whereHas('consultation', function ($q) use ($patientId) {
                $q->where('patient_id', $patientId);
            })->with(['consultation', 'diagnosedBy']);

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->get('is_active') === 'true');
            }

            $diagnoses = $query->orderBy('diagnosis_date', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $diagnoses->items(),
                'meta' => [
                    'current_page' => $diagnoses->currentPage(),
                    'last_page' => $diagnoses->lastPage(),
                    'per_page' => $diagnoses->perPage(),
                    'total' => $diagnoses->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching diagnoses: ' . $e->getMessage()
            ], 500);
        }
    }
}
