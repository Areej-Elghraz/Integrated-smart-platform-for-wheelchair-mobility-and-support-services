<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $acceptor;
    public $senderId;

    public function __construct(User $acceptor, int $senderId)
    {
        $this->acceptor = $acceptor;
        $this->senderId = $senderId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->senderId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'friend.request.accepted';
    }
}
