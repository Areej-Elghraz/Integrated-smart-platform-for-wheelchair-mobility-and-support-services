<?php

namespace App\Models;

use DateTimeInterface;


class Comment extends BaseModel
{
    protected $fillable = ['user_id', 'commentable_id', 'commentable_type', 'content', 'parent_id'];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}
