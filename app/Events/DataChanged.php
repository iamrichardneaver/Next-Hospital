<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $module;
    public $branchId;
    public $userId;
    public $changeType;
    public $data;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(string $module, int $branchId, int $userId, string $changeType, $data = null)
    {
        $this->module = $module;
        $this->branchId = $branchId;
        $this->userId = $userId;
        $this->changeType = $changeType;
        $this->data = $data;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("branch.{$this->branchId}"),
            new PrivateChannel("user.{$this->userId}"),
            new Channel("module.{$this->module}")
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'data.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'module' => $this->module,
            'branch_id' => $this->branchId,
            'user_id' => $this->userId,
            'change_type' => $this->changeType,
            'data' => $this->data,
            'timestamp' => $this->timestamp
        ];
    }
}
