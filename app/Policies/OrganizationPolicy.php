<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Vérifier si l'utilisateur peut voir l'organisation
     */
    public function view(User $user, Organization $organization): bool
    {
        // Super admin : accès global
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Evite l'ambiguïté de colonne 'id' dans les requêtes join/pivot
        return $user->organizations()
            ->where('organizations.id', $organization->id)
            ->exists();
    }


    /**
     * Vérifier si l'utilisateur peut mettre à jour l'organisation
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->isAdminOfOrganization($organization);
    }

    /**
     * Vérifier si l'utilisateur peut supprimer l'organisation
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $user->isOwnerOfOrganization($organization);
    }

    /**
     * Vérifier si l'utilisateur peut inviter des membres
     */
    public function inviteMembers(User $user, Organization $organization): bool
    {
        return $user->isAdminOfOrganization($organization);
    }

    /**
     * Vérifier si l'utilisateur peut gérer les rôles des membres
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->isAdminOfOrganization($organization);
    }
}
