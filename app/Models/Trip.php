<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trip extends BaseModel
{
    protected $fillable = [
        'wheelchair_id',
        'place_id',
        'mode',
        'status',
        'started_at',
        'ended_at',
        'start_x',
        'start_y',
        'end_x',
        'end_y',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function wheelchair(): BelongsTo
    {
        return $this->belongsTo(Wheelchair::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function movementState(): HasOne
    {
        return $this->hasOne(TripMovementState::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

}
