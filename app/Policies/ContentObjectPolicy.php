<?php

namespace App\Policies;

use App\Models\ContentObject;
use App\Models\User;

class ContentObjectPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ContentObject $contentObject): bool {
        if ($user->isAdmin()) {
            return true;
        }
        else if ($user->hasPermission('edit_content') && $contentObject->status_id !== 4 && $contentObject->status_id !== 3) {
            return true;
        }
        else if ($user->id === $contentObject->author_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ContentObject $contentObject): bool {
        return $user->hasPermission('delete_content');
    }
}
