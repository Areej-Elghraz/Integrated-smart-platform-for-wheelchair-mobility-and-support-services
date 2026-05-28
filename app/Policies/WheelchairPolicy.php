<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wheelchair;
use Illuminate\Auth\Access\HandlesAuthorization;

class WheelchairPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Wheelchair $wheelchair): bool
    {
        if ((int) $user->id === (int) $wheelchair->user_id) {
            return true;
        }

        if (in_array($user->role, ['companion', 'doctor'])) {
            return $user->friends()->wherePivot('status', 'accepted')->where('users.id', $wheelchair->user_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Wheelchair $wheelchair): bool
    {
        if ((int) $user->id === (int) $wheelchair->user_id) {
            return true;
        }

        if ($user->role === 'companion') {
            return $user->friends()->wherePivot('status', 'accepted')->where('users.id', $wheelchair->user_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Wheelchair $wheelchair): bool
    {
        return (int) $user->id === (int) $wheelchair->user_id;
    }
}
