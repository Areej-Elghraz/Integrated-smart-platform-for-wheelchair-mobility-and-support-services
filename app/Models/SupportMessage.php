<?php

namespace App\Models;

use DateTimeInterface;


class SupportMessage extends BaseModel
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
