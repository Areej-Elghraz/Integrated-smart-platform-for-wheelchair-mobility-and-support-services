<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
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
    public function view(User $user, Organization $organization): bool
    {
        if ($user->isDoctor()) {
            return false;
        }

        // organization role user
        if ($user->isOrganization()) {
            $org = $user->organizationRoleOrganization();
            return $org
                && $organization->owner_id == $user->id
                && $org->id == $organization->id;
        }
        // normal user role
        return
            // public organizations (no owner)
            is_null($organization->owner_id)
            // created by organization role users
            || optional($organization->owner)->isOrganization()
            // created by this user
            || $organization->owner_id == $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isDoctor()) {
            return false;
        }

        if ($user->isOrganization()) {
            return !($user->organizations()->count() >= 1);
        }
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool
    {
        if ($user->isDoctor()) {
            return false;
        }

        // organization role user
        if ($user->isOrganization()) {
            $org = $user->organizationRoleOrganization();
            return $org
                && $organization->owner_id == $user->id
                && $org->id == $organization->id;
        }

        // Companion acting on behalf of connected User
        if ($user->isCompanion()) {
            $connectedUser = $user->connectedUserForCompanion;
            if ($connectedUser && $organization->owner_id == $connectedUser->id) {
                return true;
            }
        }

        // normal user role
        return $organization->owner_id == $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $this->update($user, $organization);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organization $organization): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }

    /**
     * Determine whether the user can visit the model.
     */
    public function visit(User $user, Organization $organization): bool
    {
        return $user->isUser() || $user->isCompanion() || $user->isDoctor();
    }
}
