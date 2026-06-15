<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChatService
{
    /**
     * Get user conversations with pagination
     */
    public function getUserConversations($userId, $page = 1, $limit = 20)
    {
        try {
            // Check if Conversation model exists, if not return empty
            if (!class_exists('App\Models\Conversation')) {
                return [
                    'conversations' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $limit,
                        'total' => 0,
                        'total_pages' => 0,
                        'has_more' => false
                    ]
                ];
            }

            $query = Conversation::whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['participants.user', 'lastMessage'])
            ->orderBy('updated_at', 'desc');

            $total = $query->count();
            $conversations = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();

            $data = $conversations->map(function ($conversation) use ($userId) {
                $otherParticipant = $conversation->participants->where('user_id', '!=', $userId)->first();
                
                return [
                    'id' => $conversation->id,
                    'other_user' => $otherParticipant ? [
                        'id' => $otherParticipant->user->id,
                        'name' => $otherParticipant->user->name ?? 'Unknown',
                        'profile_picture' => $otherParticipant->user->profile_picture ?? null,
                    ] : null,
                    'last_message' => $conversation->lastMessage ? [
                        'content' => $conversation->lastMessage->content,
                        'created_at' => $conversation->lastMessage->created_at->format('Y-m-d H:i:s'),
                        'is_read' => $conversation->lastMessage->is_read
                    ] : null,
                    'unread_count' => $conversation->messages()->where('sender_id', '!=', $userId)->where('is_read', false)->count(),
                    'created_at' => $conversation->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $conversation->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return [
                'conversations' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit),
                    'has_more' => $page < ceil($total / $limit)
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting user conversations: ' . $e->getMessage());
            
            // Return empty data structure instead of throwing
            return [
                'conversations' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $limit,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more' => false
                ]
            ];
        }
    }

    /**
     * Start conversation with another user
     */
    public function startConversation($userId, $otherUserId, $initialMessage = null)
    {
        try {
            DB::beginTransaction();

            // Check if conversation already exists
            $existingConversation = Conversation::whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereHas('participants', function ($q) use ($otherUserId) {
                $q->where('user_id', $otherUserId);
            })
            ->first();

            if ($existingConversation) {
                DB::commit();
                return $existingConversation;
            }

            // Create new conversation
            $conversation = Conversation::create([
                'type' => 'direct',
                'started_by' => $userId,
            ]);

            // Add participants
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $otherUserId,
            ]);

            // Send initial message if provided
            if ($initialMessage) {
                $this->sendMessage($conversation->id, $userId, $initialMessage);
            }

            DB::commit();
            return $conversation->load(['participants.user', 'messages']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error starting conversation: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send message
     */
    public function sendMessage($conversationId, $senderId, $content, $attachments = null)
    {
        try {
            $message = Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'content' => $content,
                'attachments' => $attachments,
                'is_read' => false,
            ]);

            // Update conversation timestamp
            Conversation::where('id', $conversationId)->update([
                'updated_at' => now()
            ]);

            return $message->load('sender');
        } catch (\Exception $e) {
            \Log::error('Error sending message: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get conversation messages
     */
    public function getConversationMessages($conversationId, $userId, $page = 1, $limit = 50)
    {
        try {
            $query = Message::where('conversation_id', $conversationId)
                ->with('sender')
                ->orderBy('created_at', 'desc');

            $total = $query->count();
            $messages = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();

            // Mark messages as read
            Message::where('conversation_id', $conversationId)
                ->where('sender_id', '!=', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            return [
                'messages' => $messages,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit),
                    'has_more' => $page < ceil($total / $limit)
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting conversation messages: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead($messageId, $userId)
    {
        try {
            $message = Message::findOrFail($messageId);
            
            // Only mark if user is not the sender
            if ($message->sender_id != $userId) {
                $message->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
            }

            return $message;
        } catch (\Exception $e) {
            \Log::error('Error marking message as read: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete conversation
     */
    public function deleteConversation($conversationId, $userId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);
            
            // Check if user is participant
            $isParticipant = $conversation->participants()
                ->where('user_id', $userId)
                ->exists();

            if (!$isParticipant) {
                throw new \Exception('Unauthorized');
            }

            // Delete messages
            Message::where('conversation_id', $conversationId)->delete();
            
            // Delete participants
            ConversationParticipant::where('conversation_id', $conversationId)->delete();
            
            // Delete conversation
            $conversation->delete();

            return true;
        } catch (\Exception $e) {
            \Log::error('Error deleting conversation: ' . $e->getMessage());
            throw $e;
        }
    }
}

