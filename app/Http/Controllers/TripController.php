<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Wheelchair;
use App\Events\TripUpdated;
use App\Events\TripMovementStatusUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends ApiController
{
    /**
     * Start a new trip.
     */
    public function startTrip(Request $request, $wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);
        $this->authorize('update', $wheelchair);

        $validated = $request->validate([
            'mode' => 'required|string|in:autonomous,manual',
            'place_id' => 'nullable|exists:places,id',
        ]);

        // End any currently active trips for this wheelchair
        Trip::where('wheelchair_id', $wheelchair->id)
            ->where('status', 'started')
            ->update([
                'status' => 'completed',
                'ended_at' => now(),
            ]);

        $trip = Trip::create([
            'wheelchair_id' => $wheelchair->id,
            'place_id' => $validated['place_id'] ?? null,
            'mode' => $validated['mode'],
            'status' => 'started',
            'started_at' => now(),
        ]);

        $trip->load(['place', 'wheelchair']);

        broadcast(new TripUpdated($trip));

        return $this->successResponse('Trip started successfully.', parameters: ['data' => $trip]);
    }

    /**
     * End a trip.
     */
    public function endTrip($tripId): JsonResponse
    {
        $trip = Trip::with('wheelchair')->findOrFail($tripId);
        $this->authorize('update', $trip->wheelchair);

        $trip->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        broadcast(new TripUpdated($trip));

        return $this->successResponse('Trip ended successfully.', parameters: ['data' => $trip]);
    }

    /**
     * Get movement states of a trip.
     */
    public function movementStates($tripId): JsonResponse
    {
        $trip = Trip::with(['wheelchair', 'movementState'])->findOrFail($tripId);
        $this->authorize('view', $trip->wheelchair);

        return $this->successResponse('Movement state retrieved.', parameters: ['data' => $trip->movementState]);
    }

    public function updateMovementStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id' => 'nullable|exists:trips,id',
            'movement_status' => 'required|string|in:moving,idle',
            'speed' => 'required|numeric',
            'position' => 'required|array',
            'position.x' => 'required|numeric',
            'position.y' => 'required|numeric',
            'theta' => 'required|numeric',
            'mode' => 'required|string|in:autonomous,manual',
            'risk_level' => 'required|string|in:low,medium,high',
            'obstacle_detected' => 'required|boolean',
            'obstacle_distance' => 'required|numeric',
        ]);

        $wheelchair = $request->get('authenticated_wheelchair');
        if (!$wheelchair) {
            return $this->errorResponse('Unauthorized wheelchair.', 403);
        }

        // 1. Update wheelchair coordinates
        $wheelchair->update([
            'x_coordinate' => $validated['position']['x'],
            'y_coordinate' => $validated['position']['y'],
            'theta' => $validated['theta'],
        ]);

        // Broadcast to UI
        broadcast(new \App\Events\WheelchairUpdated($wheelchair));

        // 2. Update trip if exists
        $movementState = null;
        if (!empty($validated['trip_id'])) {
            $trip = Trip::find($validated['trip_id']);
            if ($trip && $trip->wheelchair_id == $wheelchair->id) {
                $movementState = $trip->movementState()->updateOrCreate(
                    ['trip_id' => $trip->id],
                    \Illuminate\Support\Arr::except($validated, ['trip_id'])
                );
                $movementState->load('trip.wheelchair');
                broadcast(new TripMovementStatusUpdated($movementState));
            }
        }

        return $this->successResponse('Trip movement state updated successfully.', parameters: [
            'data' => [
                'wheelchair' => $wheelchair,
                'movement_state' => $movementState
            ]
        ]);
    }
}