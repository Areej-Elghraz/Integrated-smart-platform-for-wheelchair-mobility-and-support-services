<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyMonitoringLog extends BaseModel
{
    use HasFactory;

    protected $table = 'safety_monitoring_logs';

    protected $fillable = [
        'wheelchair_id',
        'user_id',
        'head_pitch',
        'head_roll',
        'chair_tilt_angle',
        'acceleration_force',
        'fall_detected',
        'abnormal_posture',
        'safety_status',
    ];

    protected $casts = [
        'head_pitch' => 'double',
        'head_roll' => 'double',
        'chair_tilt_angle' => 'double',
        'acceleration_force' => 'double',
        'fall_detected' => 'boolean',
        'abnormal_posture' => 'boolean',
    ];

    public function wheelchair(): BelongsTo
    {
        return $this->belongsTo(Wheelchair::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
