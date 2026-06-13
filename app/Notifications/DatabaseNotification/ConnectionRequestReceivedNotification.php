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

    /**
     * Create a new notification instance.
     */
    public function __construct(User $sender, string $type)
    {
        $this->sender = $sender;
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
            'type' => 'connection_request_received',
            'connection_type' => $this->type,
            'user' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'username' => $this->sender->username,
            ],
            'message' => "You have received a new {$this->type} connection request from {$this->sender->name}.",
        ];
    }
}
