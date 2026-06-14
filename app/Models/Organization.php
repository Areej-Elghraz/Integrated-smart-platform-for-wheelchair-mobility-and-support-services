<?php

namespace App\Models;

use App\Traits\HasOptionalRelations;
use App\Enums\UserRoleEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasInteractions;

class Organization extends BaseModel
{
    use HasFactory, HasOptionalRelations, HasInteractions;

    protected static function booted()
    {
        static::deleting(function ($organization) {
            if ($organization->getRawOriginal('image') && \Illuminate\Support\Facades\Storage::disk('public')->exists($organization->getRawOriginal('image'))) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($organization->getRawOriginal('image'));
            }
        });
    }

    protected $fillable = [
        'name',
        'owner_id',
        // 'category_id',
        'city_id',
        'country_id',
        'latitude',
        'longitude',
        'image',
        'rate',
        'description',
    ];

    protected $hidden = [
        'country_id',
        'city_id',
        'owner_id',
        'pivot',
    ];

    protected $casts = [
        'country_id' => 'integer',
        'city_id'    => 'integer',
        'owner_id'   => 'integer',
    ];

    public const ALLOWED_RELATIONS = [
        'owner',
        'categories',
        'categories.owner',
        'categories.parent',
        'categories.children',
        'categories.organizations',
        'categories.places',
        'country',
        'city',
        'reviews',
        'favoritedBy',
        'visitors',
        'comments',
        'buildings.organization',
        'buildings.floors',
        'buildings.floors.map',
        'buildings.floors.places',
    ];

    protected $appends = ['rating_distribution', 'top_reviews', 'is_favorite', 'top_visitors', 'visitorsCount', 'average_rating'];

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // public function scopeOwnedAccessible($query, User $user)
    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->isOrganization()) {
            $org = $user->organizationRoleOrganization();

            return $org
                ? $query->where('organizations.id', $org->id)
                : $query->whereRaw('1=0');
        }

        return $query->where(function ($q) use ($user) {
            $q->whereNull('organizations.owner_id')
                ->orWhere('organizations.owner_id', $user->id)
                ->orWhereHas('owner', function ($q) {
                    $q->where('role', UserRoleEnum::ORGANIZATION->value);
                });
        });
    }

    public function getImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    // public function scopeThroughCategoriesAccessible($query, User $user)
    // {
    //     return $query->whereHas('categories', function ($q) use ($user) {
    //         $q->accessibleBy($user);
    //     });
    // }

    // public function scopeAccessibleBy($query, User $user)
    // {
    //     return $query->where(function ($q) use ($user) {
    //         $q->ownedAccessible($user)
    //             ->orWhere(
    //                 fn($q) =>
    //                 $q->throughCategoriesAccessible($user)
    //             );
    //     });
    // }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // public function category(): BelongsTo
    // {
    //     return $this->belongsTo(Category::class, 'category_id');
    // }

    // public function categories(): HasMany
    // {
    //     return $this->hasMany(Category::class, 'organization_id');
    // }

    // public function category(): BelongsTo
    // {
    //     return $this->belongsTo(Category::class, 'category_id');
    // }

    // public function categories(): HasMany
    // {
    //     return $this->hasMany(Category::class);
    // }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'category_organization'
        );
    }

    // public function places(): HasMany
    // {
    //     return $this->hasMany(Place::class);
    // }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

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
     * Get the floors belonging to this organization.
     */
    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    /**
     * Get the buildings belonging to this organization.
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }
}
