<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Teleconsultation;
use App\Models\TeleconsultationChat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TeleconsultationChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:teleconsultation.chat.view')->only(['index', 'unreadCount']);
        $this->middleware('permission:teleconsultation.chat.send')->only(['store']);
        $this->middleware('permission:teleconsultation.chat.edit')->only(['update']);
        $this->middleware('permission:teleconsultation.chat.delete')->only(['destroy']);
    }

    /**
     * Get chat messages for a teleconsultation.
     */
    public function index(Request $request, Teleconsultation $teleconsultation): JsonResponse
    {
        $messages = $teleconsultation->chatMessages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Send a message in teleconsultation chat.
     */
    public function store(Request $request, Teleconsultation $teleconsultation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'message_type' => 'sometimes|in:text,image,file,prescription,diagnosis,system_alert',
            'file' => 'sometimes|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $messageData = [
            'teleconsultation_id' => $teleconsultation->id,
            'sender_id' => Auth::id(),
            'sender_type' => $this->getSenderType(Auth::user()),
            'message' => $request->message,
            'message_type' => $request->message_type ?? 'text',
        ];

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('teleconsultation/files', $fileName, 'public');
            
            $messageData = array_merge($messageData, [
                'file_url' => Storage::url($filePath),
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        $message = TeleconsultationChat::create($messageData);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message->load('sender')
        ], 201);
    }

    /**
     * Update a chat message.
     */
    public function update(Request $request, TeleconsultationChat $chat): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:2000',
                'edit_reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can edit this message
            if ($chat->sender_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to edit this message'
                ], 403);
            }

            $chat->editMessage($request->message, $request->edit_reason);

            return response()->json([
                'success' => true,
                'message' => 'Message updated successfully',
                'data' => $chat->load('sender')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating teleconsultation chat message: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'chat_id' => $chat->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update message. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete a chat message.
     */
    public function destroy(TeleconsultationChat $chat): JsonResponse
    {
        try {
            // Check if user can delete this message
            if ($chat->sender_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this message'
                ], 403);
            }

            // Delete associated file if exists
            if ($chat->file_url) {
                $filePath = str_replace('/storage/', '', $chat->file_url);
                Storage::disk('public')->delete($filePath);
            }

            $chat->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting teleconsultation chat message: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'chat_id' => $chat->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message. Please try again.'
            ], 500);
        }
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(Request $request, Teleconsultation $teleconsultation): JsonResponse
    {
        $messageIds = $request->input('message_ids', []);
        
        if (empty($messageIds)) {
            // Mark all unread messages as read
            $teleconsultation->chatMessages()
                ->where('sender_id', '!=', Auth::id())
                ->unread()
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        } else {
            // Mark specific messages as read
            TeleconsultationChat::whereIn('id', $messageIds)
                ->where('sender_id', '!=', Auth::id())
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read'
        ]);
    }

    /**
     * Get unread message count for a teleconsultation.
     */
    public function unreadCount(Teleconsultation $teleconsultation): JsonResponse
    {
        $count = $teleconsultation->chatMessages()
            ->where('sender_id', '!=', Auth::id())
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Send system message.
     */
    public function sendSystemMessage(Teleconsultation $teleconsultation, string $message, string $type = 'system_alert'): JsonResponse
    {
        $systemMessage = TeleconsultationChat::create([
            'teleconsultation_id' => $teleconsultation->id,
            'sender_id' => Auth::id(),
            'sender_type' => 'system',
            'message' => $message,
            'message_type' => $type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'System message sent successfully',
            'data' => $systemMessage
        ]);
    }

    /**
     * Get sender type based on user role.
     */
    private function getSenderType($user): string
    {
        if ($user->hasRole('doctor') || $user->hasRole('nurse') || $user->hasRole('admin')) {
            return 'doctor';
        }
        
        return 'patient';
    }
}
