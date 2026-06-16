<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Wheelchair extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'serial_number',
        'api_key',
        'connection_state',
        'x_coordinate',
        'y_coordinate',
        'theta',
        'current_floor_id',
    ];

    protected $casts = [
        'connection_state' => 'string',
        'theta' => 'double',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - used by Handshake API.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function aiRecommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'current_floor_id');
    }

    public function movementState(): HasOne
    {
        return $this->hasOne(WheelchairMovementState::class);
    }
}
