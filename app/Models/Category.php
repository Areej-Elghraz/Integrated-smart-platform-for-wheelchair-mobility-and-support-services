<?php

namespace App\Models;

use App\Traits\HasOptionalRelations;
use App\Enums\UserRoleEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends BaseModel
{
    use HasFactory, HasOptionalRelations;

    protected static function booted()
    {
        static::deleting(function ($category) {
            if ($category->getRawOriginal('image') && \Illuminate\Support\Facades\Storage::disk('public')->exists($category->getRawOriginal('image'))) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($category->getRawOriginal('image'));
            }
        });
    }

    protected $fillable = [
        'name',
        'parent_id',
        'owner_id',
        'image',
    ];

    protected $hidden = [
        'parent_id',
        'owner_id',
        'pivot',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'owner_id'  => 'integer',
    ];

    public const ALLOWED_RELATIONS = [
        'owner',
        'parent',
        'children',
        'organizations',
        'organizations.owner',
        'organizations.categories',
        'organizations.country',
        'organizations.city',
        'places',
        'places.owner',
        'places.categories',
        'places.reviews',
        'places.favoritedBy',
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
                ? $query->where(function ($q) use ($user, $org) {
                    $q->where(function ($sub) use ($user, $org) {
                        $sub->where('categories.owner_id', $user->id)
                            ->whereHas('organizations', function ($o) use ($org) {
                                $o->where('organizations.id', $org->id);
                            });
                    });
                })
                : $query->whereRaw('1=0');
        }

        return $query->where(function ($q) use ($user) {
            $q->whereNull('categories.owner_id')
                ->orWhere('categories.owner_id', $user->id)
                ->orWhereHas('owner', function ($q) {
                    $q->where('role', UserRoleEnum::ORGANIZATION->value);
                })
            ;
        });
    }

    public function getImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    // public function scopeThroughCategoriesAccessible($query, User $user)
    // {
    //     return $query->whereHas('organizations', function ($q) use ($user) {
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // public function organization(): BelongsTo
    // {
    //     return $this->belongsTo(Organization::class);
    // }

    // public function organizations(): HasMany
    // {
    //     return $this->hasMany(Organization::class);
    // }

    // public function organizations(): BelongsToMany
    // {
    //     return $this->belongsToMany(Organization::class, 'category_organization');
    // }

    // public function organization(): BelongsTo
    // {
    //     return $this->belongsTo(Organization::class, 'organization_id');
    // }

    // public function organizations(): HasMany
    // {
    //     return $this->hasMany(Organization::class, 'category_id');
    // }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'category_organization'
        );
    }
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'category_place');
    }

    // /**
    //  * Get Country via Organization.
    //  */
    // public function country()
    // {
    //     // dd($this->organizations()->count());
    //     $orgs = $this->organizations();
    //     return $orgs->count() ? $orgs->with('country')->first()->country : null;
    // }

    // /**
    //  * Get City via Organization.
    //  */
    // public function city()
    // {
    //     $orgs = $this->organizations();
    //     return $orgs->count() ? $orgs->with('city')->first()->city : null;
    // }

    // public function getCountryAttribute()
    // {
    //     return optional($this->organizations()->with('country')->first())->country;
    // }

    // public function getCityAttribute()
    // {
    //     return optional($this->organizations()->with('city')->first())->city;
    // }

    public function scopeMain($query)
    {
        return $query->whereNull('parent_id')->whereNull('organization_id');
    }

    public function scopeOrganizationCategories($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId)->whereNull('parent_id');
    }

    public function scopeSubCategories($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }
}
