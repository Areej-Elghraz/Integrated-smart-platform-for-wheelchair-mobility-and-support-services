<?php

namespace App\Models;



class ConnectionRequest extends BaseModel
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'connection_type',
        'status',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
