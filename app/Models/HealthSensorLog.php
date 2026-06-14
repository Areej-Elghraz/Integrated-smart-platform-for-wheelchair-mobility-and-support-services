<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthSensorLog extends BaseModel
{
    protected $fillable = [
        'e_chair_id',
        'type',
        'value',
        'unit',
        'sensor_status',
        'timestamp_ms',
    ];

    public function eChair(): BelongsTo
    {
        return $this->belongsTo(EChair::class, 'e_chair_id');
    }
}
