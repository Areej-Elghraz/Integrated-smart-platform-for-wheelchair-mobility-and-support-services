<?php

namespace App\Models;



class IncidentReport extends BaseModel
{
    protected $fillable = [
        'event_type',
        'event_id',
        'description',
        'severity',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
