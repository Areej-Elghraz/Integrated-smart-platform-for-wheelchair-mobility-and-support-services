<?php

namespace App\Models;

use DateTimeInterface;


class Post extends BaseModel
{
    protected static function booted()
    {
        static::deleting(function ($post) {
            $images = $post->getRawOriginal('images');
            if ($images) {
                $filesArray = json_decode($images, true);
                if (is_array($filesArray)) {
                    foreach ($filesArray as $file) {
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($file)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($file);
                        }
                    }
                }
            }

            $files = $post->getRawOriginal('files');
            if ($files) {
                $filesArray = json_decode($files, true);
                if (is_array($filesArray)) {
                    foreach ($filesArray as $file) {
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($file)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($file);
                        }
                    }
                }
            }
        });
    }

    protected $fillable = [
        'user_id',
        'content',
        'images',
        'files',
        'shared_post_id'
    ];

    protected $casts = [
        'images' => 'array',
        'files' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sharedPost()
    {
        return $this->belongsTo(Post::class, 'shared_post_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id');
    }

    public function sharedPosts()
    {
        return $this->hasMany(Post::class, 'shared_post_id');
    }

    public function hiddenBy()
    {
        return $this->belongsToMany(User::class, 'hidden_posts')->withTimestamps();
    }

    public function getImagesAttribute($value)
    {
        if (!$value)
            return [];
        $images = is_array($value) ? $value : json_decode($value, true);
        return array_map(fn($img) => asset('storage/' . $img), $images);
    }

    public function getFilesAttribute($value)
    {
        if (!$value)
            return [];
        $files = is_array($value) ? $value : json_decode($value, true);
        return array_map(fn($file) => asset('storage/' . $file), $files);
    }
}
