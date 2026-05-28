<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function broadcastOn(): array
    {
        // Broadcast to public channel or to friends? 
        // As per requirements: posts are pushed via reverb. 
        // Usually, to a community channel.
        return [
            new \Illuminate\Broadcasting\Channel('community.posts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.created';
    }
}
