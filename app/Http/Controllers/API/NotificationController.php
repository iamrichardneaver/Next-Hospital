<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\Patient;
use App\Jobs\SendNotificationJob;
use App\Events\NotificationSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request)
    {
        $query = Notification::with(['sender', 'recipient'])
            ->where('recipient_id', auth()->id())
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'message' => 'Notifications retrieved successfully'
        ]);
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'recipient_id' => 'required|exists:users,id',
                'type' => 'required|in:appointment,lab_result,prescription,emergency,general,reminder,alert',
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'data' => 'nullable|array',
                'scheduled_at' => 'nullable|date|after:now',
                'expires_at' => 'nullable|date|after:now'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notification = Notification::create([
                'sender_id' => auth()->id(),
                'recipient_id' => $request->recipient_id,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'priority' => $request->priority,
                'data' => $request->data,
                'status' => 'pending',
                'scheduled_at' => $request->scheduled_at,
                'expires_at' => $request->expires_at
            ]);

            // Send real-time notification if not scheduled
            if (!$request->scheduled_at) {
                // Dispatch notification job for real-time delivery
                SendNotificationJob::dispatch(
                    $notification->recipient_id,
                    $notification->title,
                    $notification->message,
                    $notification->type,
                    $notification->data
                );

                // Broadcast real-time notification
                broadcast(new NotificationSent($notification));
            }

            return response()->json([
                'success' => true,
                'data' => $notification->load(['sender', 'recipient']),
                'message' => 'Notification created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating notification: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified notification.
     */
    public function show($id)
    {
        $notification = Notification::with(['sender', 'recipient'])
            ->where('id', $id)
            ->where('recipient_id', auth()->id())
            ->firstOrFail();

        // Mark as read
        if ($notification->status === 'pending') {
            $notification->update([
                'status' => 'read',
                'read_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification retrieved successfully'
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('recipient_id', auth()->id())
            ->firstOrFail();

        $notification->update([
            'status' => 'read',
            'read_at' => $request->input('read_at', now())
        ]);

        return response()->json([
            'success' => true,
            'data' => $notification->fresh(['sender', 'recipient']),
            'message' => 'Notification marked as read'
        ]);
    }
    
    /**
     * Update notification (for PATCH requests from mobile).
     */
    public function update(Request $request, $id)
    {
        try {
            $notification = Notification::where('id', $id)
                ->where('recipient_id', auth()->id())
                ->firstOrFail();

            $updateData = [];
            
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
                if ($request->status === 'read' && !$notification->read_at) {
                    $updateData['read_at'] = $request->input('read_at', now());
                }
            }
            
            if ($request->has('read_at')) {
                $updateData['read_at'] = $request->read_at;
            }

            if (!empty($updateData)) {
                $notification->update($updateData);
            }

            return response()->json([
                'success' => true,
                'data' => $notification->fresh(['sender', 'recipient']),
                'message' => 'Notification updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating notification: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'notification_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $updated = Notification::where('recipient_id', auth()->id())
            ->where('status', 'pending')
            ->update([
                'status' => 'read',
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'data' => ['updated_count' => $updated],
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete notification.
     */
    public function destroy($id)
    {
        try {
            $notification = Notification::where('id', $id)
                ->where('recipient_id', auth()->id())
                ->firstOrFail();

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'notification_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notifications for mobile app (alias for index with specific formatting).
     */
    public function getNotifications(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        $query = Notification::with(['sender', 'recipient'])
            ->where('recipient_id', auth()->id())
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'unread') {
                $query->where('status', 'pending')->whereNull('read_at');
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $notifications = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                    'has_more_pages' => $notifications->hasMorePages(),
                ],
            ],
            'message' => 'Notifications retrieved successfully'
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function getUnreadCount()
    {
        $count = Notification::where('recipient_id', auth()->id())
            ->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhereNull('read_at');
            })
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
            'message' => 'Unread notifications count retrieved successfully'
        ]);
    }

    /**
     * Get notification statistics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_notifications' => Notification::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'unread_notifications' => Notification::where('status', 'pending')->count(),
            'read_notifications' => Notification::where('status', 'read')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'sent_notifications' => Notification::where('status', 'sent')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'failed_notifications' => Notification::where('status', 'failed')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'notification_types' => $this->getNotificationTypeStats($dateFrom, $dateTo),
            'notification_priorities' => $this->getNotificationPriorityStats($dateFrom, $dateTo),
            'daily_notifications' => $this->getDailyNotificationStats($dateFrom, $dateTo)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Notification statistics retrieved successfully'
        ]);
    }

    /**
     * Send bulk notifications.
     */
    public function sendBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'exists:users,id',
            'type' => 'required|in:appointment,lab_result,prescription,emergency,general,reminder,alert',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'data' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $notifications = [];
        $now = now();

        foreach ($request->recipient_ids as $recipientId) {
            $notifications[] = [
                'sender_id' => auth()->id(),
                'recipient_id' => $recipientId,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'priority' => $request->priority,
                'data' => $request->data,
                'status' => 'pending',
                'scheduled_at' => $request->scheduled_at,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        Notification::insert($notifications);

        return response()->json([
            'success' => true,
            'data' => ['sent_count' => count($notifications)],
            'message' => 'Bulk notifications sent successfully'
        ], 201);
    }

    /**
     * Send notification to role.
     */
    public function sendToRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string',
            'type' => 'required|in:appointment,lab_result,prescription,emergency,general,reminder,alert',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'data' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get users with the specified role
        $users = User::role($request->role)->pluck('id');

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No users found with the specified role'
            ], 404);
        }

        $notifications = [];
        $now = now();

        foreach ($users as $userId) {
            $notifications[] = [
                'sender_id' => auth()->id(),
                'recipient_id' => $userId,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'priority' => $request->priority,
                'data' => $request->data,
                'status' => 'pending',
                'scheduled_at' => $request->scheduled_at,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        Notification::insert($notifications);

        return response()->json([
            'success' => true,
            'data' => ['sent_count' => count($notifications)],
            'message' => 'Notifications sent to role successfully'
        ], 201);
    }

    /**
     * Create appointment reminder.
     */
    public function createAppointmentReminder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
            'reminder_time' => 'required|date|after:now',
            'message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $appointment = \App\Models\Appointment::with(['patient', 'doctor'])->findOrFail($request->appointment_id);

        $message = $request->message ?? "Reminder: You have an appointment with Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name} on {$appointment->appointment_date} at {$appointment->appointment_time}";

        $notification = Notification::create([
            'sender_id' => auth()->id(),
            'recipient_id' => $appointment->patient->user_id ?? $appointment->patient_id,
            'type' => 'appointment',
            'title' => 'Appointment Reminder',
            'message' => $message,
            'priority' => 'medium',
            'data' => [
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->appointment_time,
                'doctor_name' => "{$appointment->doctor->first_name} {$appointment->doctor->last_name}"
            ],
            'status' => 'pending',
            'scheduled_at' => $request->reminder_time
        ]);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Appointment reminder created successfully'
        ], 201);
    }

    /**
     * Create lab result notification.
     */
    public function createLabResultNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lab_result_id' => 'required|exists:lab_results,id',
            'message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $labResult = \App\Models\LabResult::with(['labRequest.patient', 'testType'])->findOrFail($request->lab_result_id);

        $message = $request->message ?? "Lab results for {$labResult->testType->name} are ready for patient {$labResult->labRequest->patient->first_name} {$labResult->labRequest->patient->last_name}";

        $notification = Notification::create([
            'sender_id' => auth()->id(),
            'recipient_id' => $labResult->labRequest->patient->user_id ?? $labResult->labRequest->patient_id,
            'type' => 'lab_result',
            'title' => 'Lab Results Ready',
            'message' => $message,
            'priority' => $labResult->abnormal_flag ? 'high' : 'medium',
            'data' => [
                'lab_result_id' => $labResult->id,
                'test_name' => $labResult->testType->name,
                'patient_name' => "{$labResult->labRequest->patient->first_name} {$labResult->labRequest->patient->last_name}",
                'abnormal_flag' => $labResult->abnormal_flag
            ],
            'status' => 'pending'
        ]);

        $this->sendRealTimeNotification($notification);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Lab result notification created successfully'
        ], 201);
    }

    /**
     * Send real-time notification.
     */
    private function sendRealTimeNotification($notification)
    {
        // This would integrate with Laravel Echo, Pusher, or WebSockets
        // For now, we'll just update the status to sent
        $notification->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);

        // In a real implementation, you would:
        // 1. Broadcast the notification via WebSocket
        // 2. Send push notification to mobile devices
        // 3. Send email notification if configured
        // 4. Send SMS notification if configured
    }

    /**
     * Get notification type statistics.
     */
    private function getNotificationTypeStats($dateFrom, $dateTo)
    {
        return Notification::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get notification priority statistics.
     */
    private function getNotificationPriorityStats($dateFrom, $dateTo)
    {
        return Notification::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get daily notification statistics.
     */
    private function getDailyNotificationStats($dateFrom, $dateTo)
    {
        return Notification::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get user notification settings.
     */
    public function getSettings()
    {
        $user = auth()->user();
        
        // Get settings from user preferences or default settings
        $settings = [
            'push_enabled' => $user->push_notifications_enabled ?? true,
            'email_enabled' => $user->email_notifications_enabled ?? true,
            'sms_enabled' => $user->sms_notifications_enabled ?? false,
            'sound_enabled' => true,
            'vibration_enabled' => true,
            'notification_types' => [
                'appointment' => true,
                'teleconsultation' => true,
                'lab_result' => true,
                'prescription' => true,
                'payment' => true,
                'emergency' => true,
                'system' => true,
                'chat' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
            'message' => 'Notification settings retrieved successfully'
        ]);
    }

    /**
     * Update user notification settings.
     */
    public function updateSettings(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'push_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'sound_enabled' => 'boolean',
            'vibration_enabled' => 'boolean',
            'notification_types' => 'array',
            'notification_types.*' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user preferences
        $updateData = [];
        if ($request->has('push_enabled')) {
            $updateData['push_notifications_enabled'] = $request->push_enabled;
        }
        if ($request->has('email_enabled')) {
            $updateData['email_notifications_enabled'] = $request->email_enabled;
        }
        if ($request->has('sms_enabled')) {
            $updateData['sms_notifications_enabled'] = $request->sms_enabled;
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        // Store notification type preferences in user settings table or JSON field
        // For now, we'll just return success as this would require additional table structure

        return response()->json([
            'success' => true,
            'data' => [
                'push_enabled' => $user->push_notifications_enabled ?? true,
                'email_enabled' => $user->email_notifications_enabled ?? true,
                'sms_enabled' => $user->sms_notifications_enabled ?? false,
            ],
            'message' => 'Notification settings updated successfully'
        ]);
    }
}
