<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConsultationIntervention;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ConsultationInterventionController extends Controller
{
    /**
     * Get interventions for a consultation.
     */
    public function index(Request $request, $consultationId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            
            $query = $consultation->interventions()
                ->with(['medication', 'orderedBy']);

            if ($request->filled('intervention_type')) {
                $query->where('intervention_type', $request->get('intervention_type'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('priority')) {
                $query->where('priority', $request->get('priority'));
            }

            $interventions = $query->orderBy('ordered_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $interventions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching interventions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new intervention.
     */
    public function store(Request $request, $consultationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'intervention_type' => 'required|in:medication,procedure,lab_order,imaging_order,referral,counseling,lifestyle_advice',
            'description' => 'required|string|max:1000',
            'medication_id' => 'nullable|exists:drugs,id',
            'dosage_instructions' => 'nullable|string|max:500',
            'frequency' => 'nullable|string|max:100',
            'duration' => 'nullable|string|max:100',
            'procedure_code' => 'nullable|string|max:50',
            'lab_test_id' => 'nullable|exists:lab_requests,id',
            'imaging_id' => 'nullable|exists:radiology_requests,id',
            'priority' => 'required|in:routine,urgent',
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
            $consultation = Consultation::findOrFail($consultationId);

            $intervention = ConsultationIntervention::create([
                'consultation_id' => $consultation->id,
                'intervention_type' => $request->intervention_type,
                'description' => $request->description,
                'medication_id' => $request->medication_id,
                'dosage_instructions' => $request->dosage_instructions,
                'frequency' => $request->frequency,
                'duration' => $request->duration,
                'procedure_code' => $request->procedure_code,
                'lab_test_id' => $request->lab_test_id,
                'imaging_id' => $request->imaging_id,
                'priority' => $request->priority,
                'notes' => $request->notes,
                'status' => 'ordered',
                'ordered_by' => auth()->id(),
                'ordered_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $intervention->load(['medication', 'orderedBy']),
                'message' => 'Intervention created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating intervention: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific intervention.
     */
    public function show($consultationId, $interventionId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $intervention = $consultation->interventions()
                ->with(['medication', 'orderedBy'])
                ->findOrFail($interventionId);

            return response()->json([
                'success' => true,
                'data' => $intervention
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Intervention not found'
            ], 404);
        }
    }

    /**
     * Update an intervention.
     */
    public function update(Request $request, $consultationId, $interventionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'intervention_type' => 'nullable|in:medication,procedure,lab_order,imaging_order,referral,counseling,lifestyle_advice',
            'description' => 'nullable|string|max:1000',
            'medication_id' => 'nullable|exists:drugs,id',
            'dosage_instructions' => 'nullable|string|max:500',
            'frequency' => 'nullable|string|max:100',
            'duration' => 'nullable|string|max:100',
            'procedure_code' => 'nullable|string|max:50',
            'lab_test_id' => 'nullable|exists:lab_requests,id',
            'imaging_id' => 'nullable|exists:radiology_requests,id',
            'priority' => 'nullable|in:routine,urgent',
            'status' => 'nullable|in:ordered,completed,cancelled',
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
            $consultation = Consultation::findOrFail($consultationId);
            $intervention = $consultation->interventions()->findOrFail($interventionId);

            $updateData = $request->only([
                'intervention_type',
                'description',
                'medication_id',
                'dosage_instructions',
                'frequency',
                'duration',
                'procedure_code',
                'lab_test_id',
                'imaging_id',
                'priority',
                'status',
                'notes'
            ]);

            if ($request->has('status') && $request->status === 'completed') {
                $updateData['completed_at'] = now();
            }

            $intervention->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $intervention->fresh()->load(['medication', 'orderedBy']),
                'message' => 'Intervention updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating intervention: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an intervention.
     */
    public function destroy($consultationId, $interventionId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $intervention = $consultation->interventions()->findOrFail($interventionId);

            // Only allow deletion if status is ordered
            if ($intervention->status !== 'ordered') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete intervention that is not in ordered status'
                ], 422);
            }

            $intervention->delete();

            return response()->json([
                'success' => true,
                'message' => 'Intervention deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting intervention: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark intervention as completed.
     */
    public function markAsCompleted($consultationId, $interventionId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $intervention = $consultation->interventions()->findOrFail($interventionId);

            $intervention->markAsCompleted();

            return response()->json([
                'success' => true,
                'data' => $intervention->fresh(),
                'message' => 'Intervention marked as completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking intervention as completed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get interventions by type.
     */
    public function getByType(Request $request, $consultationId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            
            $validator = Validator::make($request->all(), [
                'intervention_type' => 'required|in:medication,procedure,lab_order,imaging_order,referral,counseling,lifestyle_advice'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $interventions = $consultation->interventions()
                ->where('intervention_type', $request->intervention_type)
                ->with(['medication', 'orderedBy'])
                ->orderBy('ordered_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $interventions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching interventions: ' . $e->getMessage()
            ], 500);
        }
    }
}
