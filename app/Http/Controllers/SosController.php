<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SosController extends ApiController
{
    /**
     * Trigger SOS alert - broadcasts to all accepted companions/doctors.
     */
    public function trigger(Request $request)
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'message' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Collect all accepted friends (companions + doctors)
        $friendsOfMine = $user->friendsOfMine()->wherePivot('status', 'accepted')->get();
        $friendOf = $user->friendOf()->wherePivot('status', 'accepted')->get();
        $allFriends = $friendsOfMine->merge($friendOf);

        if ($allFriends->isEmpty()) {
            return $this->errorResponse('No connected companions or doctors to alert.', 400);
        }

        $locationLink = ($request->latitude && $request->longitude)
            ? "https://www.openstreetmap.org/?mlat={$request->latitude}&mlon={$request->longitude}#map=18/{$request->latitude}/{$request->longitude}"
            : null;

        $payload = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
            ],
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_link' => $locationLink,
            'message' => $request->message ?? "{$user->name} is in an emergency and needs help!",
            'triggered_at' => now()->toISOString(),
        ];

        // Dispatch the internal event. Listeners will handle Broadcast and DB Notification.
        event(new \App\Events\SosTriggeredEvent($user, $payload, $allFriends));

        return $this->successResponse('SOS alert sent to ' . $allFriends->count() . ' connected companions.', 200, ['triggered_to' => $allFriends->count()]);
    }

    /**
     * Cancel SOS alert.
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        $friendsOfMine = $user->friendsOfMine()->wherePivot('status', 'accepted')->get();
        $friendOf = $user->friendOf()->wherePivot('status', 'accepted')->get();
        $allFriends = $friendsOfMine->merge($friendOf);

        foreach ($allFriends as $friend) {
            broadcast(new \App\Events\SosCancelled($friend->id, ['user_id' => $user->id, 'user_name' => $user->name]));
            Log::info("SOS CANCEL broadcast to {$friend->name} for patient {$user->name}");
        }

        return $this->successResponse('SOS alert cancelled.');
    }
}
