<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Floor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'organization_id',
        'place_id',
    ];

    /**
     * Get the organization that owns the floor.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the place (building) that contains this floor.
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Get the map layout associated with the floor.
     */
    public function map(): HasOne
    {
        return $this->hasOne(Map::class);
    }

    /**
     * Get the places located on this floor.
     */
    public function places(): HasMany
    {
        return $this->hasMany(Place::class, 'floor_id');
    }
}
