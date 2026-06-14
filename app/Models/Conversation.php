<?php

namespace App\Models;

use DateTimeInterface;


class Conversation extends BaseModel
{
    protected $fillable = ['user_one_id', 'user_two_id', 'deleted_by_user_one', 'deleted_by_user_two'];

    protected $casts = [
        'deleted_by_user_one' => 'boolean',
        'deleted_by_user_two' => 'boolean',
    ];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
