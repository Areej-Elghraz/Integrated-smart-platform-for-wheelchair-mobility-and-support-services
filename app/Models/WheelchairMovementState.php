<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WheelchairMovementState extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'wheelchair_id',
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

    public function wheelchair(): BelongsTo
    {
        return $this->belongsTo(Wheelchair::class);
    }
}
