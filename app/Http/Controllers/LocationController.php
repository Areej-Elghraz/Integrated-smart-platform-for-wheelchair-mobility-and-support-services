<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Events\UserLocationUpdated;

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

        broadcast(new UserLocationUpdated($user->id, $validated['latitude'], $validated['longitude']));

        return $this->successResponse('User location broadcasted.');
    }
}
