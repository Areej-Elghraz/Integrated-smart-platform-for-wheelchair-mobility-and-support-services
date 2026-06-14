<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrentVitalState extends BaseModel
{
    use HasFactory;

    protected $table = 'current_vital_states';
    protected $primaryKey = 'wheelchair_id';
    public $incrementing = false;

    protected $fillable = [
        'wheelchair_id',
        'heart_rate',
        'heart_rate_status',
        'temperature',
        'temperature_status',
        'mpu_angle',
        'fall_status',
        'type',
        'risk_level',
        'reason',
        'recommendation',
    ];

    protected $casts = [
        'heart_rate' => 'double',
        'temperature' => 'double',
        'mpu_angle' => 'double',
        'fall_status' => 'boolean',
    ];

    public function wheelchair(): BelongsTo
    {
        return $this->belongsTo(Wheelchair::class);
    }
}
