<?php

namespace App\Policies;

use App\Models\Place;
use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Auth\Access\Response;

class PlacePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Place $place): bool
    {
        if ($user->isOrganization()) {
            $org = $user->organizationRoleOrganization();
            return $org
                && $place->owner_id == $user->id
                && $place->floor?->building?->organization_id == $org->id;
        }

        if (is_null($place->owner_id) || optional($place->owner)->isOrganization() || $place->owner_id == $user->id) {
            return true;
        }

        if ($user->isCompanion()) {
            $connectedUser = $user->connectedUserForCompanion;
            if ($connectedUser && $place->owner_id == $connectedUser->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, array $data): bool
    {
        if ($user->isDoctor()) {
            return false;
        }

        // Removed direct organization_id check since places don't hold it anymore.
        // It's inferred from the floor context in the controller.
        return true;
    }
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Place $place): bool
    {
        if ($user->isDoctor()) {
            return false;
        }

        if ($user->isOrganization()) {
            $org = $user->organizationRoleOrganization();
            return $org
                && $place->owner_id == $user->id
                && $place->floor?->building?->organization_id == $org->id;
        }
        
        // Companion acting on behalf of connected User
        if ($user->isCompanion()) {
            $connectedUser = $user->connectedUserForCompanion;
            if ($connectedUser && $place->owner_id == $connectedUser->id) {
                return true;
            }
        }

        // regular user can update only what they created
        return $place->owner_id == $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Place $place): bool
    {
        return $this->update($user, $place);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Place $place): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Place $place): bool
    {
        return false;
    }

    /**
     * Determine whether the user can visit the model.
     */
    public function visit(User $user, Place $place): bool
    {
        return $user->isUser() || $user->isCompanion() || $user->isDoctor();
    }
}
