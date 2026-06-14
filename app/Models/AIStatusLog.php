<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIStatusLog extends BaseModel
{
    protected $fillable = [
        'e_chair_id',
        'event_type',
        'component_name',
        'message',
        'decision_context',
        'details',
        'timestamp_ms',
    ];

    protected $casts = [
        'decision_context' => 'array',
        'details' => 'array',
    ];

    public function eChair(): BelongsTo
    {
        return $this->belongsTo(EChair::class, 'e_chair_id');
    }
}
