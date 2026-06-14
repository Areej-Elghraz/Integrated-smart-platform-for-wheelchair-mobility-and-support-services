<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyEvent extends BaseModel
{
    protected $fillable = [
        'e_chair_id',
        'trip_id',
        'event_type',
        'source_classification',
        'location',
        'severity',
        'resolved_at',
        'timestamp_ms',
    ];

    protected $casts = [
        'location' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function eChair(): BelongsTo
    {
        return $this->belongsTo(EChair::class, 'e_chair_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
