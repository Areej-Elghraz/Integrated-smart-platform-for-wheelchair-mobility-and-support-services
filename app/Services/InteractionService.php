<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use App\Models\Review;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class InteractionService
{
    /**
     * Add a review to a model.
     */
    public function addReview(User $user, Model $model, array $data): Model
    {
        // dd($data, $user, $model);
        $data['user_id'] = $user->id;
        $review = $model->reviews()->create($data);
        $this->clearCache($user, $model);
        return $review;
    }

    /**
     * Delete a review from a model.
     */
    public function deleteReview(User $user, Review $review): void
    {
        $model = $review->reviewable;
        $review->delete();
        if ($model) {
            $this->clearCache($user, $model);
        }
    }

    /**
     * Toggle favorite status for a model.
     */
    public function toggleFavorite(User $user, Model $model): bool
    {
        $relation = $this->getFavoriteRelation($model);
        $isFavorited = $user->{$relation}()->where('favoritable_id', $model->id)->exists();

        if ($isFavorited) {
            $user->{$relation}()->detach($model->id);
            $result = false;
        } else {
            $user->{$relation}()->attach($model->id);
            $result = true;
        }

        $this->clearCache($user, $model);
        return $result;
    }

    public function addComment(User $user, Model $model, string $content, ?int $parentId = null): Model
    {
        if ($parentId) {
            $comment = Comment::find($parentId);
            while ($comment && $comment->parent_id) {
                $comment = $comment->parent;
            }
            $parentId = $comment?->id;
        }
        $comment = $model->comments()->create([
            'user_id'   => $user->id,
            'content'   => $content,
            'parent_id' => $parentId,
        ]);
        $this->clearCache($user, $model);
        return $comment;
    }

    /**
     * Get paginated comments for a model.
     */
    public function getComments(Model $model, array $filters = []): array
    {
        $entityType = strtolower(class_basename($model));
        $cacheKey = "{$entityType}_{$model->id}_comments_" . md5(json_encode($filters));

        return Cache::remember($cacheKey, 3600, function () use ($model, $filters) {
            $query = \App\Models\Comment::query()
                ->where('commentable_id', $model->id)
                ->where('commentable_type', get_class($model))
                ->with(['user:id,name,image'])
                ->withCount(['likes', 'replies'])
                ->when(array_key_exists('parent_id', $filters), function ($q) use ($filters) {
                    $q->where('parent_id', $filters['parent_id']);
                }, function ($q) {
                    $q->whereNull('parent_id');
                })
                ->when(!empty($filters['search']), function ($q) use ($filters) {
                    $q->where('content', 'like', "%{$filters['search']}%");
                })
                ->when($filters['sort_by'] ?? null, function ($q, $sort) use ($filters) {
                    $dir = $filters['sort_direction'] ?? 'asc';
                    if (in_array($sort, ['created_at', 'likes_count', 'replies_count'])) {
                        $q->orderBy($sort, $dir);
                    }
                }, function ($q) {
                    $q->latest();
                });

            $paginator = $query->cursorPaginate($filters['pagination'] ?? 15);

            return [
                'comments'    => \App\Http\Resources\CommentResource::collection($paginator->getCollection())->resolve(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ];
        });
    }

    /**
     * Record a visit to a model.
     */
    public function recordVisit(User $user, Model $model): void
    {
        $model->visitors()->syncWithoutDetaching([$user->id]);
        $this->clearCache($user, $model);
    }

    /**
     * Clear relevant caches.
     */
    protected function clearCache(User $user, Model $model): void
    {
        $entityType = strtolower(class_basename($model));
        $tags = [$entityType . 's', 'user_' . $user->id];

        // Cache tags flush removed for database driver compatibility
        // Cache tags flush removed for database driver compatibility
    }

    /**
     * Get the correct favorite relation name on the User model.
     */
    protected function getFavoriteRelation(Model $model): string
    {
        $basename = class_basename($model);
        return 'favorite' . $basename . 's'; // e.g., favoritePlaces, favoriteOrganizations
    }
}

