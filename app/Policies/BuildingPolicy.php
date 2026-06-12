<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BuildingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the building.
     */
    public function view(User $user, Building $building): bool
    {
        if (!$building->organization) {
            return true;
        }
        return $user->can('view', $building->organization);
    }

    /**
     * Determine whether the user can create buildings.
     */
    public function create(User $user, $parent): bool
    {
        return $user->can('update', $parent);
    }

    /**
     * Determine whether the user can update the building.
     */
    public function update(User $user, Building $building): bool
    {
        if (!$building->organization) {
            return false;
        }
        return $user->can('update', $building->organization);
    }

    /**
     * Determine whether the user can delete the building.
     */
    public function delete(User $user, Building $building): bool
    {
        return $this->update($user, $building);
    }
}
