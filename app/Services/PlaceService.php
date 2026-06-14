<?php

namespace App\Services;

use App\Models\Place;
use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PlaceService
{
    protected $interactionService, $geoService, $categoryService;

    public function __construct(InteractionService $interactionService, GeoService $geoService, CategoryService $categoryService)
    {
        $this->interactionService = $interactionService;
        $this->geoService = $geoService;
        $this->categoryService = $categoryService;
    }
    /**
     * Create a new place.
     */
    public function createPlace(?User $user, array $data, ?string $imagePath = null, array $with = []): Place
    {
        $categoryId = null;
        if (isset($data['category_id'])) {
            $categoryId = $data['category_id'];
        } elseif (!isset($data['category_id']) && isset($data['category_name'])) {
            $ownerId = null;
            if ($user?->isUser()) {
                $ownerId = $user->id;
            }
            $category = $this->categoryService->createCategory($user, [
                'name' => $data['category_name'],
                'parent_id' => null,
                'owner_id' => $ownerId,
            ], [], $imagePath);
            $categoryId = $category['id'];
        }



        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data, $imagePath, $with, $categoryId) {
            $data['owner_id'] = $user?->id;

            if (!is_null($imagePath)) {
                $data['image'] = $imagePath;
            }

            // Calculate center_x and center_y from points if x and y are not set
            if (isset($data['points']) && is_array($data['points']) && count($data['points']) > 0) {
                if (!isset($data['x']) || !isset($data['y'])) {
                    $sumX = 0;
                    $sumY = 0;
                    foreach ($data['points'] as $point) {
                        $sumX += $point['x'] ?? 0;
                        $sumY += $point['y'] ?? 0;
                    }
                    $data['x'] = $sumX / count($data['points']);
                    $data['y'] = $sumY / count($data['points']);
                }
            }

            $place = Place::create($data);

            if (!is_null($categoryId)) {
                $place->categories()->syncWithoutDetaching([$categoryId]);
            }

            $this->clearCache($user);

            return $place->load($with);
        });
    }

    /**
     * Update a place.
     */
    public function updatePlace(User $user, Place $place, array $data, ?string $imagePath = null, array $with = []): Place
    {
        $categoryId = null;
        if (isset($data['category_id'])) {
            $categoryId = $data['category_id'];
        } elseif (!isset($data['category_id']) && isset($data['category_name'])) {
            $ownerId = null;
            if ($user->isUser()) {
                $ownerId = $user->id;
            }
            $category = \App\Models\Category::firstOrCreate([
                'name'      => $data['category_name'],
                'parent_id' => null,
            ], [
                'owner_id'  => $ownerId,
            ]);
            $categoryId = $category->id;
        }



        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $place, $data, $imagePath, $with, $categoryId) {
            if (!is_null($imagePath)) {
                $data['image'] = $imagePath;
            }

            // Calculate center_x and center_y from points if x and y are not set
            if (isset($data['points']) && is_array($data['points']) && count($data['points']) > 0) {
                if (!isset($data['x']) || !isset($data['y'])) {
                    $sumX = 0;
                    $sumY = 0;
                    foreach ($data['points'] as $point) {
                        $sumX += $point['x'] ?? 0;
                        $sumY += $point['y'] ?? 0;
                    }
                    $data['x'] = $sumX / count($data['points']);
                    $data['y'] = $sumY / count($data['points']);
                }
            }

            $place->update($data);

            if (!is_null($categoryId)) {
                $place->categories()->syncWithoutDetaching([$categoryId]);
            }

            $this->clearCache($user);
            return $place->load($with);
        });
    }

    /**
     * Delete a place.
     */
    public function deletePlace(User $user, Place $place)
    {
        if ($place->image && Storage::disk('public')->exists($place->getRawOriginal('image'))) {
            Storage::disk('public')->delete($place->getRawOriginal('image'));
        }
        $place->delete();
        $this->clearCache($user);
    }

    /**
     * Get places visible to the user.
     */
    public function getVisiblePlaces(User $user, array $filters = [], array $with = [])
    {
        $cacheKey = 'places_' . $user->id . '_' . md5(json_encode([
            'filters' => $filters,
            'with'    => $with,
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($user, $filters, $with) {
            $query = Place::accessibleBy($user)->with($with ?: []);

            // if ($user->isOrganization()) {

            //     $org = $user->organizationRoleOrganization();

            //     if (!$org) { /// 
            //         return collect();
            //     }

            //     $query->where('owner_id', $user->id)
            //         ->where('organization_id', $org->id);
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
                    $filters['organization_id'] ?? null,
                    fn($q, $orgId) =>
                    $q->whereHas('floor.building', fn($b) => $b->where('organization_id', $orgId))
                )
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
                    $q->has('categories', '>', 0)
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
            return $query->search($filters['search'] ?? null)->get();
        });
    }

    /**
     * Get a single place.
     */
    public function getPlace($id, array $with = []): Place
    {
        return Place::with($with ?: [])->find($id);
    }

    /**
     * Get user's favorite places.
     */
    public function getFavorites(User $user, array $filters = [], array $with = [])
    {
        $cacheKey = 'favorites_' . $user->id . '_' . md5(json_encode([
            'filters' => $filters,
            'with'    => $with,
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($user, $filters, $with) {
            $query = $user->favorites()->with($with ?: []);

            if (!empty($filters['pagination'])) {
                $paginated = $query->paginate((int) $filters['pagination'] ?: 15);
                return [
                    'data' => $paginated->items(),
                    'total' => $paginated->total(),
                ];
            }
            if (!empty($filters['limit'])) {
                $query->limit($filters['limit']);
            }

            return $query->get();
        });
    }

    /**
     * Record a visit to a place.
     */
    public function recordVisit(User $user, Place $place)
    {
        $this->interactionService->recordVisit($user, $place);
    }

    /**
     * Clear place cache.
     */
    public function clearCache(?User $user)
    {
        if ($user?->isOrganization()) {
            // Cache tags flush removed for database driver compatibility
        }
        if ($user) {
            // Cache tags flush removed for database driver compatibility
            // Cache tags flush removed for database driver compatibility
        } else {
            // Cache tags flush removed for database driver compatibility
        }
    }
}

