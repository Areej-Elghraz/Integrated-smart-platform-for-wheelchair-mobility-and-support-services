<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripUpdate extends BaseModel
{
    protected $fillable = [
        'trip_id',
        'update_type',
        'source',
        'update_data',
        'timestamp_ms',
    ];

    protected $casts = [
        'update_data' => 'array',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
