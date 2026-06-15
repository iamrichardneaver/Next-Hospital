<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\EmergencyAlert;

class EmergencyAlertSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $alert;

    /**
     * Create a new event instance.
     */
    public function __construct(EmergencyAlert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('emergency-alerts'),
            new Channel('hospital-notifications')
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'emergency.alert';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->alert->id,
            'alert_type' => $this->alert->alert_type,
            'message' => $this->alert->message,
            'priority' => $this->alert->priority,
            'status' => $this->alert->status,
            'created_at' => $this->alert->created_at->toISOString(),
            'patient' => [
                'id' => $this->alert->emergencyVisit->patient->id,
                'name' => $this->alert->emergencyVisit->patient->full_name,
                'patient_number' => $this->alert->emergencyVisit->patient->patient_number
            ],
            'created_by' => [
                'id' => $this->alert->createdBy->id,
                'name' => $this->alert->createdBy->name
            ],
            'acknowledged_by' => $this->alert->acknowledgedBy ? [
                'id' => $this->alert->acknowledgedBy->id,
                'name' => $this->alert->acknowledgedBy->name
            ] : null,
            'acknowledged_at' => $this->alert->acknowledged_at?->toISOString(),
            'sound_alert' => $this->shouldPlaySound(),
            'visual_priority' => $this->getVisualPriority()
        ];
    }

    /**
     * Determine if sound should be played for this alert
     */
    private function shouldPlaySound(): bool
    {
        return in_array($this->alert->priority, ['critical', 'urgent']) && 
               $this->alert->status === 'active';
    }

    /**
     * Get visual priority level for UI styling
     */
    private function getVisualPriority(): string
    {
        switch ($this->alert->priority) {
            case 'critical':
                return 'danger';
            case 'urgent':
                return 'warning';
            case 'high':
                return 'info';
            case 'medium':
                return 'primary';
            case 'low':
                return 'secondary';
            default:
                return 'info';
        }
    }
}
