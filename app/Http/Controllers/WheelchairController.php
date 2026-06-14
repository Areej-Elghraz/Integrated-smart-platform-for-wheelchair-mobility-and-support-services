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
     * Get the current connected wheelchair(s) for the user.
     */
    public function current(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (in_array($user->role, ['companion', 'doctor'])) {
            $patientIds = $user->friends()->wherePivot('status', 'accepted')->pluck('users.id');
            $wheelchairs = Wheelchair::whereIn('user_id', $patientIds)
                ->where('connection_state', 'online')
                ->get();

            return $this->successResponse('Current wheelchairs retrieved successfully.', parameters: ['data' => $wheelchairs]);
        }

        $wheelchair = Wheelchair::where('user_id', $user->id)
            ->where('connection_state', 'online')
            ->first();

        if (!$wheelchair) {
            return $this->errorResponse('No active wheelchair found.', 404);
        }

        return $this->successResponse('Current wheelchair retrieved successfully.', parameters: ['data' => $wheelchair]);
    }

    /**
     * Check if the current user has permission to create/update a map via their wheelchair.
     */
    public function checkMappingPermission(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validate that floor_id is required
        $request->validate([
            'floor_id' => 'required|integer|exists:floors,id',
        ]);

        $floorId = $request->input('floor_id');
        $floor = \App\Models\Floor::with('building.organization')->find($floorId);

        // 1. Check if user has an active connected wheelchair
        $wheelchair = Wheelchair::where('user_id', $user->id)
            ->where('connection_state', 'online')
            ->first();

        if (!$wheelchair) {
            return $this->successResponse('Mapping permission check.', parameters: [
                'data' => [
                    'can_map' => false,
                    'wheelchair_id' => null,
                    'reason' => 'No active wheelchair connected.',
                ]
            ]);
        }

        $canMap = false;
        $reason = 'User does not have permission to map this floor.';

        // Super Admin check
        if ($user->role === \App\Enums\UserRoleEnum::SUPER_ADMIN->value) {
            $canMap = true;
            $reason = 'Super admin has access to all floors.';
        } else {
            $buildingOwnerId = $floor->building->owner_id ?? null;
            $orgOwnerId = $floor->building->organization->owner_id ?? null;

            if ($buildingOwnerId === $user->id) {
                $canMap = true;
                $reason = 'User is the owner of the building for this floor.';
            } elseif ($orgOwnerId === $user->id) {
                $canMap = true;
                $reason = 'User is the owner of the organization for this floor.';
            }
        }

        return $this->successResponse('Mapping permission check.', parameters: [
            'data' => [
                'can_map' => $canMap,
                'wheelchair_id' => $wheelchair->id,
                'reason' => $reason,
            ]
        ]);
    }
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
            $wheelchair = Wheelchair::where('serial_number', $validated['serial_number'])->first();

            if ($wheelchair) {
                if ($wheelchair->user_id !== null && $wheelchair->user_id !== $user->id) {
                    return $this->errorResponse('This wheelchair is already connected to another user.', 403);
                }
            } else {
                $wheelchair = Wheelchair::create([
                    'serial_number' => $validated['serial_number'],
                    'api_key' => 'wh_' . \Illuminate\Support\Str::random(32),
                    'connection_state' => 'offline'
                ]);
            }

            // Generate an api_key if it doesn't have one (for older records)
            if (empty($wheelchair->api_key)) {
                $wheelchair->api_key = 'wh_' . \Illuminate\Support\Str::random(32);
            }

            // Unassign this user from any other wheelchair
            Wheelchair::where('user_id', $user->id)
                ->where('serial_number', '!=', $validated['serial_number'])
                ->orWhere('id', '!=', $wheelchair->id)
                ->update(['user_id' => null, 'connection_state' => 'offline']);

            $wheelchair->update([
                'user_id' => $user->id,
                'connection_state' => 'online',
            ]);

            DB::commit();

            broadcast(new WheelchairUpdated($wheelchair));

            $diseases = [];
            if ($user && $user->medicalConditions) {
                $diseases = $user->medicalConditions->pluck('name');
            }

            return $this->successResponse('Wheelchair connected successfully.', parameters: [
                'data' => [
                    'wheelchair_id' => $wheelchair->id,
                    'api_key' => $wheelchair->api_key,
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
    public function updateCurrentVitalState(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id' => 'nullable|exists:trips,id',
            'heart_rate' => 'required|numeric',
            'heart_rate_status' => 'required|string|in:low,medium,critical',
            'temperature' => 'required|numeric',
            'temperature_status' => 'required|string|in:low,medium,critical',
            'mpu_angle' => 'required|numeric',
            'fall_status' => 'required|string|in:low,medium,critical',
            'type' => 'nullable|string',
            'risk_level' => 'required|string|in:low,medium,critical',
            'reason' => 'nullable|string',
            'recommendation' => 'nullable|string',
        ]);

        $wheelchair = $request->get('authenticated_wheelchair');
        if (!$wheelchair) {
            return $this->errorResponse('Unauthorized wheelchair.', 403);
        }

        DB::beginTransaction();
        try {
            $aiData = $wheelchair->aiRecommendations()->updateOrCreate(
                ['wheelchair_id' => $wheelchair->id],
                \Illuminate\Support\Arr::except($validated, ['trip_id'])
            );

            // Record event
            $event = Event::create([
                'wheelchair_id' => $wheelchair->id,
                'trip_id' => $validated['trip_id'] ?? null,
                'type' => $validated['type'] ?? 'health',
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
                $user = $wheelchair->user;
                if ($user) {
                    $companions = collect($user->connectedCompanions);
                    $doctor = $user->connectedDoctor;

                    $allConnected = $companions;
                    if ($doctor) {
                        $allConnected->push($doctor);
                    }

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

                    foreach ($allConnected as $connection) {
                        broadcast(new \App\Events\SosTriggered($connection->id, $payload));
                        $connection->notify(new \App\Notifications\DatabaseNotification\SosAlertNotification($user, $payload));
                        \Illuminate\Support\Facades\Log::info("Auto SOS broadcast to {$connection->name} for patient {$user->name} due to critical fall.");
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
