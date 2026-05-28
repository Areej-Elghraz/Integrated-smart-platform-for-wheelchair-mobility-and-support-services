<?php

namespace App\Http\Controllers;

use App\Models\Wheelchair;
use App\Models\CurrentVitalState;
use App\Models\Event;
use App\Models\Trip;
use App\Events\WheelchairUpdated;
use App\Events\VitalStateUpdated;
use App\Events\WheelchairEventOccurred;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WheelchairController extends ApiController
{
    /**
     * Connect a wheelchair.
     */
    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'serial_number' => 'required|string',
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // Unassign this user from any other wheelchair
            Wheelchair::where('user_id', $user->id)
                ->where('serial_number', '!=', $validated['serial_number'])
                ->update(['user_id' => null, 'connection_state' => 'offline']);

            $wheelchair = Wheelchair::firstOrCreate(
                ['serial_number' => $validated['serial_number']],
                ['battery' => 100, 'voltage' => 24, 'current' => 0, 'temperature' => 25, 'connection_state' => 'offline']
            );

            $wheelchair->update([
                'user_id' => $user->id,
                'connection_state' => 'online',
            ]);

            DB::commit();

            broadcast(new WheelchairUpdated($wheelchair));

            $diseases = [];
            if ($user && $user->medicalConditions) {
                $diseases = $user->medicalConditions->pluck('name'); // Assumes 'name' field exists in medical_conditions
            }

            return $this->successResponse('Wheelchair connected successfully.', parameters: [
                'data' => [
                    'wheelchair_id' => $wheelchair->id,
                    'user_id' => $user ? $user->id : null,
                    'user_weight' => $user ? $user->weight : null,
                    'user_height' => $user ? $user->height : null,
                    'diseases' => $diseases,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to connect wheelchair: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Disconnect a wheelchair.
     */
    public function disconnect($wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);

        $this->authorize('update', $wheelchair);

        $wheelchair->update(['connection_state' => 'offline']);

        broadcast(new WheelchairUpdated($wheelchair));

        return $this->successResponse('Wheelchair disconnected successfully.', parameters: ['data' => $wheelchair]);
    }

    /**
     * Update wheelchair data (battery, voltage, etc).
     */
    public function update(\App\Http\Requests\Wheelchair\UpdateWheelchairRequest $request, $wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);

        $this->authorize('update', $wheelchair);

        $validated = $request->validated();

        $wheelchair->update($validated);

        // Check for low battery
        if (isset($validated['battery']) && $validated['battery'] <= 20) {
            $existingBatteryEvent = Event::findDuplicate(
                $wheelchair->id,
                'battery',
                'high',
                'system'
            );

            if ($existingBatteryEvent) {
                $existingBatteryEvent->touch();
            } else {
                $event = Event::create([
                    'wheelchair_id' => $wheelchair->id,
                    'trip_id' => null,
                    'type' => 'battery',
                    'severity' => 'high',
                    'message' => 'Low battery warning: ' . $validated['battery'] . '% remaining.',
                    'data' => ['battery' => $validated['battery']],
                    'event_source' => 'system',
                ]);
                broadcast(new WheelchairEventOccurred($event));
            }
        }

        broadcast(new WheelchairUpdated($wheelchair));

        return $this->successResponse('Wheelchair updated successfully.', parameters: ['data' => $wheelchair]);
    }

    /**
     * Unassign a wheelchair from the user.
     */
    public function unassign($wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);

        $this->authorize('update', $wheelchair);

        $wheelchair->update([
            'user_id' => null,
            'connection_state' => 'offline',
        ]);

        broadcast(new WheelchairUpdated($wheelchair));

        return $this->successResponse('Wheelchair unassigned successfully.', parameters: ['data' => $wheelchair]);
    }

    /**
     * Get current vital state.
     */
    public function showVitals($wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);
        $this->authorize('view', $wheelchair);

        return $this->successResponse('Vital state retrieved.', parameters: ['data' => $wheelchair->aiRecommendations()->latest()->first()]);
    }

    /**
     * Update current vital state and record an event.
     */
    public function updateCurrentVitalState(Request $request, $wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);
        $this->authorize('update', $wheelchair);

        $validated = $request->validate([
            'heart_rate' => 'required|numeric',
            'heart_rate_status' => 'required|string|in:normal,medium,critical',
            'temperature' => 'required|numeric',
            'temperature_status' => 'required|string|in:normal,medium,critical',
            'mpu_angle' => 'required|numeric',
            'fall_status' => 'required|string|in:normal,medium,critical',
            'type' => 'nullable|string',
            'risk_level' => 'required|string|in:normal,medium,critical',
            'reason' => 'nullable|string',
            'recommendation' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $aiData = $wheelchair->aiRecommendations()->updateOrCreate(
                ['wheelchair_id' => $wheelchair->id],
                $validated
            );

            // Find active trip
            $activeTrip = Trip::where('wheelchair_id', $wheelchair->id)
                ->where('status', 'started')
                ->latest()
                ->first();

            // Record event
            $event = Event::create([
                'wheelchair_id' => $wheelchair->id,
                'trip_id' => $activeTrip ? $activeTrip->id : null,
                'type' => $validated['type'] ?? 'heart',
                'severity' => $validated['risk_level'],
                'message' => $validated['reason'] ?? $validated['recommendation'] ?? 'Health update',
                'data' => [
                    'heart_rate' => $validated['heart_rate'],
                    'temperature' => $validated['temperature'],
                    'mpu_angle' => $validated['mpu_angle'],
                    'fall_status' => $validated['fall_status'],
                ],
                'event_source' => 'ai',
            ]);

            // Automatic SOS Trigger on critical fall (Fainting/Accident)
            if ($validated['fall_status'] === 'critical') {
                $user = $wheelchair->assignedUser;
                if ($user) {
                    $friendsOfMine = $user->friendsOfMine()->wherePivot('status', 'accepted')->get();
                    $friendOf = $user->friendOf()->wherePivot('status', 'accepted')->get();
                    $allFriends = $friendsOfMine->merge($friendOf);

                    $payload = [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username,
                        ],
                        'latitude' => null,
                        'longitude' => null,
                        'location_link' => null,
                        'message' => "AUTOMATIC SOS: {$user->name} has experienced a critical fall/fainting event!",
                        'triggered_at' => now()->toISOString(),
                    ];

                    foreach ($allFriends as $friend) {
                        broadcast(new \App\Events\SosTriggered($friend->id, $payload));
                        $friend->notify(new \App\Notifications\DatabaseNotification\SosAlertNotification($user, $payload));
                        \Illuminate\Support\Facades\Log::info("Auto SOS broadcast to {$friend->name} for patient {$user->name} due to critical fall.");
                    }
                }
            }

            DB::commit();

            broadcast(new WheelchairEventOccurred($event));

            // Trigger Dashboard Broadcast
            $targetUser = $wheelchair->user;
            if ($targetUser) {
                $dashboardData = \App\Http\Controllers\DashboardController::getDashboardData($targetUser);
                broadcast(new \App\Events\DashboardUpdated($targetUser->id, 'user_dashboard', $dashboardData));
            }

            return $this->successResponse('Vital state updated successfully.', parameters: ['data' => $aiData]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update vitals: ' . $e->getMessage(), 500);
        }
    }
}
