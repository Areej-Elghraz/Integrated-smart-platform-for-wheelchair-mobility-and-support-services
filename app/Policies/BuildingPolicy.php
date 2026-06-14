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

        // Must be able to view the organization first
        if (!$user->can('view', $building->organization)) {
            return false;
        }

        // Global organization (Admin's organization)
        if (is_null($building->organization->owner_id)) {
            // System Admin's building (null) or user's own building
            if (is_null($building->owner_id) || $building->owner_id == $user->id) {
                return true;
            }
            // Companion check
            if ($user->isCompanion()) {
                $connectedUser = $user->connectedUserForCompanion;
                if ($connectedUser && $building->owner_id == $connectedUser->id) {
                    return true;
                }
            }
            return false; // Can't view other users' private buildings in a global org
        }

        return true; // For private organizations, view is handled by org policy
    }

    /**
     * Determine whether the user can create buildings.
     */
    public function create(User $user, $parent): bool
    {
        if ($user->isDoctor()) return false;
        
        // Anyone can create a building if they can view the organization.
        // For Global orgs, it will be saved with their owner_id.
        return $user->can('view', $parent);
    }

    /**
     * Determine whether the user can update the building.
     */
    public function update(User $user, Building $building): bool
    {
        if ($user->isDoctor()) return false;

        // If they own the building, they can update it
        if ($building->owner_id == $user->id) {
            return true;
        }

        // Companion acting on behalf of connected User
        if ($user->isCompanion()) {
            $connectedUser = $user->connectedUserForCompanion;
            if ($connectedUser && $building->owner_id == $connectedUser->id) {
                return true;
            }
        }

        // If it's a global building (owner_id is null) in a global org, only system admin can update it.
        // If it's an organization admin, they own their org, so check if they can update the org.
        if (is_null($building->owner_id)) {
            if ($user->isAdmin() || $user->role === \App\Enums\UserRoleEnum::SUPER_ADMIN->value) {
                return true;
            }
        }

        if ($building->organization) {
            return $user->can('update', $building->organization);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the building.
     */
    public function delete(User $user, Building $building): bool
    {
        return $this->update($user, $building);
    }
}
