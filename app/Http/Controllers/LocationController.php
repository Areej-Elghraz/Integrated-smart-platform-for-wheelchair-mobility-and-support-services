<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Events\UserLocationUpdated;
use Illuminate\Support\Facades\Cache;

class LocationController extends ApiController
{
    /**
     * Broadcast User's GPS Location (Flutter -> Companion)
     */
    public function userLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user();

        \Illuminate\Support\Facades\Cache::put('user_location_' . $user->id, [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'updated_at' => now(),
        ], now()->addHours(24));

        broadcast(new UserLocationUpdated($user->id, $validated['latitude'], $validated['longitude']));

        // Check
        // $key = 'user_location_' . $user->id;
        // return response()->json([
        //     'key' => $key,
        //     'saved' => Cache::get($key),
        // ]);

        return $this->successResponse('User location broadcasted and cached.');
    }

    /**
     * Get User's Live Location (For Companion)
     */
    public function companionLocation(Request $request): JsonResponse
    {
        $companion = $request->user();
        if (!$companion->isCompanion()) {
            return $this->errorResponse('Only companions can access this endpoint.', 403);
        }

        $user = $companion->connectedUserForCompanion;
        if (!$user) {
            return $this->errorResponse('No connected user found.', 404);
        }

        $outdoorLocation = \Illuminate\Support\Facades\Cache::get('user_location_' . $user->id);

        $indoorLocation = null;
        $wheelchair = $user->wheelchairs()->where('connection_state', 'online')->first() ?? $user->wheelchairs()->first();
        if ($wheelchair && $wheelchair->current_floor_id) {
            $indoorLocation = [
                'x' => $wheelchair->x_coordinate ?? 0,
                'y' => $wheelchair->y_coordinate ?? 0,
                'theta' => $wheelchair->theta ?? 0,
                'floor_id' => $wheelchair->current_floor_id,
                'updated_at' => $wheelchair->updated_at,
            ];
        }

        return $this->successResponse('Location retrieved.', parameters: [
            'data' => [
                'user_id' => $user->id,
                'outdoor' => $outdoorLocation,
                'indoor' => $indoorLocation,
            ]
        ]);
    }
}
