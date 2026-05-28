<?php

namespace App\Policies;

use App\Models\Map;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MapPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the map.
     */
    public function view(User $user, Map $map): bool
    {
        return $user->can('view', $map->floor);
    }

    /**
     * Determine whether the user can create a map on a floor.
     */
    public function create(User $user, $floor): bool
    {
        return $user->can('update', $floor);
    }

    /**
     * Determine whether the user can update the map.
     */
    public function update(User $user, Map $map): bool
    {
        return $user->can('update', $map->floor);
    }

    /**
     * Determine whether the user can delete the map.
     */
    public function delete(User $user, Map $map): bool
    {
        return $this->update($user, $map);
    }
}
