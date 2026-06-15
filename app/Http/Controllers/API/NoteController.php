<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    /**
     * Get notes for a consultation.
     */
    public function index(Request $request, $consultationId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            
            $query = $consultation->notes()
                ->with(['creator']);

            if ($request->filled('note_type')) {
                $query->where('note_type', $request->get('note_type'));
            }

            if ($request->filled('is_urgent')) {
                $query->where('is_urgent', $request->get('is_urgent') === 'true');
            }

            if ($request->filled('is_private')) {
                $query->where('is_private', $request->get('is_private') === 'true');
            }

            // Filter private notes based on user permissions
            $user = auth()->user();
            if (!$user->hasPermissionTo('view_private_notes')) {
                $query->where('is_private', false)
                      ->orWhere('created_by', $user->id);
            }

            $notes = $query->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $notes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching notes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new note.
     */
    public function store(Request $request, $consultationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'note_type' => 'required|in:progress,procedure,discharge,consult',
            'content' => 'required|string|max:5000',
            'is_private' => 'nullable|boolean',
            'is_urgent' => 'nullable|boolean',
            'priority' => 'nullable|in:high,medium,low'
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

            $note = Note::create([
                'consultation_id' => $consultation->id,
                'note_type' => $request->note_type,
                'content' => $request->content,
                'is_private' => $request->get('is_private', false),
                'is_urgent' => $request->get('is_urgent', false),
                'priority' => $request->priority ?? 'medium',
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $note->load(['creator']),
                'message' => 'Note created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific note.
     */
    public function show($consultationId, $noteId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $note = $consultation->notes()
                ->with(['creator'])
                ->findOrFail($noteId);

            // Check if user can view private note
            $user = auth()->user();
            if ($note->is_private && $note->created_by !== $user->id && !$user->hasPermissionTo('view_private_notes')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this note'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $note
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Note not found'
            ], 404);
        }
    }

    /**
     * Update a note.
     */
    public function update(Request $request, $consultationId, $noteId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'note_type' => 'nullable|in:progress,procedure,discharge,consult',
            'content' => 'nullable|string|max:5000',
            'is_private' => 'nullable|boolean',
            'is_urgent' => 'nullable|boolean',
            'priority' => 'nullable|in:high,medium,low'
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
            $note = $consultation->notes()->findOrFail($noteId);

            // Only creator or admin can update
            $user = auth()->user();
            if ($note->created_by !== $user->id && !$user->hasPermissionTo('edit_all_notes')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this note'
                ], 403);
            }

            $note->update($request->only([
                'note_type',
                'content',
                'is_private',
                'is_urgent',
                'priority'
            ]));

            return response()->json([
                'success' => true,
                'data' => $note->fresh()->load(['creator']),
                'message' => 'Note updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a note.
     */
    public function destroy($consultationId, $noteId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $note = $consultation->notes()->findOrFail($noteId);

            // Only creator or admin can delete
            $user = auth()->user();
            if ($note->created_by !== $user->id && !$user->hasPermissionTo('delete_all_notes')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this note'
                ], 403);
            }

            $note->delete();

            return response()->json([
                'success' => true,
                'message' => 'Note deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get urgent notes for a consultation.
     */
    public function getUrgent($consultationId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            
            $notes = $consultation->notes()
                ->urgent()
                ->with(['creator'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $notes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching urgent notes: ' . $e->getMessage()
            ], 500);
        }
    }
}
