<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

class ProfilePolicy
{
    /**
     * Determine whether the user can view any profiles.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('create_profiles')
            || $user->hasPermission('edit_profiles')
            || $user->hasPermission('delete_profiles');
    }

    /**
     * Determine whether the user can view the profile.
     *
     * @param User $user
     * @param Profile $profile
     * @return bool
     */
    public function view(User $user, Profile $profile): bool
    {
        return $this->viewAny($user)
            || $user->id === $profile->created_by;
    }

    /**
     * Determine whether the user can create profiles.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_profiles');
    }

    /**
     * Determine whether the user can update the profile.
     *
     * @param User $user
     * @param Profile $profile
     * @return bool
     */
    public function update(User $user, Profile $profile): bool
    {
        return $user->hasPermission('edit_profiles')
            || $user->id === $profile->created_by;
    }

    /**
     * Determine whether the user can delete the profile.
     *
     * @param User $user
     * @param Profile $profile
     * @return bool
     */
    public function delete(User $user, Profile $profile): bool
    {
        return $user->hasPermission('delete_profiles');
    }
}

