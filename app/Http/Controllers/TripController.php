<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Wheelchair;
use App\Events\TripUpdated;
use App\Events\TripMovementStatusUpdated;
use App\Http\Requests\Trip\UpdateMovementStateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TripController extends ApiController
{
    /**
     * Get all trips for a specific wheelchair with filters.
     */
    public function index(Request $request, $wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);
        $this->authorize('view', $wheelchair);

        $query = Trip::with('events')->where('wheelchair_id', $wheelchair->id)->latest('started_at');

        if ($request->has('from_date')) {
            $query->whereDate('started_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('started_at', '<=', $request->to_date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('mode')) {
            $query->where('mode', $request->mode);
        }

        $trips = $query->paginate($request->input('per_page', 20));

        return $this->successResponse('Trips retrieved successfully.', parameters: ['data' => $trips]);
    }

    /**
     * Get a specific trip with events.
     */
    public function show($tripId): JsonResponse
    {
        $trip = Trip::with(['events', 'wheelchair', 'place'])->findOrFail($tripId);
        $this->authorize('view', $trip->wheelchair);

        return $this->successResponse('Trip details retrieved successfully.', parameters: ['data' => $trip]);
    }

    /**
     * Start a new trip.
     */
    public function startTrip(Request $request, $wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);

        $user = Auth::user();
        if ($wheelchair->user_id !== $user->id) {
            return $this->errorResponse('Only the wheelchair owner can start a trip.', 403);
        }

        $validated = $request->validate([
            'mode' => 'required|string|in:autonomous,manual',
            'place_id' => 'required_without:end_x,end_y|exists:places,id',
            'end_x' => 'required_without:place_id|numeric',
            'end_y' => 'required_without:place_id|numeric',
        ]);

        // Check if there is an active trip
        $activeTrip = Trip::where('wheelchair_id', $wheelchair->id)
            ->where('status', 'started')
            ->first();

        if ($activeTrip) {
            return $this->errorResponse('There is already an active trip. Please end or fail it before starting a new one.', 409, ['active_trip' => $activeTrip]);
        }

        $end_x = $validated['end_x'] ?? null;
        $end_y = $validated['end_y'] ?? null;

        if (!empty($validated['place_id'])) {
            $place = \App\Models\Place::find($validated['place_id']);
            if ($place) {
                $end_x = $end_x ?? $place->x;
                $end_y = $end_y ?? $place->y;
            }
        }

        $trip = Trip::create([
            'wheelchair_id' => $wheelchair->id,
            'place_id' => $validated['place_id'] ?? null,
            'mode' => $validated['mode'],
            'status' => 'started',
            'start_x' => $wheelchair->x_coordinate,
            'start_y' => $wheelchair->y_coordinate,
            'end_x' => $end_x,
            'end_y' => $end_y,
            'started_at' => now(),
        ]);

        $trip->load(['place', 'wheelchair']);

        broadcast(new TripUpdated($trip));

        return $this->successResponse('Trip started successfully.', parameters: ['data' => $trip]);
    }

    /**
     * End a trip via Flutter.
     */
    public function endTrip($tripId): JsonResponse
    {
        $trip = Trip::with('wheelchair')->findOrFail($tripId);

        $user = Auth::user();
        if ($trip->wheelchair->user_id !== $user->id) {
            return $this->errorResponse('Only the wheelchair owner can end the trip.', 403);
        }

        $trip->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        broadcast(new TripUpdated($trip));

        return $this->successResponse('Trip ended successfully.', parameters: ['data' => $trip]);
    }

    /**
     * Fail a trip via Flutter.
     */
    public function failTrip($tripId): JsonResponse
    {
        $trip = Trip::with('wheelchair')->findOrFail($tripId);

        $user = Auth::user();
        if ($trip->wheelchair->user_id !== $user->id) {
            return $this->errorResponse('Only the wheelchair owner can fail the trip.', 403);
        }

        $trip->update([
            'status' => 'failed',
            'ended_at' => now(),
        ]);

        broadcast(new TripUpdated($trip));

        return $this->successResponse('Trip failed successfully.', parameters: ['data' => $trip]);
    }

    /**
     * End a trip via IoT (Wheelchair HW).
     */
    public function endTripIot(Request $request): JsonResponse
    {
        $wheelchair = $request->get('authenticated_wheelchair');
        if (!$wheelchair) {
            return $this->errorResponse('Unauthorized wheelchair.', 403);
        }

        $trip = Trip::where('wheelchair_id', $wheelchair->id)->where('status', 'started')->first();
        if (!$trip) {
            return $this->errorResponse('No active trip found to end.', 404);
        }

        $trip->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        broadcast(new TripUpdated($trip));

        return $this->successResponse('Trip ended successfully.', parameters: ['data' => $trip]);
    }

    /**
     * Fail a trip via IoT (Wheelchair HW).
     */
    public function failTripIot(Request $request): JsonResponse
    {
        $wheelchair = $request->get('authenticated_wheelchair');
        if (!$wheelchair) {
            return $this->errorResponse('Unauthorized wheelchair.', 403);
        }

        $trip = Trip::where('wheelchair_id', $wheelchair->id)->where('status', 'started')->first();
        if (!$trip) {
            return $this->errorResponse('No active trip found to fail.', 404);
        }

        $trip->update([
            'status' => 'failed',
            'ended_at' => now(),
        ]);

        broadcast(new TripUpdated($trip));

        return $this->successResponse('Trip failed successfully.', parameters: ['data' => $trip]);
    }

}
