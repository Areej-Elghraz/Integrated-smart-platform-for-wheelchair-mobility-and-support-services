<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Organization;
use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class OrganizationService
{
    protected $geoService, $categoryService, $interactionService;

    public function __construct(GeoService $geoService, CategoryService $categoryService, InteractionService $interactionService)
    {
        $this->geoService = $geoService;
        $this->categoryService = $categoryService;
        $this->interactionService = $interactionService;
    }

    /**
     * Create a new organization.
     */
    public function createOrganization(?User $user, array $data, ?string $imagePath = null, array $with = []): array
    {
        // dd($data);
        // if ($user->isOrganization() && $user->organizations()->count() >= 1) {
        //     throw new \Exception(__('messages.max_reached', ['object' => __('messages.resources.organization.singular'), 'max' => 1]));
        // }

        // Category logic: If category_id is missing, find or create a new Main Category
        $categoryId = null;
        if (isset($data['category_id'])) { /// 
            $categoryId = $data['category_id'];
        } elseif (!isset($data['category_id']) && isset($data['category_name'])) {
            $ownerId = null;
            if ($user?->isUser()) {
                $ownerId = $user->id;
            }
            $category = $this->categoryService->createCategory($user, [
                'name' => $data['category_name'],
                'parent_id' => null,
                'organization_id' => null,
                'owner_id' => $ownerId,
            ], [], $imagePath);
            // $category = Category::firstOrCreate([
            //     'name'            => $data['category_name'],
            //     'parent_id'       => null,
            // ], [
            //     'owner_id'        => $ownerId,
            // ]);
            $categoryId = $category['id'];
        }

        if (!isset($data['country_id']) && !isset($data['city_id'])) {
            if (isset($data['country_name']) && isset($data['city_name'])) {
                $geoData = $this->geoService->getOrCreateGeoData($data['country_name'], $data['city_name']);
                $data['country_id'] = $geoData['country_id'];
                $data['city_id'] = $geoData['city_id'];
            }
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data, $imagePath, $with, $categoryId) {
            // if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            //     if ($data->image && Storage::disk('public')->exists($user->image)) {
            //         Storage::disk('public')->delete($user->image);
            //     }
            //     $data['image'] = $data['image']->store('organizations', 'public');
            // }

            if (!is_null($imagePath)) {
                $data['image'] = $imagePath;
            }
            $data['owner_id'] = $user?->id;

            // dd($data);
            $organization = Organization::create($data);
            if (!is_null($categoryId)) {
                $organization->categories()->syncWithoutDetaching([$categoryId] ?? []);
            }

            $this->clearCache($user);

            return $organization->load($with)->toArray();
        });
    }

    /**
     * Update an organization.
     */
    public function updateOrganization(User $user, Organization $organization, array $data, ?string $imagePath = null, array $with = []): array
    {
        $categoryId = null;
        if (isset($data['category_id'])) { /// 
            $categoryId = $data['category_id'];
        } elseif (!isset($data['category_id']) && isset($data['category_name'])) {
            $ownerId = null;
            if ($user->isUser()) {
                $ownerId = $user->id;
            }
            $category = Category::firstOrCreate([
                'name'      => $data['category_name'],
                'parent_id' => null,
            ], [
                'owner_id'  => $ownerId,
            ]);
            $categoryId = $category->id;
        }

        if (!isset($data['country_id']) && !isset($data['city_id'])) {
            if (!empty($data['country_name']) && !empty($data['city_name'])) {
                $geoData = $this->geoService
                    ->getOrCreateGeoData($data['country_name'], $data['city_name']);

                $data['country_id'] = $geoData['country_id'];
                $data['city_id']    = $geoData['city_id'];
            }
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $organization, $data, $imagePath, $with, $categoryId) {
            // if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            //     if ($organization->image) {
            //         Storage::disk('public')->delete($organization->image);
            //     }
            //     $data['image'] = $data['image']->store('organizations', 'public');
            // }

            if (!is_null($imagePath)) {
                $data['image'] = $imagePath;
            }
            if (!is_null($categoryId)) {
                $organization->categories()->syncWithoutDetaching([$categoryId] ?? []);
            }

            $organization->update($data);
            $this->clearCache($user);
            return $organization->load($with)->toArray();
        });
    }

    /**
     * Delete an organization.
     */
    public function deleteOrganization(Organization $organization)
    {
        if ($organization->image && Storage::disk('public')->exists($organization->getRawOriginal('image'))) {
            Storage::disk('public')->delete($organization->getRawOriginal('image'));
        }
        $user = $organization->owner;
        $organization->delete();
        $this->clearCache($user);
    }

    /**
     * Get organizations visible to the user.
     */
    public function getVisibleOrganizations(User $user, array $filters = [], array $with = []): array
    {
        // $cacheKey = implode('_', [
        //     'user_' . $user->id,
        //     'organization',
        // ]);
        // return Cache::

        $cacheKey = 'organizations_' . $user->id . '_' . md5(json_encode([
            'filters' => $filters,
            'with'    => $with,
        ]));

        // return Cache::remember($cacheKey, 3600, function () use ($user, $filters, $with) {
        //     // if ($user->isUser()) {
        //     //     return Organization::where('owner_id', $user->id)
        //     //         ->orWhereHas('owner', function ($query) {
        //     //             $query->where('role', \App\Enums\UserRoleEnum::ORGANIZATION->value);
        //     //         })->get();
        //     // }
        //     // return $user->organizations;

        //     // if ($user->isOrganization()) {
        //     //     $query = $user->organizations()->getQuery(); // get the base query
        //     // } else {
        //     //     $query = Organization::where('owner_id', $user->id)
        //     //         ->orWhereHas('owner', function ($query) {
        //     //             $query->where('role', UserRoleEnum::ORGANIZATION->value);
        //     //         });
        //     // }
        //     // return $query->with($with)->get();
        // });

        return Cache::remember($cacheKey, 3600, function () use ($user, $filters, $with) {
            $query = Organization::accessibleBy($user)->with($with ?: []);

            // if (in_array('categories', $with)) {
            //     $query->throughCategoriesAccessible($user);
            // }

            // if ($user->isOrganization()) {
            //     $org = $user->organizationRoleOrganization();
            //     if (!$org) { /// 
            //         return collect();
            //     }
            //     $query
            //         // ->where('owner_id', $user->id)
            //         ->where('id', $org->id);
            // } else {
            //     $query->where(function ($q) use ($user) {

            //         $q->whereNull('owner_id') // main places

            //             ->orWhere('owner_id', $user->id) // created by the current user

            //             ->orWhereHas('owner', function ($q) {
            //                 $q->where('role', UserRoleEnum::ORGANIZATION->value); // created by any organization
            //             });
            //     });
            // }

            $query
                ->when(
                    $filters['category_id'] ?? null,
                    fn($q, $catId) =>
                    $q->whereHas(
                        'categories',
                        fn($q) =>
                        $q->where('categories.id', $catId)
                    )
                )
                ->when(
                    !empty($filters['has_categories']),
                    fn($q) =>
                    $q->has('categories', '>', 1)
                )
                ->when(
                    !empty($filters['has_places']),
                    fn($q) =>
                    $q->has('places')
                )
                // ->when(
                //     $filters['owner_id'] ?? null,
                //     fn($q, $ownerId) =>
                //     $q->where('owner_id', $ownerId)
                // )
                ->when(
                    $filters['country_id'] ?? null,
                    fn($q, $countryId) =>
                    $q->where('country_id', $countryId)
                )
                ->when(
                    $filters['city_id'] ?? null,
                    fn($q, $cityId) =>
                    $q->where('city_id', $cityId)
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
                    $filters['min_categories'] ?? null,
                    fn($q, $count) =>
                    $q->has('categories', '>=', $count)
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
                return $query->cursorPaginate($filters['pagination'] ?? 15);
            }
            if (!empty($filters['limit'])) {
                $query->limit($filters['limit']);
            }
            return $query->search($filters['search'] ?? null)->get()->toArray();
        });
    }

    /**
     * Get a single organization.
     */
    public function getOrganization($id, array $with = []): Organization
    {
        return Organization::with($with)->find($id);
    }

    /**
     * Clear organization cache.
     */
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

