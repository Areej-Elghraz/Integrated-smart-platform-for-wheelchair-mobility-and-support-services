<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Place\FilterRequest;
use App\Http\Requests\Place\StoreRequest;
use App\Http\Requests\Place\UpdateRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Models\Floor;
use App\Models\User;
use App\Services\InteractionService;
use App\Services\PlaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Traits\HasInteractionActions;

class PlaceController extends ApiController
{
    use HasInteractionActions;
    use \App\Traits\LogsAdminActions;

    protected $placeService;

    public function __construct(PlaceService $placeService, InteractionService $interactionService)
    {
        $this->placeService = $placeService;
        $this->interactionService = $interactionService;
    }

    public function relationships($include, User $user)
    {
        $with = array_filter(explode(',', $include));
        $with = array_intersect($with, Place::ALLOWED_RELATIONS);
        $with = collect($with)->mapWithKeys(function ($relation) use ($user) {
            $access = [
                'categories',
                'categories.parent',
                'categories.children',
                'categories.organizations',
                'categories.places',
                'floor.building.organization',
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

        $places = $this->placeService->getVisiblePlaces(auth('sanctum')->user(), $request->validated(), $with);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.place.plural')]),
            status: 200,
            parameters: PlaceResource::collection($places)->resolve()
        );
    }

    /**
     * List all places for a specific floor (Shallow Nesting: GET /floors/{floor}/places).
     */
    public function indexForFloor(Floor $floor, Request $request)
    {
        $this->authorize('view', $floor);

        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $places = $floor->places()
            ->search($request->input('search'))
            ->with($with)
            ->get();

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.place.plural')]),
            status: 200,
            parameters: PlaceResource::collection($places)->resolve()
        );
    }

    /**
     * Create a place under a specific floor (Shallow Nesting: POST /floors/{floor}/places).
     * The floor_id and map_id are inferred from the URL, no need to send them in body.
     */
    public function storeForFloor(Floor $floor, StoreRequest $request)
    {
        $this->authorize('view', $floor); // changed from update to view to allow users to add places to global floors

        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('places', 'public');
        }

        // Merge floor_id from the URL context
        $data = $request->validated();
        $data['floor_id'] = $floor->id;

        $place = $this->placeService->createPlace(auth('sanctum')->user(), $data, $path, $with);
        $place->load('floor');

        $this->logAdminAction('created', $place, $data);

        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => __('messages.resources.place.singular')]),
            status: 201,
            parameters: (new PlaceResource($place))->resolve()
        );
    }

    public function show(Request $request, $id)
    {
        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $place = $this->placeService->getPlace($id, $with);

        $this->authorize('view', $place);

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.place.singular')]),
            status: 200,
            parameters: (new PlaceResource($place))->resolve()
        );
    }

    public function store(StoreRequest $request)
    {
        $this->authorize('create', [Place::class, $request->validated()]);

        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('places', 'public');
        }
        $place = $this->placeService->createPlace(auth('sanctum')->user(), $request->validated(), $path, $with);

        $this->logAdminAction('created', $place, $request->validated());

        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => __('messages.resources.place.singular')]),
            status: 201,
            parameters: (new PlaceResource($place))->resolve()
        );
    }

    public function update(UpdateRequest $request, Place $place)
    {
        $this->authorize('update', $place);

        $with = $this->relationships($request->query('include', ''), auth('sanctum')->user());

        $path = null;
        if ($request->hasFile('image')) {
            if ($place->image && Storage::disk('public')->exists($place->getRawOriginal('image'))) {
                Storage::disk('public')->delete($place->getRawOriginal('image'));
            }
            $path = $request->file('image')->store('places', 'public');
        }

        $place = $this->placeService->updatePlace(auth('sanctum')->user(), $place, $request->validated(), $path, $with);

        $this->logAdminAction('updated', $place, $request->validated());

        return $this->successResponse(
            message: __('messages.actions.updated_success', ['resource' => __('messages.resources.place.singular')]),
            status: 200,
            parameters: (new PlaceResource($place))->resolve()
        );
    }

    public function destroy(Place $place)
    {
        $this->authorize('delete', $place);

        $this->logAdminAction('deleted', $place);

        $this->placeService->deletePlace(auth('sanctum')->user(), $place);

        return $this->successResponse(
            message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.place.singular')]),
            status: 200
        );
    }
    public function visit(Place $place)
    {
        return $this->recordVisit($place);
    }

    public function lastVisited(Request $request)
    {
        $user = $request->user();
        $targetUserId = $request->query('user_id');

        if ($targetUserId && $targetUserId != $user->id) {
            if (!in_array($user->role, ['companion', 'doctor'])) {
                return $this->errorResponse('Unauthorized to view other users.', 403);
            }
            
            $isFriend = $user->friends()->wherePivot('status', 'accepted')->where('users.id', $targetUserId)->exists();
            if (!$isFriend) {
                return $this->errorResponse('Not connected to this user.', 403);
            }
            $targetUser = User::findOrFail($targetUserId);
        } else {
            if ($user->role !== 'user') {
                return $this->errorResponse('Unauthorized. Patients only, unless user_id is provided.', 403);
            }
            $targetUser = $user;
        }

        $search = $request->query('search');
        $limit = $request->query('limit', 15);

        $query = \App\Models\Trip::whereHas('wheelchair', function ($q) use ($targetUser) {
            $q->where('user_id', $targetUser->id);
        })->where('status', 'completed')->with('place')->latest();
        
        if ($search) {
            $query->whereHas('place', function ($q) use ($search) {
                $q->search($search);
            });
        }

        $trips = $query->paginate($limit);
        $lastVisitedPlaces = $trips->getCollection()->pluck('place')->filter()->values();

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => 'Last Visited Places']),
            status: 200,
            parameters: [
                'data' => PlaceResource::collection($lastVisitedPlaces)->resolve(),
                'meta' => [
                    'current_page' => $trips->currentPage(),
                    'last_page' => $trips->lastPage(),
                    'per_page' => $trips->perPage(),
                    'total' => $trips->total(),
                ]
            ]
        );
    }
}
