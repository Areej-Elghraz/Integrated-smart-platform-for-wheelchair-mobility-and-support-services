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

        // Collect connected companions and doctor
        $companions = collect($user->connectedCompanions);
        $doctor = $user->connectedDoctor;
        
        $allConnected = $companions;
        if ($doctor) {
            $allConnected->push($doctor);
        }

        if ($allConnected->isEmpty()) {
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

        // Fail any active trips
        $wheelchair = \App\Models\Wheelchair::where('user_id', $user->id)->first();
        if ($wheelchair) {
            \App\Models\Trip::where('wheelchair_id', $wheelchair->id)
                ->where('status', 'started')
                ->update([
                    'status' => 'failed',
                    'ended_at' => now(),
                ]);
        }

        // Dispatch the internal event. Listeners will handle Broadcast and DB Notification.
        event(new \App\Events\SosTriggeredEvent($user, $payload, $allConnected));

        return $this->successResponse('SOS alert sent to ' . $allConnected->count() . ' connected companions/doctors.', 200, ['triggered_to' => $allConnected->count()]);
    }

    /**
     * Cancel SOS alert.
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        $companions = collect($user->connectedCompanions);
        $doctor = $user->connectedDoctor;
        
        $allConnected = $companions;
        if ($doctor) {
            $allConnected->push($doctor);
        }

        foreach ($allConnected as $connection) {
            broadcast(new \App\Events\SosCancelled($connection->id, ['user_id' => $user->id, 'user_name' => $user->name]));
            Log::info("SOS CANCEL broadcast to {$connection->name} for patient {$user->name}");
        }

        return $this->successResponse('SOS alert cancelled.');
    }
}
