<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class AuditLog extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'details' => 'array',
    ];
}
