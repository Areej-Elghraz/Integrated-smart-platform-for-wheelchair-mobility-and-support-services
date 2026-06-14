<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
// use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PostService
{
    private function clearCache()
    {
        // Cache tags flush removed for database driver compatibility
    }

    public function getPosts(?User $currentUser, array $filters = [], array $with = []): array
    {
        $cacheKey = 'posts_' . md5(json_encode(['current_user' => $currentUser?->id, 'filters' => $filters, 'with' => $with]));

        return Cache::remember($cacheKey, 3600, function () use ($currentUser, $filters, $with) {
            $query = Post::with($with)
                ->withCount(['likes', 'comments', 'sharedPosts as shares_count'])
                ->when(empty($filters['user_id']) && $currentUser, function ($q) use ($currentUser) {
                    $q->whereDoesntHave('hiddenBy', function ($sub) use ($currentUser) {
                        $sub->where('user_id', $currentUser->id);
                    });
                })
                ->when($filters['user_id'] ?? null, function ($q, $userId) {
                    $q->where('user_id', $userId);
                })
                ->when($filters['content'] ?? null, function ($q, $content) {
                    $q->where('content', 'like', "%{$content}%");
                })
                ->when($filters['search'] ?? null, function ($q, $search) {
                    $q->where('content', 'like', "%{$search}%");
                })
                ->when($filters['created_from'] ?? null, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                ->when($filters['created_to'] ?? null, fn($q, $date) => $q->whereDate('created_at', '<=', $date))
                ->when(
                    $filters['sort_by'] ?? null,
                    function ($q, $sort) use ($filters) {
                        $dir = $filters['sort_direction'] ?? 'desc';
                        if (in_array($sort, ['created_at', 'likes_count', 'comments_count', 'shares_count'])) {
                            $q->orderBy($sort, $dir);
                        }
                    },
                    function ($q) {
                        $q->latest();
                    }
                );

            $paginator = $query->cursorPaginate($filters['pagination'] ?? 15);
            return [
                'posts'       => \App\Http\Resources\PostResource::collection($paginator->getCollection())->resolve(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ];
        });
    }

    public function getPost(Post $post): array
    {
        $post->load(['user:id,name,image', 'sharedPost.user:id,name,image'])
            ->loadCount(['likes', 'comments', 'sharedPosts as shares_count']);

        return (new \App\Http\Resources\PostResource($post))->resolve();
    }

    public function toggleHidePost(User $user, Post $post): bool
    {
        $isHidden = $user->hiddenPosts()->where('post_id', $post->id)->exists();

        if ($isHidden) {
            $user->hiddenPosts()->detach($post->id);
            $result = false;
        } else {
            $user->hiddenPosts()->attach($post->id);
            $result = true;
        }

        $this->clearCache();
        return $result;
    }

    public function createPost(User $user, array $data, array $imagePaths = [], array $filePaths = []): Post
    {
        $post = Post::create([
            'user_id' => $user->id,
            'content' => $data['content'] ?? null,
            'images'  => !empty($imagePaths) ? $imagePaths : null,
            'files'   => !empty($filePaths) ? $filePaths : null,
        ]);

        $this->clearCache();
        return $post;
    }

    public function updatePost(Post $post, array $data): Post
    {
        $post->update([
            'content' => $data['content'] ?? null,
        ]);

        $this->clearCache();
        return $post->load(['user:id,name,image', 'sharedPost.user:id,name,image']);
    }

    public function sharePost(User $user, Post $post, array $data): Post
    {
        $newPost = Post::create([
            'user_id'        => $user->id,
            'content'        => $data['content'] ?? null,
            'shared_post_id' => $post->id,
        ]);

        $this->clearCache();
        return $newPost;
    }

    public function deletePost(Post $post)
    {
        if ($post->images) {
            foreach ($post->images as $img) Storage::disk('public')->delete($img);
        }
        if ($post->files) {
            foreach ($post->files as $file) Storage::disk('public')->delete($file);
        }
        $post->delete();
        $this->clearCache();
    }
}
