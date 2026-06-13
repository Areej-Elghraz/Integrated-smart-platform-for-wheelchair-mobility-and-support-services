<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Floor\StoreFloorRequest;
use App\Http\Requests\Floor\UpdateFloorRequest;
use App\Models\Floor;
use App\Models\Building;
use Illuminate\Http\Request;

class FloorController extends ApiController
{
    use \App\Traits\LogsAdminActions;

    /**
     * Get allowed relations from request include query.
     */
    protected function getRelations(Request $request): array
    {
        $includes = explode(',', $request->query('include', ''));
        $allowed = Floor::ALLOWED_RELATIONS;
        return array_intersect($includes, $allowed);
    }



    /**
     * Display a listing of floors for a specific building.
     */
    public function indexForBuilding(Building $building, Request $request)
    {
        $this->authorize('view', $building);

        $with = $this->getRelations($request);
        $floors = $building->floors()
            ->search($request->input('search'))
            ->with($with)
            ->get();

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.floor.plural')]),
            status: 200,
            parameters: $floors->toArray()
        );
    }



    /**
     * Store a newly created floor for a building.
     */
    public function storeForBuilding(Building $building, StoreFloorRequest $request)
    {
        $this->authorize('update', $building);

        $floor = $building->floors()->create($request->validated());

        $this->logAdminAction('created', $floor, $request->validated());

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

        $this->logAdminAction('updated', $floor, $request->validated());

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

        $this->logAdminAction('deleted', $floor);

        $floor->delete();

        return $this->successResponse(
            message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.floor.singular')]),
            status: 200
        );
    }
}
