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
     * Get paginated events for a specific wheelchair.
     */
    public function index(\Illuminate\Http\Request $request, $wheelchairId): JsonResponse
    {
        $wheelchair = \App\Models\Wheelchair::findOrFail($wheelchairId);
        $this->authorize('view', $wheelchair);

        $perPage = $request->input('per_page', 50);
        $type = $request->input('type');
        $severity = $request->input('severity');

        $query = Event::where('wheelchair_id', $wheelchair->id)->latest('created_at');

        if ($type) {
            $query->where('type', $type);
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        $events = $query->paginate($perPage);

        return $this->successResponse('Events retrieved successfully.', parameters: ['data' => $events]);
    }

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
        // Auto-detect active trip
        $activeTrip = \App\Models\Trip::where('wheelchair_id', $wheelchair->id)
            ->where('status', 'started')
            ->first();

        $event = Event::create([
            'wheelchair_id' => $wheelchair->id,
            'trip_id' => $activeTrip ? $activeTrip->id : null,
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'message' => $validated['message'],
            'data' => $validated['data'],
            'event_source' => $validated['event_source'] ?? 'ai',
        ]);

        broadcast(new WheelchairEventOccurred($event));

        // Trigger Dashboard Broadcast (Lightweight)
        if ($wheelchair->user) {
            broadcast(new \App\Events\DashboardUpdated($wheelchair->user->id, 'user_dashboard', ['action' => 'refresh_required']));
        }

        return $this->successResponse('Event stored successfully.', parameters: ['data' => $event]);
    }
}
