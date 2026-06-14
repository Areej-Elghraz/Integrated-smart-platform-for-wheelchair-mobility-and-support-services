<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrentSafetyState extends BaseModel
{
    use HasFactory;

    protected $table = 'current_safety_states';
    protected $primaryKey = 'wheelchair_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'wheelchair_id',
        'fall_detected',
        'abnormal_posture',
        'sudden_motion_detected',
        'status',
        'updated_at',
    ];

    protected $casts = [
        'fall_detected' => 'boolean',
        'abnormal_posture' => 'boolean',
        'sudden_motion_detected' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public function wheelchair(): BelongsTo
    {
        return $this->belongsTo(Wheelchair::class);
    }
}
