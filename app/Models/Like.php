<?php

namespace App\Models;

use DateTimeInterface;


class Like extends BaseModel
{
    protected $fillable = ['user_id', 'likeable_id', 'likeable_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likeable()
    {
        return $this->morphTo();
    }
}
