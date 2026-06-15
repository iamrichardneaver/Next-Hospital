<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Display messages page.
     */
    public function index()
    {
        $conversations = Auth::user()->conversations()
            ->with(['latestMessage.sender', 'participants'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('messages.index', compact('conversations'));
    }

    /**
     * Display specific conversation.
     */
    public function show($id)
    {
        $conversation = Conversation::with(['messages.sender', 'participants'])
            ->findOrFail($id);

        // Check if user is a participant
        if (!$conversation->participants->contains(Auth::id())) {
            abort(403, 'Unauthorized access to this conversation');
        }

        // Mark as read
        $conversation->markAsReadForUser(Auth::id());

        return view('messages.show', compact('conversation'));
    }

    /**
     * Get unread messages count (AJAX).
     */
    public function getUnreadCount(): JsonResponse
    {
        $unreadCount = 0;
        
        $conversations = Auth::user()->conversations;
        
        foreach ($conversations as $conversation) {
            $unreadCount += $conversation->unreadCountForUser(Auth::id());
        }

        return response()->json([
            'success' => true,
            'count' => $unreadCount
        ]);
    }

    /**
     * Get latest conversations (AJAX).
     */
    public function getLatest(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 5);
        
        $conversations = Auth::user()->conversations()
            ->with(['latestMessage.sender', 'participants'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($conversation) {
                $latestMessage = $conversation->latestMessage;
                $unreadCount = $conversation->unreadCountForUser(Auth::id());
                
                // Get other participants
                $otherParticipants = $conversation->participants
                    ->where('id', '!=', Auth::id())
                    ->values();

                $participantNames = $otherParticipants->pluck('name')->join(', ');
                
                return [
                    'id' => $conversation->id,
                    'subject' => $conversation->subject ?? $participantNames,
                    'type' => $conversation->type,
                    'unread_count' => $unreadCount,
                    'latest_message' => [
                        'text' => $latestMessage ? $latestMessage->message : 'No messages yet',
                        'sender' => $latestMessage ? $latestMessage->sender->name : '',
                        'time' => $latestMessage ? $latestMessage->created_at->diffForHumans() : '',
                    ],
                    'participants' => $otherParticipants->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'avatar' => $user->staffProfile && $user->staffProfile->photo 
                                ? asset('storage/' . $user->staffProfile->photo) 
                                : null,
                        ];
                    }),
                    'url' => route('messages.show', $conversation->id),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $conversations,
            'total_unread' => $conversations->sum('unread_count')
        ]);
    }

    /**
     * Create new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'recipient_ids' => 'required|array|min:1',
                'recipient_ids.*' => 'exists:users,id',
                'subject' => 'nullable|string|max:255',
                'message' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if direct conversation already exists
            $type = count($request->recipient_ids) === 1 ? 'direct' : 'group';
            
            if ($type === 'direct') {
                $existingConversation = $this->findDirectConversation(Auth::id(), $request->recipient_ids[0]);
                
                if ($existingConversation) {
                    // Add message to existing conversation
                    $message = Message::create([
                        'conversation_id' => $existingConversation->id,
                        'sender_id' => Auth::id(),
                        'message' => $request->message,
                        'type' => 'text',
                    ]);

                    $existingConversation->touch(); // Update conversation timestamp

                    return response()->json([
                        'success' => true,
                        'message' => 'Message sent',
                        'data' => [
                            'conversation_id' => $existingConversation->id,
                            'message' => $message,
                        ]
                    ]);
                }
            }

            // Create new conversation
            $conversation = Conversation::create([
                'subject' => $request->subject,
                'type' => $type,
                'created_by' => Auth::id(),
            ]);

            // Add participants
            $participantIds = array_merge($request->recipient_ids, [Auth::id()]);
            $conversation->participants()->attach($participantIds);

            // Create first message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => Auth::id(),
                'message' => $request->message,
                'type' => 'text',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation created',
                'data' => [
                    'conversation_id' => $conversation->id,
                    'message' => $message,
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating conversation: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create conversation. Please try again.'
            ], 500);
        }
    }

    /**
     * Send message to conversation.
     */
    public function sendMessage(Request $request, $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is a participant
        if (!$conversation->participants->contains(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this conversation'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:file|string',
            'file' => 'nullable|file|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $messageData = [
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::id(),
            'message' => $request->message ?? '',
            'type' => 'text',
        ];

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('messages/files', $fileName, 'public');
            
            $messageData['type'] = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file';
            $messageData['file_path'] = $filePath;
            $messageData['file_name'] = $file->getClientOriginalName();
            $messageData['file_type'] = $file->getMimeType();
        }

        $message = Message::create($messageData);
        $conversation->touch(); // Update conversation timestamp

        return response()->json([
            'success' => true,
            'message' => 'Message sent',
            'data' => $message->load('sender')
        ]);
    }

    /**
     * Mark conversation as read.
     */
    public function markAsRead($conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is a participant
        if (!$conversation->participants->contains(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this conversation'
            ], 403);
        }

        $conversation->markAsReadForUser(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as read'
        ]);
    }

    /**
     * Get available users for messaging.
     */
    public function getUsers(Request $request): JsonResponse
    {
        $search = $request->get('search', '');
        
        $users = User::where('id', '!=', Auth::id())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with('staffProfile')
            ->limit(20)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()->name ?? 'User',
                    'avatar' => $user->staffProfile && $user->staffProfile->photo 
                        ? asset('storage/' . $user->staffProfile->photo) 
                        : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Find existing direct conversation between two users.
     */
    private function findDirectConversation($user1Id, $user2Id)
    {
        return Conversation::where('type', 'direct')
            ->whereHas('participants', function ($query) use ($user1Id) {
                $query->where('user_id', $user1Id);
            })
            ->whereHas('participants', function ($query) use ($user2Id) {
                $query->where('user_id', $user2Id);
            })
            ->first();
    }
}
