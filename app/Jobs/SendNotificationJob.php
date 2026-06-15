<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Models\Notification as NotificationModel;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $title;
    protected $message;
    protected $type;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $title, $message, $type = 'info', $data = [])
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Sending notification to user: {$this->userId}");

            // Create notification record
            $notification = NotificationModel::create([
                'user_id' => $this->userId,
                'title' => $this->title,
                'message' => $this->message,
                'type' => $this->type,
                'data' => json_encode($this->data),
                'is_read' => false,
                'sent_at' => now()
            ]);

            // Broadcast real-time notification
            $this->broadcastNotification($notification);

            Log::info("Notification sent successfully to user: {$this->userId}");
        } catch (\Exception $e) {
            Log::error("Failed to send notification to user: {$this->userId}, error: " . $e->getMessage());
            throw $e;
        }
    }

    private function broadcastNotification($notification)
    {
        // This would integrate with Laravel Echo/Pusher for real-time broadcasting
        // For now, we'll just log it
        Log::info("Broadcasting notification: {$notification->title}");
        
        // In a real implementation, you would use:
        // broadcast(new NotificationSent($notification))->toOthers();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Notification job failed for user: {$this->userId}, error: " . $exception->getMessage());
    }
}