<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Trip;
use App\Http\Requests\Wheelchair\StoreEventRequest;
use App\Events\WheelchairEventOccurred;
use Illuminate\Http\JsonResponse;

class EventController extends ApiController
{
    /**
     * Store an event associated with a trip, with deduplication.
     */
    public function storeTripEvent(StoreEventRequest $request, $tripId): JsonResponse
    {
        $trip = Trip::with('wheelchair')->findOrFail($tripId);
        $this->authorize('update', $trip->wheelchair);

        $validated = $request->validated();

        // Check for deduplication
        $existingEvent = Event::findDuplicate(
            $trip->wheelchair_id,
            $validated['type'],
            $validated['severity'],
            $validated['event_source'] ?? 'ai'
        );

        if ($existingEvent) {
            // Update timestamp of the existing unresolved event
            $existingEvent->touch();

            return $this->successResponse('Event deduplicated (updated timestamp).', parameters: ['data' => $existingEvent]);
        }

        // Otherwise create new event
        $event = Event::create([
            'wheelchair_id' => $trip->wheelchair_id,
            'trip_id' => $trip->id,
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'message' => $validated['message'],
            'data' => $validated['data'],
            'event_source' => $validated['event_source'] ?? 'ai',
        ]);

        broadcast(new WheelchairEventOccurred($event));

        // Trigger Dashboard Broadcast
        $targetUser = $trip->wheelchair->user;
        $dashboardData = \App\Http\Controllers\DashboardController::getDashboardData($targetUser);
        broadcast(new \App\Events\DashboardUpdated($targetUser->id, 'user_dashboard', $dashboardData));

        return $this->successResponse('Event stored successfully.', parameters: ['data' => $event]);
    }
}
