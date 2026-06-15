<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FollowUpController extends Controller
{
    /**
     * Get follow-ups for a consultation.
     */
    public function index(Request $request, $consultationId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            
            $query = $consultation->followUps()
                ->with(['assignedTo', 'creator']);

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('follow_up_type')) {
                $query->where('follow_up_type', $request->get('follow_up_type'));
            }

            if ($request->filled('overdue')) {
                $query->overdue();
            }

            $followUps = $query->orderBy('follow_up_date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $followUps
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching follow-ups: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new follow-up.
     */
    public function store(Request $request, $consultationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'follow_up_date' => 'required|date|after_or_equal:today',
            'follow_up_type' => 'required|in:in-person,teleconsultation,phone_call',
            'reason' => 'required|string|max:1000',
            'assigned_to' => 'nullable|exists:users,id',
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

            $followUp = FollowUp::create([
                'consultation_id' => $consultation->id,
                'follow_up_date' => $request->follow_up_date,
                'follow_up_type' => $request->follow_up_type,
                'reason' => $request->reason,
                'assigned_to' => $request->assigned_to ?? auth()->id(),
                'notes' => $request->notes,
                'status' => 'scheduled',
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $followUp->load(['assignedTo', 'creator']),
                'message' => 'Follow-up scheduled successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating follow-up: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific follow-up.
     */
    public function show($consultationId, $followUpId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $followUp = $consultation->followUps()
                ->with(['assignedTo', 'creator'])
                ->findOrFail($followUpId);

            return response()->json([
                'success' => true,
                'data' => $followUp
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Follow-up not found'
            ], 404);
        }
    }

    /**
     * Update a follow-up.
     */
    public function update(Request $request, $consultationId, $followUpId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'follow_up_date' => 'nullable|date',
            'follow_up_type' => 'nullable|in:in-person,teleconsultation,phone_call',
            'reason' => 'nullable|string|max:1000',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:2000',
            'status' => 'nullable|in:scheduled,completed,cancelled'
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
            $followUp = $consultation->followUps()->findOrFail($followUpId);

            $updateData = $request->only([
                'follow_up_date',
                'follow_up_type',
                'reason',
                'assigned_to',
                'notes',
                'status'
            ]);

            if ($request->has('status') && $request->status === 'completed') {
                $updateData['completed_at'] = now();
            }

            $followUp->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $followUp->fresh()->load(['assignedTo', 'creator']),
                'message' => 'Follow-up updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating follow-up: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a follow-up.
     */
    public function destroy($consultationId, $followUpId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $followUp = $consultation->followUps()->findOrFail($followUpId);

            $followUp->delete();

            return response()->json([
                'success' => true,
                'message' => 'Follow-up deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting follow-up: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark follow-up as completed.
     */
    public function markAsCompleted($consultationId, $followUpId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $followUp = $consultation->followUps()->findOrFail($followUpId);

            $followUp->markAsCompleted();

            return response()->json([
                'success' => true,
                'data' => $followUp->fresh(),
                'message' => 'Follow-up marked as completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking follow-up as completed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reschedule follow-up.
     */
    public function reschedule(Request $request, $consultationId, $followUpId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'follow_up_date' => 'required|date|after_or_equal:today'
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
            $followUp = $consultation->followUps()->findOrFail($followUpId);

            $followUp->reschedule($request->follow_up_date);

            return response()->json([
                'success' => true,
                'data' => $followUp->fresh(),
                'message' => 'Follow-up rescheduled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rescheduling follow-up: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overdue follow-ups.
     */
    public function getOverdue(Request $request): JsonResponse
    {
        try {
            $query = FollowUp::overdue()
                ->with(['consultation.patient', 'assignedTo', 'creator']);

            if ($request->filled('consultation_id')) {
                $query->where('consultation_id', $request->get('consultation_id'));
            }

            $followUps = $query->orderBy('follow_up_date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $followUps
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching overdue follow-ups: ' . $e->getMessage()
            ], 500);
        }
    }
}
