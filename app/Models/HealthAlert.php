<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthAlert extends BaseModel
{
    protected $fillable = [
        'health_prediction_id',
        'user_id',
        'trip_id',
        'status',
        'severity',
        'timestamp_ms',
    ];

    public function prediction(): BelongsTo
    {
        return $this->belongsTo(HealthPrediction::class, 'health_prediction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
