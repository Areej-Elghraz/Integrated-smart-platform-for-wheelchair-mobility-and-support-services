<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WheelchairSensor extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'wheelchair_id',
        'sensor_type',
        'sensor_name',
        'firmware_version',
        'status',
        'last_reading_time',
    ];

    protected $casts = [
        'last_reading_time' => 'datetime',
    ];

    public function wheelchair(): BelongsTo
    {
        return $this->belongsTo(Wheelchair::class);
    }
}
