<?php

namespace App\Notifications\DatabaseNotification;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MessageSentDbNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'chat_message',
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'sender_image' => $this->message->sender->image,
            'message' => "{$this->message->sender->name} sent you a message.",
        ];
    }
}