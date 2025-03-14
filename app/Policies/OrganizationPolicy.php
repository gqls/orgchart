<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Organization $organization)
    {
        // Check if user belongs to this organization
        return $user->organizations()->where('organization_id', $organization->id)->exists();
    }

    public function update(User $user, Organization $organization)
    {
        // Check if user is an admin of this organization
        return $user->organizations()->where('organization_id', $organization->id)
            ->wherePivot('is_admin', true)->exists();
    }

    public function delete(User $user, Organization $organization)
    {
        // Only admin can delete
        return $user->organizations()->where('organization_id', $organization->id)
            ->wherePivot('is_admin', true)->exists();
    }
}