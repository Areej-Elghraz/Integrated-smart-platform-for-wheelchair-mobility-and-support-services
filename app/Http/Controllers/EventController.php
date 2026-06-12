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
    public function storeTripEvent(StoreEventRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $wheelchair = $request->get('authenticated_wheelchair');
        if (!$wheelchair) {
            return $this->errorResponse('Unauthorized wheelchair.', 403);
        }

        // Check for deduplication
        $existingEvent = Event::findDuplicate(
            $wheelchair->id,
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
            'wheelchair_id' => $wheelchair->id,
            'trip_id' => $validated['trip_id'] ?? null,
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'message' => $validated['message'],
            'data' => $validated['data'],
            'event_source' => $validated['event_source'] ?? 'ai',
        ]);

        broadcast(new WheelchairEventOccurred($event));

        // Trigger Dashboard Broadcast
        if ($wheelchair->user) {
            $dashboardData = \App\Http\Controllers\DashboardController::getDashboardData($wheelchair->user);
            broadcast(new \App\Events\DashboardUpdated($wheelchair->user->id, 'user_dashboard', $dashboardData));
        }

        return $this->successResponse('Event stored successfully.', parameters: ['data' => $event]);
    }
}
