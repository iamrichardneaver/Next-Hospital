<?php

namespace App\Listeners;

use App\Events\NotificationSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationSent $event): void
    {
        try {
            $notification = $event->notification;
            Log::info("Processing notification broadcast: {$notification->title}");
        } catch (\Exception $e) {
            Log::error("Failed to process notification: " . $e->getMessage());
            throw $e;
        }
    }
}
