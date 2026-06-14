<?php

namespace App\Models;

use DateTimeInterface;


class Message extends BaseModel
{
    protected static function booted()
    {
        static::deleting(function ($message) {
            if ($message->getRawOriginal('attachment') && \Illuminate\Support\Facades\Storage::disk('public')->exists($message->getRawOriginal('attachment'))) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($message->getRawOriginal('attachment'));
            }
        });
    }

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'content',
        'attachment',
        'is_read',
        'deleted_by_sender',
        'deleted_by_receiver'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'deleted_by_sender' => 'boolean',
        'deleted_by_receiver' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
