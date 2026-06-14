<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends BaseModel
{
    use HasFactory;

    protected static function booted()
    {
        static::deleting(function ($message) {
            $attachments = $message->getRawOriginal('attachments');
            if ($attachments) {
                $files = json_decode($attachments, true);
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($file)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($file);
                        }
                    }
                }
            }
        });
    }

    protected $fillable = [
        'chat_session_id',
        'sender_type',
        'content',
        'attachments',
        'reaction',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }
}
