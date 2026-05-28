<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SosCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $recipientId;
    public array $payload;

    public function __construct(int $recipientId, array $payload)
    {
        $this->recipientId = $recipientId;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sos.' . $this->recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'emergency.sos.cancelled';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
