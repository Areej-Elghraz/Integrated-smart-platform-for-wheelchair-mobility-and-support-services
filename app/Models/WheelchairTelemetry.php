<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WheelchairTelemetry extends BaseModel
{
    protected $table = 'wheelchair_telemetry';

    protected $fillable = [
        'e_chair_id',
        'position_data',
        'speed',
        'battery_level',
        'heading',
        'status',
        'timestamp_ms',
    ];

    protected $casts = [
        'position_data' => 'array',
    ];

    public function eChair(): BelongsTo
    {
        return $this->belongsTo(EChair::class, 'e_chair_id');
    }
}
