<?php

namespace App\Models;

use App\Enums\UserRoleEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasInteractions;

class Place extends BaseModel
{
    use HasFactory, HasInteractions;

    protected static function booted()
    {
        static::deleting(function ($place) {
            if ($place->getRawOriginal('image') && \Illuminate\Support\Facades\Storage::disk('public')->exists($place->getRawOriginal('image'))) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($place->getRawOriginal('image'));
            }
        });
    }

    protected $fillable = [
        'name',
        'description',
        'parent_place_id',
        'image',
        'accessibility_data',
        'floor_id',
        'points',
        'x',
        'y',
        'z',
        'rotation',
    ];

    protected $with = ['floor', 'categories'];

    protected $hidden = [
        'owner_id',
        'parent_place_id',
        'pivot',
    ];

    protected $casts = [
        'accessibility_data' => 'array',
        'points'             => 'array',
        'owner_id'           => 'integer',
        'parent_place_id'    => 'integer',
        'floor_id'           => 'integer',
        'x'                  => 'double',
        'y'                  => 'double',
        'z'                  => 'double',
        'rotation'           => 'double',
    ];

    protected $appends = ['rating', 'rating_distribution', 'top_reviews', 'is_favorite', 'top_visitors', 'visitorsCount', 'average_rating', 'country', 'city'];

    public const ALLOWED_RELATIONS = [
        'owner',
        'categories',
        'categories.owner',
        'categories.parent',
        'categories.children',
        'categories.organizations',
        'categories.places',
        'reviews',
        'favoritedBy',
        'visitors',
        'comments',
        'parent',
        'children',
        'floor',
        'floor.map',
        'floor.building',
        'floor.building.organization',
    ];

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where('name', 'like', "%{$term}%");
    }

    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->isOrganization()) {
            $org = $user->organizationRoleOrganization();

            return $org
                ? $query->where('places.owner_id', $user->id)
                ->whereHas('floor.building', fn($q) => $q->where('organization_id', $org->id))
                : $query->whereRaw('1=0');
        }

        return $query->where(function ($q) use ($user) {
            $q->whereNull('places.owner_id')
                ->orWhere('places.owner_id', $user->id)
                ->orWhereHas('owner', function ($q) {
                    $q->where('role', UserRoleEnum::ORGANIZATION->value);
                });
        });
    }

    public function getImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_place');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'parent_place_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Place::class, 'parent_place_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get Country via Organization.
     */
    public function getCountryAttribute()
    {
        return $this->floor?->building?->organization?->country;
    }

    /**
     * Get City via Organization.
     */
    public function getCityAttribute()
    {
        return $this->floor?->building?->organization?->city;
    }

    // Protected by HasInteractions trait

    public function getRatingAttribute()
    {
        return $this->average_rating;
    }

    // public function getVisitorsCountAttribute(): int
    // {
    //     return $this->visitors()->count();
    // }

    public function getRatingDistributionAttribute()
    {
        $total = $this->reviews()->count();
        if ($total == 0) return [];

        $distribution = $this->reviews()
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $result = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = $distribution[$i] ?? 0;
            $result[$i] = round(($count / $total) * 100, 1) . '%';
        }
        return $result;
    }

    public function getTopReviewsAttribute()
    {
        return $this->reviews()
            ->with('user:id,name,image')
            ->orderByDesc('rating')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
    }

    public function getIsFavoriteAttribute(): bool
    {
        $user = auth('sanctum')->user();
        if (!$user) return false;
        return $this->favoritedBy()->where('user_id', $user->id)->exists();
    }

    public function getTopVisitorsAttribute()
    {
        return $this->visitors()
            ->select('users.id', 'users.name', 'users.image')
            ->orderBy('visitors.created_at', 'asc')
            ->limit(3)
            ->get()
            ->makeHidden('pivot');
    }

    /**
     * Get the floor that this place is located on.
     */
    public function floor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Floor::class, 'floor_id');
    }
}
