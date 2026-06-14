<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthPrediction extends BaseModel
{
    protected $fillable = [
        'e_chair_id',
        'prediction_type',
        'confidence',
        'is_critical',
        'source_model',
        'prediction_window_ms',
        'details',
        'timestamp_ms',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
        'details' => 'array',
    ];

    public function eChair(): BelongsTo
    {
        return $this->belongsTo(EChair::class, 'e_chair_id');
    }
}
