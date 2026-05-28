<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Wheelchair extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'serial_number',
        'battery',
        'voltage',
        'current',
        'temperature',
        'connection_state',
        'x_coordinate',
        'y_coordinate',
    ];

    protected $casts = [
        'battery' => 'double',
        'voltage' => 'double',
        'current' => 'double',
        'temperature' => 'double',
        'connection_state' => 'string',
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
}
