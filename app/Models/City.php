<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_id'
    ];

    protected $hidden = [];

    protected $casts = ['country_id' => 'integer'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }
}
