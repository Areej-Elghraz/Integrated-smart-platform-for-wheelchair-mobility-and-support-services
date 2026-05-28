<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $dashboardType;
    public $data;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $dashboardType, $data)
    {
        $this->userId = $userId;
        $this->dashboardType = $dashboardType;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.' . $this->userId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->dashboardType,
            'data' => $this->data,
        ];
    }
}
