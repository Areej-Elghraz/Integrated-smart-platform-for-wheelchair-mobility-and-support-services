<?php

namespace App\Notifications\DatabaseNotification;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;

class ConnectionRequestAcceptedNotification extends Notification
{
    use Queueable;

    public $accepter;
    public $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $accepter, string $type)
    {
        $this->accepter = $accepter;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'connection_request_accepted',
            'connection_type' => $this->type,
            'sender_id' => $this->accepter->id,
            'sender_name' => $this->accepter->name,
            'sender_image' => $this->accepter->image,
            'user' => [
                'id' => $this->accepter->id,
                'name' => $this->accepter->name,
                'username' => $this->accepter->username,
                'image' => $this->accepter->image,
            ],
            'message' => "Your {$this->type} connection request was accepted by {$this->accepter->name}.",
        ];
    }
}
