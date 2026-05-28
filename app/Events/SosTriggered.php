<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SosTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $recipientId;
    public array $payload;

    /**
     * Create a new event instance.
     */
    public function __construct(int $recipientId, array $payload)
    {
        $this->recipientId = $recipientId;
        $this->payload = $payload;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sos.' . $this->recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'emergency.sos';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
