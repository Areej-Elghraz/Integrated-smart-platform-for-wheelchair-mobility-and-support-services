<?php

namespace App\Models;

use DateTimeInterface;


class Visitor extends BaseModel
{
    protected $fillable = ['user_id', 'visitable_id', 'visitable_type'];

    public function visitable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
