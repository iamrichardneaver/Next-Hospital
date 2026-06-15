<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'audio_enabled',
        'audio_volume',
        'notification_sound',
        'notify_opd_queue',
        'notify_lab_queue',
        'notify_pharmacy_queue',
        'notify_emergency_queue',
        'notify_triage_queue',
        'notify_routine',
        'notify_urgent',
        'notify_critical',
        'notify_new_patient',
        'notify_patient_waiting',
        'notify_prescription_ready',
        'notify_lab_result_ready',
        'notify_consultation_required',
        'check_interval',
        'desktop_notification',
        'do_not_disturb',
        'dnd_start',
        'dnd_end',
    ];

    protected $casts = [
        'audio_enabled' => 'boolean',
        'audio_volume' => 'integer',
        'notify_opd_queue' => 'boolean',
        'notify_lab_queue' => 'boolean',
        'notify_pharmacy_queue' => 'boolean',
        'notify_emergency_queue' => 'boolean',
        'notify_triage_queue' => 'boolean',
        'notify_routine' => 'boolean',
        'notify_urgent' => 'boolean',
        'notify_critical' => 'boolean',
        'notify_new_patient' => 'boolean',
        'notify_patient_waiting' => 'boolean',
        'notify_prescription_ready' => 'boolean',
        'notify_lab_result_ready' => 'boolean',
        'notify_consultation_required' => 'boolean',
        'check_interval' => 'integer',
        'desktop_notification' => 'boolean',
        'do_not_disturb' => 'boolean',
    ];

    /**
     * Form field keys used by the notification settings UI.
     */
    public static function formFields(): array
    {
        return [
            'audio_enabled',
            'audio_volume',
            'notification_sound',
            'notify_opd_queue',
            'notify_lab_queue',
            'notify_pharmacy_queue',
            'notify_emergency_queue',
            'notify_triage_queue',
            'notify_routine',
            'notify_urgent',
            'notify_critical',
            'notify_new_patient',
            'notify_patient_waiting',
            'notify_prescription_ready',
            'notify_lab_result_ready',
            'notify_consultation_required',
            'check_interval',
            'desktop_notification',
            'do_not_disturb',
            'dnd_start',
            'dnd_end',
        ];
    }

    /**
     * Normalize validated input before persisting.
     */
    public static function normalizeUpdateData(array $data): array
    {
        foreach (['dnd_start', 'dnd_end'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if ($data[$field] === '' || $data[$field] === null) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * Format preferences for the settings form and AJAX responses.
     */
    public function toFormArray(): array
    {
        $data = $this->only(self::formFields());

        foreach (['dnd_start', 'dnd_end'] as $field) {
            if (!empty($data[$field])) {
                $data[$field] = substr((string) $data[$field], 0, 5);
            } else {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * Get the user that owns the notification preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user should receive an FCM push for a notification type.
     */
    public function shouldNotifyForPush(string $type, ?string $priority = null): bool
    {
        if ($this->do_not_disturb && $this->isInDoNotDisturbPeriod()) {
            return false;
        }

        $type = strtolower($type);

        if ($type === 'config_update') {
            return true;
        }

        $preferenceField = match ($type) {
            'lab_result', 'lab_result_ready' => 'notify_lab_result_ready',
            'prescription', 'new_prescription' => 'notify_prescription_ready',
            'consultation', 'consultation_required', 'vitals_needed' => 'notify_consultation_required',
            'new_patient' => 'notify_new_patient',
            'patient_waiting' => 'notify_patient_waiting',
            'new_lab_request', 'new_lab_order' => 'notify_lab_queue',
            'new_pharmacy_patient' => 'notify_pharmacy_queue',
            'emergency_alert', 'emergency' => 'notify_emergency_queue',
            default => null,
        };

        if ($preferenceField !== null) {
            return (bool) $this->{$preferenceField};
        }

        $priority = strtolower((string) ($priority ?? 'medium'));

        return match ($priority) {
            'critical', 'emergency' => (bool) $this->notify_critical,
            'urgent', 'high' => (bool) $this->notify_urgent,
            'routine', 'normal', 'medium', 'low' => (bool) $this->notify_routine,
            default => true,
        };
    }

    /**
     * Check if user should be notified for a specific queue type.
     */
    public function shouldNotifyForQueue(string $queueType): bool
    {
        if ($this->do_not_disturb && $this->isInDoNotDisturbPeriod()) {
            return false;
        }

        if (!$this->audio_enabled) {
            return false;
        }

        $queueType = strtolower($queueType);
        
        switch ($queueType) {
            case 'opd':
                return $this->notify_opd_queue;
            case 'lab':
                return $this->notify_lab_queue;
            case 'pharmacy':
                return $this->notify_pharmacy_queue;
            case 'emergency':
                return $this->notify_emergency_queue;
            case 'triage':
                return $this->notify_triage_queue;
            default:
                return true;
        }
    }

    /**
     * Check if user should be notified for a specific priority.
     */
    public function shouldNotifyForPriority(string $priority): bool
    {
        if ($this->do_not_disturb && $this->isInDoNotDisturbPeriod()) {
            return false;
        }

        if (!$this->audio_enabled) {
            return false;
        }

        $priority = strtolower($priority);
        
        switch ($priority) {
            case 'routine':
            case 'normal':
                return $this->notify_routine;
            case 'urgent':
                return $this->notify_urgent;
            case 'critical':
            case 'emergency':
                return $this->notify_critical;
            default:
                return true;
        }
    }

    /**
     * Check if current time is in Do Not Disturb period.
     */
    public function isInDoNotDisturbPeriod(): bool
    {
        if (!$this->do_not_disturb || !$this->dnd_start || !$this->dnd_end) {
            return false;
        }

        $now = now()->format('H:i:s');
        return $now >= $this->dnd_start && $now <= $this->dnd_end;
    }

    /**
     * Get sound file based on priority.
     */
    public function getSoundForPriority(string $priority): string
    {
        if ($this->notification_sound === 'custom') {
            return $this->notification_sound;
        }

        $priority = strtolower($priority);
        
        switch ($priority) {
            case 'critical':
            case 'emergency':
                return 'critical';
            case 'urgent':
                return 'urgent';
            default:
                return 'standard';
        }
    }

    /**
     * Create default preferences for a user.
     */
    public static function createDefault(int $userId): self
    {
        return self::create([
            'user_id' => $userId,
            'audio_enabled' => true,
            'audio_volume' => 80,
            'notification_sound' => 'standard',
            'notify_opd_queue' => true,
            'notify_lab_queue' => true,
            'notify_pharmacy_queue' => true,
            'notify_emergency_queue' => true,
            'notify_triage_queue' => true,
            'notify_routine' => true,
            'notify_urgent' => true,
            'notify_critical' => true,
            'notify_new_patient' => true,
            'notify_patient_waiting' => true,
            'notify_prescription_ready' => true,
            'notify_lab_result_ready' => true,
            'notify_consultation_required' => true,
            'check_interval' => 30,
            'desktop_notification' => true,
            'do_not_disturb' => false,
        ]);
    }
}

