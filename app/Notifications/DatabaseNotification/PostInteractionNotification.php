<?php

namespace App\Notifications\DatabaseNotification;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostInteractionNotification extends Notification
{
    use Queueable;

    protected $actor;
    protected $actionType; // 'liked', 'commented', 'shared'
    protected $postId;

    public function __construct(User $actor, string $actionType, int $postId)
    {
        $this->actor = $actor;
        $this->actionType = $actionType;
        $this->postId = $postId;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'post_interaction',
            'action' => $this->actionType,
            'post_id' => $this->postId,
            'sender_id' => $this->actor->id,
            'sender_name' => $this->actor->name,
            'sender_image' => $this->actor->image,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'message' => "{$this->actor->name} {$this->actionType} your post.",
        ];
    }
}
