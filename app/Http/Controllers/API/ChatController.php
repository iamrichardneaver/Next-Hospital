<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Get user conversations
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);

            $conversations = $this->chatService->getUserConversations($user->id, $page, $limit);

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start conversation with doctor
     */
    public function startConversation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:users,id',
                'initial_message' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $conversation = $this->chatService->startConversation(
                $user->id,
                $request->doctor_id,
                $request->initial_message
            );

            return response()->json([
                'success' => true,
                'data' => $conversation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start support conversation
     */
    public function startSupportConversation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'subject' => 'nullable|string|max:255',
                'initial_message' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $conversation = $this->chatService->startSupportConversation(
                $user->id,
                $request->subject,
                $request->initial_message
            );

            return response()->json([
                'success' => true,
                'data' => $conversation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start support conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages for a conversation
     */
    public function getMessages(Request $request, $conversationId): JsonResponse
    {
        try {
            $user = Auth::user();
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 50);

            $messages = $this->chatService->getConversationMessages(
                $conversationId,
                $user->id,
                $page,
                $limit
            );

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'conversation_id' => 'required|exists:conversations,id',
                'message' => 'required|string|max:2000',
                'attachment_url' => 'nullable|string|max:500',
                'attachment_type' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $message = $this->chatService->sendMessage(
                $request->conversation_id,
                $user->id,
                $request->message,
                $request->attachment_url,
                $request->attachment_type
            );

            return response()->json([
                'success' => true,
                'data' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'conversation_id' => 'required|exists:conversations,id',
                'message_ids' => 'nullable|array',
                'message_ids.*' => 'exists:messages,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $result = $this->chatService->markMessagesAsRead(
                $request->conversation_id,
                $user->id,
                $request->message_ids
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload attachment
     */
    public function uploadAttachment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'conversation_id' => 'required|exists:conversations,id',
                'file' => 'required|file|max:10240', // 10MB max
                'message_id' => 'nullable|exists:messages,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $attachment = $this->chatService->uploadAttachment(
                $request->conversation_id,
                $user->id,
                $request->file('file'),
                $request->message_id
            );

            return response()->json([
                'success' => true,
                'data' => $attachment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload attachment: ' . $e->getMessage()
            ], 500);
        }
    }
}
