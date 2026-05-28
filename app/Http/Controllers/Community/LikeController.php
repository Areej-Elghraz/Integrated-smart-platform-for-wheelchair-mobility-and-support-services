<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\ApiController;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends ApiController
{
    public function toggleLike(\App\Http\Requests\Community\ToggleLikeRequest $request, Post $post)
    {
        $user = $request->user();

        $like = $post->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            return $this->successResponse(
                message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.like.singular') ?? 'Like']),
                status: 200
            );
        } else {
            $post->likes()->create(['user_id' => $user->id]);

            if ($user->id !== $post->user_id) {
                broadcast(new \App\Events\PostLiked($post, $user));
                
                $postOwner = \App\Models\User::find($post->user_id);
                if ($postOwner) {
                    $postOwner->notify(new \App\Notifications\DatabaseNotification\PostInteractionNotification($user, 'liked', $post->id));
                }
            }

            return $this->successResponse(
                message: __('messages.actions.created_success', ['resource' => __('messages.resources.like.singular') ?? 'Like']),
                status: 201
            );
        }
    }

    public function toggleCommentLike(\App\Http\Requests\Community\ToggleLikeRequest $request, Comment $comment)
    {
        $user = $request->user();

        $like = $comment->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            return $this->successResponse(
                message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.like.singular') ?? 'Like']),
                status: 200
            );
        } else {
            $comment->likes()->create(['user_id' => $user->id]);
            return $this->successResponse(
                message: __('messages.actions.created_success', ['resource' => __('messages.resources.like.singular') ?? 'Like']),
                status: 201
            );
        }
    }
}