<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organization\FilterRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\OrganizationService;
use App\Http\Requests\Organization\StoreRequest;
use App\Http\Requests\Organization\UpdateRequest;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Traits\HasInteractionActions;

class OrganizationController extends ApiController
{
    use HasInteractionActions;
    use \App\Traits\LogsAdminActions;

    protected $organizationService;

    public function __construct(OrganizationService $organizationService, InteractionService $interactionService)
    {
        $this->organizationService = $organizationService;
        $this->interactionService = $interactionService;
    }

    public function relationships($include, User $user)
    {
        $with = array_filter(explode(',', $include));
        $with = array_intersect($with, Organization::ALLOWED_RELATIONS);
        $with = collect($with)->mapWithKeys(function ($relation) use ($user) {
            $access = [
                'categories',
                'categories.parent',
                'categories.children',
                'categories.organizations',
                'categories.places',
                'buildings.floors.places',
                'buildings.organization',
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

    public function index(FilterRequest $request)
    {
        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $organizations = $this->organizationService->getVisibleOrganizations(auth('sanctum')->user(), $request->validated(), $with);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.organization.plural')]),
            status: 200,
            parameters: $organizations
        );
    }

    public function show(Request $request, $id)
    {
        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $organization = $this->organizationService->getOrganization($id, $with);

        $this->authorize('view', $organization);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.organization.singular')]),
            status: 200,
            parameters: $organization->toArray()
        );
    }

    public function store(StoreRequest $request)
    {
        $this->authorize('create', Organization::class);

        try {
            $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('organizations', 'public');
            }

            $organization = $this->organizationService->createOrganization(auth('sanctum')->user(), $request->validated(), $path ?? null, $with);

            $this->logAdminAction('created', $organization, $request->validated());

            return $this->successResponse(
                message: __('messages.actions.created_success', ['resource' => __('messages.resources.organization.singular')]),
                status: 201,
                parameters: $organization
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    public function update(UpdateRequest $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        if ($request->hasFile('image')) {
            if ($organization->image && Storage::disk('public')->exists($organization->getRawOriginal('image'))) {
                Storage::disk('public')->delete($organization->getRawOriginal('image'));
            }
            $path = $request->file('image')->store('organizations', 'public');
        }

        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $organization = $this->organizationService->updateOrganization(auth('sanctum')->user(), $organization, $request->validated(), $path ?? null, $with);

        $this->logAdminAction('updated', $organization, $request->validated());

        return $this->successResponse(
            message: __('messages.actions.updated_success', ['resource' => __('messages.resources.organization.singular')]),
            status: 200,
            parameters: $organization
        );
    }

    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);

        $this->logAdminAction('deleted', $organization);

        $this->organizationService->deleteOrganization($organization);

        return $this->successResponse(
            message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.organization.singular')]),
            status: 200
        );
    }

    public function visit(Organization $organization)
    {
        return $this->recordVisit($organization);
    }
}
