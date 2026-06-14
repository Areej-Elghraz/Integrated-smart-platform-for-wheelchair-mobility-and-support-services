<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends BaseModel
{
    use HasFactory;

    protected $fillable = ['name'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
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
