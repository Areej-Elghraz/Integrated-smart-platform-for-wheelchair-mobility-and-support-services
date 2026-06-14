<?php

namespace App\Policies;

use App\Models\Floor;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FloorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the floor.
     */
    public function view(User $user, Floor $floor): bool
    {
        if ($floor->building) {
            return $user->can('view', $floor->building);
        }
        return true;
    }

    /**
     * Determine whether the user can create floors on a resource.
     */
    public function create(User $user, $parent): bool
    {
        return $user->can('update', $parent);
    }

    /**
     * Determine whether the user can update the floor.
     */
    public function update(User $user, Floor $floor): bool
    {
        if ($floor->building) {
            return $user->can('update', $floor->building);
        }
        return false;
    }

    /**
     * Determine whether the user can delete the floor.
     */
    public function delete(User $user, Floor $floor): bool
    {
        return $this->update($user, $floor);
    }
}
