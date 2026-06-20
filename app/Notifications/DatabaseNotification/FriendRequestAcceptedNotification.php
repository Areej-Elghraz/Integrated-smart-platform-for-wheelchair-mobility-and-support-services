<?php

namespace App\Notifications\DatabaseNotification;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRequestAcceptedNotification extends Notification
{
    use Queueable;

    protected $acceptor;

    public function __construct(User $acceptor)
    {
        $this->acceptor = $acceptor;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'friend_request_accepted',
            'sender_id' => $this->acceptor->id,
            'sender_name' => $this->acceptor->name,
            'sender_image' => $this->acceptor->image,
            'acceptor_id' => $this->acceptor->id,
            'acceptor_name' => $this->acceptor->name,
            'message' => "{$this->acceptor->name} accepted your friend request.",
        ];
    }
}
