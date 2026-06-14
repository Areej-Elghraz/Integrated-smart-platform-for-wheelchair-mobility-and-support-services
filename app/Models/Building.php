<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Building extends BaseModel
{
    use HasFactory;

    protected static function booted()
    {
        static::deleting(function ($building) {
            if ($building->getRawOriginal('image') && \Illuminate\Support\Facades\Storage::disk('public')->exists($building->getRawOriginal('image'))) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($building->getRawOriginal('image'));
            }
        });
    }

    public function getImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    protected $fillable = [
        'name',
        'description',
        'organization_id',
        'owner_id',
        'image',
        'latitude',
        'longitude',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    public function places(): HasManyThrough
    {
        return $this->hasManyThrough(Place::class, Floor::class);
    }
}
