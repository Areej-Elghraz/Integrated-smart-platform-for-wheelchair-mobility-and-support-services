<?php

namespace App\Models;



class DecisionTraceLog extends BaseModel
{
    protected $fillable = [
        'event_type',
        'event_id',
        'decisions',
        'reasoning',
        'latency_ms',
        'timestamp_ms',
    ];

    protected $casts = [
        'decisions' => 'array',
    ];
}
