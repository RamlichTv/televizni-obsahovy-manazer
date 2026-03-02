<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    /**
     * Update the current resource.
     *
     * @param User $user
     * @param Module $module
     * @return bool
     */
    public function update(User $user, Module $module): bool
    {
        if ($user->hasPermission('edit_modules')) {
            if ($module->isSystem()) {
                return $user->isAdmin();
            }

            return true;
        } elseif ($user->id === $module->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Delete the current resource.
     *
     * @param User $user
     * @param Module $module
     * @return bool
     */
    public function delete(User $user, Module $module): bool
    {
        return $user->hasPermission('delete_modules') && !$module->isSystem();
    }
}

