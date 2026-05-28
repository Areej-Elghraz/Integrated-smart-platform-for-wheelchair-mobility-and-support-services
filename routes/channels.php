<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('wheelchairs.{wheelchairId}', function ($user, $wheelchairId) {
    $wheelchair = \App\Models\Wheelchair::find($wheelchairId);
    return $wheelchair && ((int) $user->id === (int) $wheelchair->user_id);
});

Broadcast::channel('trips.{tripId}', function ($user, $tripId) {
    $trip = \App\Models\Trip::with('wheelchair')->find($tripId);
    return $trip && $trip->wheelchair && ((int) $user->id === (int) $trip->wheelchair->user_id);
});

Broadcast::channel('dashboard.{userId}', function ($user, $userId) {
    if ((int) $user->id === (int) $userId) {
        return true;
    }
    // Check if the user is a friend (companion/doctor) of the requested userId
    return $user->friends()->wherePivot('status', 'accepted')->where('users.id', $userId)->exists();
});

// Live Location: User GPS (Flutter User -> Companion)
Broadcast::channel('user.{userId}.location', function ($user, $userId) {
    if ((int) $user->id === (int) $userId) {
        return true;
    }
    return $user->friends()->wherePivot('status', 'accepted')->where('users.id', $userId)->exists();
});

// Live Location: Wheelchair indoor ROS (Python -> Flutter)
Broadcast::channel('wheelchair.{wheelchairId}.location', function ($user, $wheelchairId) {
    $wheelchair = \App\Models\Wheelchair::find($wheelchairId);
    if (!$wheelchair) return false;

    // Owner can listen
    if ((int) $user->id === (int) $wheelchair->user_id) {
        return true;
    }
    // Companion/Doctor friends can listen
    return $user->friends()->wherePivot('status', 'accepted')->where('users.id', $wheelchair->user_id)->exists();
});

