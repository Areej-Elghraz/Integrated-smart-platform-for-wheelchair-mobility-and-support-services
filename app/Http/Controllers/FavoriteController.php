<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\FavoriteFilterRequest;
use Illuminate\Http\Request;
use App\Models\Place;
use Illuminate\Support\Facades\Auth;

use App\Services\PlaceService;

use App\Models\Organization;
use App\Services\InteractionService;
use App\Traits\HasInteractionActions;

class FavoriteController extends ApiController
{
    use HasInteractionActions;

    protected $placeService;

    public function __construct(PlaceService $placeService, InteractionService $interactionService)
    {
        $this->placeService = $placeService;
        $this->interactionService = $interactionService;
    }

    public function relationships($include, $user)
    {
        $with = array_filter(explode(',', $include));
        $allowed = array_merge(Place::ALLOWED_RELATIONS, Organization::ALLOWED_RELATIONS);
        $with = array_intersect($with, $allowed);
        $with = collect($with)->mapWithKeys(function ($relation) use ($user) {
            $access = [
                'category',
                'category.parent',
                'category.children',
                'category.organizations',
                'category.places',
                'organization',
                'organization.categories',
                'organization.places',
                'categories',
                'places',
            ];
            if (in_array($relation, $access)) {
                return [$relation => function ($q) use ($user) {
                    $q->accessibleBy($user);
                }];
            }
            return [$relation => fn($q) => $q];
        })->toArray();
        return $with;
    }

    /**
     * Get user's favorite places and organizations.
     */
    public function index(FavoriteFilterRequest $request)
    {
        $with = $this->relationships($request->query('include', ''), Auth::user());

        $favorites = $this->placeService->getFavorites(Auth::user(), $request->validated(), $with);
        $parameters = $favorites instanceof \Illuminate\Database\Eloquent\Collection
            ? $favorites->toArray()
            : (is_array($favorites) ? $favorites : []);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.place.plural')]),
            status: 200,
            parameters: $parameters
        );
    }

    /**
     * Toggle favorite status for a place.
     */
    public function toggle(Place $place)
    {
        return $this->toggleFavorite($place);
    }

    /**
     * Toggle favorite status for an organization.
     */
    public function toggleOrganization(Organization $organization)
    {
        return $this->toggleFavorite($organization);
    }
}
