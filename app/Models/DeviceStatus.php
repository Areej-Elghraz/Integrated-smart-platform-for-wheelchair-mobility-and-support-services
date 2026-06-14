<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceStatus extends BaseModel
{
    protected $table = 'device_status';

    protected $fillable = [
        'e_chair_id',
        'device_name',
        'connection_status',
        'firmware_version',
        'last_heartbeat',
    ];

    protected $casts = [
        'last_heartbeat' => 'datetime',
    ];

    public function eChair(): BelongsTo
    {
        return $this->belongsTo(EChair::class, 'e_chair_id');
    }
}
