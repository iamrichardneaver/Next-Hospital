<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Display notifications page.
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Get unread notifications count (AJAX).
     */
    public function getUnreadCount(): JsonResponse
    {
        $count = Auth::user()->unreadNotifications->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Get latest notifications (AJAX).
     */
    public function getLatest(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $notifications = Auth::user()->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'icon' => $notification->data['icon'] ?? 'bell',
                    'url' => $notification->data['url'] ?? '#',
                    'priority' => $notification->data['priority'] ?? 'normal',
                    'is_read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'created_at_timestamp' => $notification->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => Auth::user()->unreadNotifications->count()
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = Auth::user()->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'unread_count' => Auth::user()->unreadNotifications->count()
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete notification.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $notification = Auth::user()->notifications()->find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted',
                'unread_count' => Auth::user()->unreadNotifications->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'notification_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification. Please try again.'
            ], 500);
        }
    }

    /**
     * Clear all notifications.
     */
    public function clearAll(): JsonResponse
    {
        Auth::user()->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications cleared'
        ]);
    }
}