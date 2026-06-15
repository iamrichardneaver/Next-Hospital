<?php

namespace App\Observers;

use App\Models\Notification;
use App\Services\PushNotificationService;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        if (!$notification->recipient_id) {
            return;
        }

        try {
            $data = is_array($notification->data) ? $notification->data : [];
            $data['notification_id'] = (string) $notification->id;
            $data['type'] = $notification->type;
            $data['priority'] = $notification->priority;
            $data['screen'] = $data['screen'] ?? $this->screenForType($notification->type);

            if (isset($data['id'])) {
                $data['id'] = (string) $data['id'];
            }

            app(PushNotificationService::class)->sendToUser(
                (int) $notification->recipient_id,
                (string) $notification->title,
                (string) $notification->message,
                $data
            );
        } catch (\Throwable $e) {
            \Log::error('Failed to dispatch push for notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function screenForType(string $type): string
    {
        return match ($type) {
            'appointment' => 'Schedule',
            'lab_result', 'lab_result_ready' => 'LabTests',
            'payment' => 'PaymentHistory',
            'expense' => 'Expenses',
            'prescription' => 'Medications',
            'config_update' => 'Dashboard',
            default => 'Notifications',
        };
    }
}
