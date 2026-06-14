<?php

namespace App\Models;

use DateTime;
use DateTimeInterface;


class Favorite extends BaseModel
{
    protected $fillable = ['user_id', 'favoritable_id', 'favoritable_type'];

    public function favoritable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
