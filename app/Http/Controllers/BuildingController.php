<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Building\StoreBuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Http\Resources\BuildingResource;
use App\Models\Building;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuildingController extends ApiController
{
    use \App\Traits\LogsAdminActions;

    /**
     * Display a listing of buildings for an organization.
     */
    public function indexForOrganization(Organization $organization, Request $request)
    {
        $this->authorize('view', $organization);

        $buildings = $organization->buildings()
            ->when(is_null($organization->owner_id), function ($query) use ($request) {
                // For global organizations, only show admin buildings (null) or user's buildings
                $query->where(function ($q) use ($request) {
                    $q->whereNull('owner_id')
                      ->orWhere('owner_id', $request->user()->id);
                });
            })
            ->search($request->input('search'))
            ->with(['floors', 'floors.map'])
            ->get();

        return $this->successResponse(
            message: 'Buildings retrieved successfully.',
            status: 200,
            parameters: BuildingResource::collection($buildings)->resolve()
        );
    }

    /**
     * Store a newly created building for an organization.
     */
    public function storeForOrganization(Organization $organization, StoreBuildingRequest $request)
    {
        $this->authorize('create', [Building::class, $organization]);

        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('buildings', 'public');
        }

        if ($request->user()->isOrganization()) {
            $data['owner_id'] = $request->user()->id;
        } elseif ($request->user()->isAdmin() || $request->user()->role === \App\Enums\UserRoleEnum::SUPER_ADMIN->value) {
            $data['owner_id'] = null; // Admin
        } else {
            $data['owner_id'] = $request->user()->id; // Normal user
        }

        $building = $organization->buildings()->create($data);

        $this->logAdminAction('created', $building, $request->validated());

        return $this->successResponse(
            message: 'Building created successfully.',
            status: 201,
            parameters: (new BuildingResource($building))->resolve()
        );
    }

    /**
     * Display the specified building.
     */
    public function show(Building $building)
    {
        $this->authorize('view', $building);

        $building->load(['floors', 'floors.map']);

        return $this->successResponse(
            message: 'Building retrieved successfully.',
            status: 200,
            parameters: (new BuildingResource($building))->resolve()
        );
    }

    /**
     * Update the specified building.
     */
    public function update(UpdateBuildingRequest $request, Building $building)
    {
        $this->authorize('update', $building);

        $data = $request->validated();
        if ($request->hasFile('image')) {
            if ($building->image && Storage::disk('public')->exists($building->getRawOriginal('image'))) {
                Storage::disk('public')->delete($building->getRawOriginal('image'));
            }
            $data['image'] = $request->file('image')->store('buildings', 'public');
        }

        $building->update($data);

        $this->logAdminAction('updated', $building, $request->validated());

        return $this->successResponse(
            message: 'Building updated successfully.',
            status: 200,
            parameters: (new BuildingResource($building))->resolve()
        );
    }

    /**
     * Remove the specified building from storage.
     */
    public function destroy(Building $building)
    {
        $this->authorize('delete', $building);

        if ($building->image && Storage::disk('public')->exists($building->getRawOriginal('image'))) {
            Storage::disk('public')->delete($building->getRawOriginal('image'));
        }

        $this->logAdminAction('deleted', $building);

        $building->delete();

        return $this->successResponse(
            message: 'Building deleted successfully.',
            status: 200
        );
    }
}
