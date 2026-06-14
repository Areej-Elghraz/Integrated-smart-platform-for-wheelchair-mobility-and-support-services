<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'wheelchair_id',
        'trip_id',
        'heart_rate_min',
        'heart_rate_max',
        'heart_rate_avg',
        'temperature_min',
        'temperature_max',
        'temperature_avg',
        'mpu_angle_min',
        'mpu_angle_max',
        'mpu_angle_avg',
        'reading_time',
    ];

    protected $casts = [
        'heart_rate_min'  => 'double',
        'heart_rate_max'  => 'double',
        'heart_rate_avg'  => 'double',
        'temperature_min' => 'double',
        'temperature_max' => 'double',
        'temperature_avg' => 'double',
        'mpu_angle_min'   => 'double',
        'mpu_angle_max'   => 'double',
        'mpu_angle_avg'   => 'double',
        'reading_time'    => 'datetime',
    ];

    public function wheelchair(): BelongsTo
    {
        return $this->belongsTo(Wheelchair::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
