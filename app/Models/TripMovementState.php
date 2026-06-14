<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripMovementState extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'movement_status',
        'speed',
        'position',
        'theta',
        'mode',
        'risk_level',
        'obstacle_detected',
        'obstacle_distance',
    ];

    protected $casts = [
        'position' => 'array',
        'obstacle_detected' => 'boolean',
        'speed' => 'double',
        'theta' => 'double',
        'obstacle_distance' => 'double',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
