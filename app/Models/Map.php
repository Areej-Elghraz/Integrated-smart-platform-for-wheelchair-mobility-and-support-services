<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Map extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'floor_id',
        'map_file',
        'yaml_file',
        'yaml_data',
        'extension',
        'width',
        'height',
    ];

    protected $casts = [
        'yaml_data' => 'array',
        'width' => 'double',
        'height' => 'double',
    ];

    /**
     * Get the floor that owns the map.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Get the places that owns the map.
     */
    public function places(): HasMany
    {
        return $this->hasMany(place::class);
    }

    /**
     * Get the map file full asset path.
     */
    public function getMapFileAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    /**
     * Get the yaml file full asset path.
     */
    public function getYamlFileAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
}
