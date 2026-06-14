<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Floor extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'building_id',
    ];

    protected $with = ['building'];

    public const ALLOWED_RELATIONS = [
        'building',
        'building.organization',
        'map',
        'places',
    ];

    /**
     * Get the building that contains this floor.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where('name', 'like', "%{$term}%");
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
