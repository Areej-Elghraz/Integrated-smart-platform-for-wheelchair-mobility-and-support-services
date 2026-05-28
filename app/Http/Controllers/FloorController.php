<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Floor\StoreFloorRequest;
use App\Http\Requests\Floor\UpdateFloorRequest;
use App\Models\Floor;
use App\Models\Organization;
use App\Models\Place;
use Illuminate\Http\Request;

class FloorController extends ApiController
{
    /**
     * Get allowed relations from request include query.
     */
    protected function getRelations(Request $request): array
    {
        $includes = explode(',', $request->query('include', ''));
        $allowed = ['map', 'places', 'organization', 'place'];
        return array_intersect($includes, $allowed);
    }

    /**
     * Display a listing of floors for a specific organization.
     */
    public function indexForOrganization(Organization $organization, Request $request)
    {
        $this->authorize('view', $organization);

        $with = $this->getRelations($request);
        $floors = $organization->floors()->with($with)->get();

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.floor.plural')]),
            status: 200,
            parameters: $floors->toArray()
        );
    }

    /**
     * Display a listing of floors for a specific place.
     */
    public function indexForPlace(Place $place, Request $request)
    {
        $this->authorize('view', $place);

        $with = $this->getRelations($request);
        $floors = $place->floors()->with($with)->get();

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.floor.plural')]),
            status: 200,
            parameters: $floors->toArray()
        );
    }

    /**
     * Store a newly created floor for an organization.
     */
    public function storeForOrganization(Organization $organization, StoreFloorRequest $request)
    {
        $this->authorize('update', $organization);

        $floor = $organization->floors()->create($request->validated());

        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => __('messages.resources.floor.singular')]),
            status: 201,
            parameters: $floor->toArray()
        );
    }

    /**
     * Store a newly created floor for a place.
     */
    public function storeForPlace(Place $place, StoreFloorRequest $request)
    {
        $this->authorize('update', $place);

        $floor = $place->floors()->create($request->validated());

        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => __('messages.resources.floor.singular')]),
            status: 201,
            parameters: $floor->toArray()
        );
    }

    /**
     * Display the specified floor.
     */
    public function show(Floor $floor, Request $request)
    {
        $this->authorize('view', $floor);

        $with = $this->getRelations($request);
        if (!empty($with)) {
            $floor->load($with);
        }

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.floor.singular')]),
            status: 200,
            parameters: $floor->toArray()
        );
    }

    /**
     * Update the specified floor.
     */
    public function update(UpdateFloorRequest $request, Floor $floor)
    {
        $this->authorize('update', $floor);

        $floor->update($request->validated());

        return $this->successResponse(
            message: __('messages.actions.updated_success', ['resource' => __('messages.resources.floor.singular')]),
            status: 200,
            parameters: $floor->toArray()
        );
    }

    /**
     * Remove the specified floor from storage.
     */
    public function destroy(Floor $floor)
    {
        $this->authorize('delete', $floor);

        $floor->delete();

        return $this->successResponse(
            message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.floor.singular')]),
            status: 200
        );
    }
}
