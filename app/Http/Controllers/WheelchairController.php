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
            $wheelchairs = Wheelchair::with(['trips' => function ($q) {
                $q->where('status', 'started')->latest();
            }])->whereIn('user_id', $patientIds)
                ->where('connection_state', 'online')
                ->get();

            $wheelchairs->each(function($w) {
                $w->active_trip = $w->trips->first() ?? null;
                unset($w->trips);
            });

            return $this->successResponse('Current wheelchairs retrieved successfully.', parameters: ['data' => $wheelchairs]);
        }

        $wheelchair = Wheelchair::with(['trips' => function ($q) {
            $q->where('status', 'started')->latest();
        }])->where('user_id', $user->id)
            ->where('connection_state', 'online')
            ->first();

        if (!$wheelchair) {
            return $this->errorResponse('No active wheelchair found.', 404);
        }

        $wheelchair->active_trip = $wheelchair->trips->first() ?? null;
        unset($wheelchair->trips);

        return $this->successResponse('Current wheelchair retrieved successfully.', parameters: ['data' => $wheelchair]);
    }

    /**
     * Initialize location for the wheelchair.
     */
    public function initializeLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wheelchair_id' => 'required|exists:wheelchairs,id',
            'floor_id' => 'required|exists:floors,id',
            'x_coordinate' => 'required|numeric',
            'y_coordinate' => 'required|numeric',
            'theta' => 'nullable|numeric',
        ]);

        $wheelchair = Wheelchair::findOrFail($validated['wheelchair_id']);
        
        $user = Auth::user();
        if ($wheelchair->user_id !== $user->id) {
            // Check if companion
            $isCompanion = $user->friends()
                ->wherePivot('status', 'accepted')
                ->where('users.id', $wheelchair->user_id)
                ->exists();
            if (!$isCompanion) {
                return $this->errorResponse('Unauthorized to initialize location for this wheelchair.', 403);
            }
        }

        $wheelchair->update([
            'current_floor_id' => $validated['floor_id'],
            'x_coordinate' => $validated['x_coordinate'],
            'y_coordinate' => $validated['y_coordinate'],
            'theta' => $validated['theta'] ?? $wheelchair->theta,
        ]);

        broadcast(new WheelchairUpdated($wheelchair));

        return $this->successResponse('Location initialized successfully.', parameters: ['data' => $wheelchair]);
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

        // Fetch from Redis first to get real-time data before MySQL sync
        $redisKey = "latest_vital_state_{$wheelchairId}";
        $cachedState = null;
        $warning = null;
        
        try {
            $cachedState = \Illuminate\Support\Facades\Redis::get($redisKey);
        } catch (\Exception $e) {
            $warning = 'Redis connection failed. Falling back to MySQL.';
        }

        if ($cachedState) {
            $aiData = json_decode($cachedState);
        } else {
            // Fallback to MySQL
            $aiData = $wheelchair->aiRecommendations()->latest()->first();
        }

        return $this->successResponse('Vital state retrieved.', parameters: [
            'data' => $aiData,
            'warning' => $warning
        ]);
    }

    /**
     * Update current vital state and record an event.
     */
    public function updateCurrentVitalState(Request $request): JsonResponse
    {
        $validated = $request->validate([
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
            // Auto-detect active trip
            $activeTrip = \App\Models\Trip::where('wheelchair_id', $wheelchair->id)
                ->where('status', 'started')
                ->first();

            $warning = null;
            
            // Buffer vital state safely
            $vitalData = $validated;
            $vitalData['wheelchair_id'] = $wheelchair->id;
            
            try {
                \Illuminate\Support\Facades\Redis::rpush('buffer:vital_states', json_encode($vitalData));
                // Save latest state to Redis key for immediate retrieval via showVitals API
                \Illuminate\Support\Facades\Redis::setex("latest_vital_state_{$wheelchair->id}", 600, json_encode($vitalData));
                $aiData = (object) $vitalData;
            } catch (\Exception $e) {
                $warning .= 'Redis connection failed. Falling back to MySQL. ';
                $aiData = $wheelchair->aiRecommendations()->updateOrCreate(
                    ['wheelchair_id' => $wheelchair->id],
                    $validated
                );
            }

            // Record event ONLY if it's not a routine 'low' risk update (Best Practice)
            if ($validated['risk_level'] !== 'low') {
                $event = Event::create([
                    'wheelchair_id' => $wheelchair->id,
                    'trip_id' => $activeTrip ? $activeTrip->id : null,
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
            }

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
                        try {
                            broadcast(new \App\Events\SosTriggered($connection->id, $payload));
                            $connection->notify(new \App\Notifications\DatabaseNotification\SosAlertNotification($user, $payload));
                            \Illuminate\Support\Facades\Log::info("Auto SOS broadcast to {$connection->name} for patient {$user->name} due to critical fall.");
                        } catch (\Exception $e) {
                            $warning .= 'SOS Broadcast failed for one or more users. ';
                        }
                    }
                }
            }

            DB::commit();

            try {
                if (isset($event)) {
                    broadcast(new WheelchairEventOccurred($event));
                }
            } catch (\Exception $e) {
                $warning .= 'Event broadcast failed. ';
            }

            // Trigger Dashboard Broadcast
            $targetUser = $wheelchair->user;
            if ($targetUser) {
                try {
                    broadcast(new \App\Events\DashboardUpdated($targetUser->id, 'user_dashboard', ['action' => 'refresh_required']));
                } catch (\Exception $e) {
                    $warning .= 'Dashboard broadcast failed. ';
                }
            }

            return $this->successResponse('Vital state updated successfully.', parameters: [
                'data' => $aiData,
                'warning' => $warning ?: null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update vitals: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get movement states of a wheelchair.
     */
    public function getMovementStatus($wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);
        $this->authorize('view', $wheelchair);

        // Fetch from Redis first to get real-time data before MySQL sync
        $redisKey = "latest_movement_state_{$wheelchairId}";
        $cachedState = null;
        $warning = null;
        
        try {
            $cachedState = \Illuminate\Support\Facades\Redis::get($redisKey);
        } catch (\Exception $e) {
            $warning = 'Redis connection failed. Falling back to MySQL.';
        }

        if ($cachedState) {
            $movementState = json_decode($cachedState);
        } else {
            // Fallback to MySQL
            $movementState = $wheelchair->movementState;
        }

        return $this->successResponse('Movement state retrieved.', parameters: [
            'data' => $movementState,
            'warning' => $warning
        ]);
    }

    /**
     * Update movement states of a wheelchair.
     */
    public function updateMovementStatus(\App\Http\Requests\Trip\UpdateMovementStateRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

        // Broadcast to UI safely
        $warning = null;
        try {
            broadcast(new \App\Events\WheelchairUpdated($wheelchair));
        } catch (\Exception $e) {
            $warning = 'Reverb/Broadcast failed. ';
        }

        // 2. Buffer movement state safely
        $movementData = $validated;
        $movementData['wheelchair_id'] = $wheelchair->id;
        
        try {
            \Illuminate\Support\Facades\Redis::rpush('buffer:movement_states', json_encode($movementData));
            // Save latest state to Redis key for immediate retrieval via getMovementStatus API
            \Illuminate\Support\Facades\Redis::setex("latest_movement_state_{$wheelchair->id}", 600, json_encode($movementData));
            
            $movementState = new \App\Models\WheelchairMovementState($movementData);
            $movementState->wheelchair = $wheelchair;
        } catch (\Exception $e) {
            $warning .= 'Redis connection failed. Falling back to MySQL. ';
            $movementState = $wheelchair->movementState()->updateOrCreate(
                ['wheelchair_id' => $wheelchair->id],
                $validated
            );
            $movementState->load('wheelchair');
        }

        try {
            broadcast(new \App\Events\WheelchairMovementStatusUpdated($movementState));
        } catch (\Exception $e) {
            // Already appended warning if Reverb failed above
            if (!$warning) $warning = 'Reverb/Broadcast failed.';
        }

        return $this->successResponse('Wheelchair movement state processed successfully.', parameters: [
            'data' => [
                'wheelchair' => $wheelchair,
                'movement_state' => $movementState
            ],
            'warning' => $warning ?: null
        ]);
    }
}
