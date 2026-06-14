<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WheelchairEvent extends BaseModel
{
    protected $fillable = [
        'e_chair_id',
        'event_type',
        'event_data',
        'severity',
        'timestamp_ms',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

    public function eChair(): BelongsTo
    {
        return $this->belongsTo(EChair::class, 'e_chair_id');
    }
}
