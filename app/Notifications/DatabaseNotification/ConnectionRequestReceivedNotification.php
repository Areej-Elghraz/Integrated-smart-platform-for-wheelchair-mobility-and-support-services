<?php

namespace App\Notifications\DatabaseNotification;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;

class ConnectionRequestReceivedNotification extends Notification
{
    use Queueable;

    public $sender;
    public $type;
    public $connectionRequestId;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $sender, string $type, $connectionRequestId = null)
    {
        $this->sender = $sender;
        $this->type = $type;
        $this->connectionRequestId = $connectionRequestId;
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
            'type' => 'connection_request_received',
            'connection_type' => $this->type,
            'connection_id' => $this->connectionRequestId,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'sender_image' => $this->sender->image,
            'user' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'username' => $this->sender->username,
                'image' => $this->sender->image,
            ],
            'message' => "You have received a new {$this->type} connection request from {$this->sender->name}.",
        ];
    }
}
