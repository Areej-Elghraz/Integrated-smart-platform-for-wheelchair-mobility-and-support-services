<?php

namespace App\Notifications\DatabaseNotification;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRequestReceivedNotification extends Notification
{
    use Queueable;

    protected $sender;
    protected $friendship_id;

    public function __construct(User $sender, $friendship_id = null)
    {
        $this->sender = $sender;
        $this->friendship_id = $friendship_id;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'friend_request_received',
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'sender_image' => $this->sender->image,
            'friendship_id' => $this->friendship_id,
            'message' => "{$this->sender->name} sent you a connection request.",
        ];
    }
}