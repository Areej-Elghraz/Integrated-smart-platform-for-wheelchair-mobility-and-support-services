<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Organization;
use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    /**
     * Create a new category.
     */
    public function createCategory(?User $user, array $data, array $with = [], ?string $imagePath = null): array
    {
        $data['name'] = strtolower($data['name']);
        if (!isset($data['owner_id'])) {
            $data['owner_id'] = $user?->id;
        }
        if (!empty($imagePath)) {
            $data['image'] = $imagePath;
        }

        if (empty($data['parent_id']) && $user?->isOrganization()) {
            $orgForParent = $user->organizationRoleOrganization();
            if ($orgForParent) {
                // Get the main category for this organization (main categories have parent_id = null)
                $mainCategory = $orgForParent->categories()->whereNull('parent_id')->first();
                if ($mainCategory) {
                    $data['parent_id'] = $mainCategory->id;
                }
            }
        }
        // $category = Category::create(Arr::except($data, ['organization_id']));
        // if ($data['organization_id'] || $user->isOrganization()) {
        //     $org = $data['organization_id']
        //         ? Organization::find($data['organization_id'])
        //         : $user->organizationRoleOrganization();

        //     if ($org) {
        //         $category->organizations()->attach($org->id);
        //     }
        // }


        // هات الـ organization
        $org = null;
        if (!empty($data['organization_id'])) {
            $org = Organization::find($data['organization_id']);
        } elseif ($user?->isOrganization()) {
            $org = $user->organizationRoleOrganization();
        }

        // لو في organization → نعمل check قبل الـ create
        $existingCategoryQuery = Category::where('name', $data['name'])
            ->where('owner_id', $data['owner_id'])
            ->where('parent_id', $data['parent_id'] ?? null);

        if ($org) {
            $existingCategory = $existingCategoryQuery
                ->whereHas('organizations', function ($q) use ($org) {
                    $q->where('organizations.id', $org->id);
                })
                ->first();
        } else {
            $existingCategory = $existingCategoryQuery->first();
        }
        if ($existingCategory) {
            abort(409, __('messages.exceptions.category_already_exists'));
        }

        // مش موجودة → نعمل create
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $org, $user, $with) {
            $category = Category::create(
                Arr::except($data, ['organization_id'])
            );

            // Attach organization
            if ($org) {
                $category->organizations()->syncWithoutDetaching([$org->id]);
            }

            // Clear cache
            $this->clearCache($user);

            return $category->load($with ?? [])->toArray();
        });
    }

    /**
     * Update a category.
     */
    public function updateCategory(Category $category, User $user, array $data, array $with = [], ?string $imagePath = null): array
    {
        if (isset($data['name']) && !empty($data['name'])) {
            $data['name'] = strtolower($data['name']);
        }
        if (!empty($imagePath)) {
            $data['image'] = $imagePath;
        }
        $category->update(attributes: Arr::except($data, ['organization_id']));
        if (isset($data['organization_ids']) && !empty($data['organization_ids'])) {
            $category->organizations()->sync($data['organization_ids']);
        }
        //     /* =======================
        //  *  Authorization
        //  * ======================= */

        //     if ($user->isOrganization()) {
        //         $org = $user->organizationRoleOrganization();

        //         $allowed =
        //             $category->owner_id === $user->id ||
        //             $category->organizations()->where('organizations.id', $org->id)->exists();

        //         if (! $allowed) {
        //             abort(403, 'You are not allowed to update this category.');
        //         }
        //     } else {
        //         // user role user
        //         if ($category->owner_id !== $user->id) {
        //             abort(403, 'You are not allowed to update this category.');
        //         }
        //     }

        /* =======================
         * Determine organization
         * ======================= */

        $orgIds = $data['organization_ids']
            ?? $category->organizations()->pluck('organizations.id')->toArray();


        /* =======================
         * Update category
         * ======================= */

        return \Illuminate\Support\Facades\DB::transaction(function () use ($category, $user, $data, $orgIds, $with) {
            /* =======================
             * Update category
             * ======================= */
            $category->update(
                Arr::except($data, ['organization_ids'])
            );

            /* =======================
             * Sync organizations
             * ======================= */
            if (isset($data['organization_ids']) && !empty($data['organization_ids'])) {
                $category->organizations()->sync($orgIds);
            }

            /* =======================
             * Clear cache
             * ======================= */
            foreach ($orgIds as $orgId) {
                $this->clearCache($user);
            }
            return $category->load($with)->toArray();
        });
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(Category $category)
    {
        if ($category->image && Storage::disk('public')->exists($category->getRawOriginal('image'))) {
            Storage::disk('public')->delete($category->getRawOriginal('image'));
        }
        $category->delete();
        $this->clearCache($category->owner);
    }

    /**
     * Get categories tree.
     */
    public function getCategoriesTree(User $user, array $filters = [], array $with = []): array
    {

        // dd(
        //     Category::count(),
        //     Category::whereNull('owner_id')->count(),
        //     Category::whereNotNull('owner_id')->count()
        // );
        $cacheKey = 'categories_' . $user->id . '_' . md5(json_encode([
            'filters' => $filters,
            'with' => $with,
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($user, $filters, $with) {
            $query = Category::accessibleBy($user)->with($with ?: []);
            if ($user->isOrganization()) {

                $org = $user->organizationRoleOrganization();

                if (!$org) { /// 
                    return collect();
                }

                $query->where('owner_id', $user->id)
                    ->whereHas('organizations', function ($q) use ($org) {
                        $q->where('organizations.id', $org->id);
                    });
            } else {
                $query->where(function ($q) use ($user) {

                    $q->whereNull('owner_id') // main places

                        ->orWhere('owner_id', $user->id) // created by the current user

                        ->orWhereHas('owner', function ($q) {
                            $q->where('role', UserRoleEnum::ORGANIZATION->value); // created by any organization
                        });
                });
            }

            $query
                ->when(
                    !empty($filters['main_only']),
                    fn($q) =>
                    $q->whereNull('parent_id')
                )
                ->when(
                    $filters['organization_id'] ?? null,
                    fn($q, $orgId) =>
                    $q->whereHas(
                        'organizations',
                        fn($q) =>
                        $q->where('organizations.id', $orgId)
                    )
                )
                ->when(
                    $filters['parent_id'] ?? null,
                    fn($q, $parentId) =>
                    $q->where('parent_id', $parentId)
                )
                ->when(
                    !empty($filters['has_organizations']),
                    fn($q) =>
                    $q->has('organizations', '>=', 1)
                )
                // ->when(
                //     $filters['owner_id'] ?? null,
                //     fn($q, $ownerId) =>
                //     $q->where('owner_id', $ownerId)
                // )
                ->when(
                    !empty($filters['has_places']),
                    fn($q) =>
                    $q->has('places')
                )
                ->when(
                    $filters['country_id'] ?? null,
                    fn($q, $countryId) =>
                    $q->whereHas(
                        'organizations',
                        fn($q) =>
                        $q->where('country_id', $countryId)
                    )
                )
                ->when(
                    $filters['city_id'] ?? null,
                    fn($q, $cityId) =>
                    $q->whereHas(
                        'organizations',
                        fn($q) =>
                        $q->where('city_id', $cityId)
                    )
                )
                ->when(
                    $filters['created_from'] ?? null,
                    fn($q, $date) =>
                    $q->whereDate('created_at', '>=', $date)
                )
                ->when(
                    $filters['created_to'] ?? null,
                    fn($q, $date) =>
                    $q->whereDate('created_at', '<=', $date)
                )
                ->when(
                    $filters['min_places'] ?? null,
                    fn($q, $count) =>
                    $q->has('places', '>=', $count)
                )
                ->when(
                    $filters['min_organizations'] ?? null,
                    fn($q, $count) =>
                    $q->has('organizations', '>=', $count)
                )
                ->when(
                    $filters['sort_by'] ?? null,
                    function ($q, $sortBy) use ($filters) {

                        $direction = $filters['sort_direction'] ?? 'asc';

                        $allowed = ['name', 'created_at'];

                        if (in_array($sortBy, $allowed)) {
                            $q->orderBy($sortBy, $direction);
                        }
                    }
                );
            if (!empty($filters['pagination'])) {
                return $query->cursorPaginate($filters['pagination'] ?? 20);
            }
            if (!empty($filters['limit'])) {
                $query->limit($filters['limit']);
            }
            return $query->search($filters['search'] ?? null)->get()->toArray();
        });
    }
    // public function getCategoriesTree(User $user, $organizationId = null, $parentId = null, array $with = [])
    // {
    //     // $cacheKey = $organizationId
    //     //     ? "org_{$organizationId}_categories"
    //     //     : "user_{$user->id}_main_categories";

    //     $cacheKey = implode('_', [
    //         'categories',
    //         'user_' . $user->id,
    //         // 'role_' . $user->role,
    //         // 'org_' . ($organizationId ?? 'none'),
    //         // 'parent_' . ($parentId ?? 'root'),
    //     ]);
    //     // dd(Category::withOptionalRelations($with)->get());
    //     // dd(Category::with('children')->where('organization_id', $organizationId)->get());
    //     // dd($organizationId, $parentId);
    //     return Cache::remember($cacheKey, 3600, function () use ($user, $organizationId, $parentId, $with) {
    //         $query = Category::withOptionalRelations($with);
    //         if ($organizationId || $user->isOrganization()) {
    //             $org = $organizationId
    //                 ? Organization::find($organizationId)
    //                 : $user->organizationRoleOrganization();

    //             if ($org) {
    //                 $query->whereHas('organizations', function ($q) use ($org) {
    //                     $q->where('organizations.id', $org->id);
    //                 });
    //             }
    //             // $org = $user->organizationRoleOrganization();
    //             // $query->where('organization_id', ($org ?? false) ? $org->id : $organizationId);
    //         } else {
    //             $query->where(function ($q) use ($user) {
    //                 // shared main categories
    //                 $q->where(function ($q) {
    //                     $q->whereNull('owner_id')
    //                         ->doesntHave('organizations');
    //                 })

    //                     // categories added by any organization
    //                     ->orWhereHas('organizations')

    //                     // categories added by this user
    //                     ->orWhere(function ($q) use ($user) {
    //                         $q->where('owner_id', $user->id)
    //                             ->doesntHave('organizations');
    //                     });
    //             });
    //         }
    //         return $query->where('parent_id', $parentId)->get();
    //         // return $query->get();
    //     });
    // }

    /**
     * Get a single category.
     */
    public function getCategory($id, array $with = []): Category
    {
        return Category::with($with)->findOrFail($id);
    }

    /**
     * Clear category cache.
     */
    // public function clearCache(User $user, $organizationId = null, $parentId = null)
    public function clearCache(?User $user)
    {
        if ($user?->isOrganization()) {
            // Cache tags flush removed for database driver compatibility
        }
        if ($user) {
            // Cache tags flush removed for database driver compatibility
        } else {
            // Cache tags flush removed for database driver compatibility
        }
    }
}

