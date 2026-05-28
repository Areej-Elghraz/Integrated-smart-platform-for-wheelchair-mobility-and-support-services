<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Map extends Model
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
        'origin',
        'resolution',
        'mode',
        'negate',
        'occupied_thresh',
        'free_thresh',
    ];

    protected $casts = [
        'origin' => 'array',
        'yaml_data' => 'array',
        'width' => 'double',
        'height' => 'double',
        'resolution' => 'double',
        'occupied_thresh' => 'double',
        'free_thresh' => 'double',
        'negate' => 'integer',
    ];

    /**
     * Get the floor that owns the map.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
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
