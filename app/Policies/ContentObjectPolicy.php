<?php

namespace App\Policies;

use App\Models\ContentObject;
use App\Models\User;

class ContentObjectPolicy
{
    /**
     * Update the current resource.
     *
     * @param User $user
     * @param ContentObject $contentObject
     * @return bool
     */
    public function update(User $user, ContentObject $contentObject): bool
    {
        if ($user->isAdmin()) {
            return true;
        } elseif ($user->hasPermission('edit_content') && $contentObject->status_id !== 4 && $contentObject->status_id !== 3) {
            return true;
        } elseif ($user->id === $contentObject->author_id) {
            return true;
        }

        return false;
    }

    /**
     * Delete the current resource.
     *
     * @param User $user
     * @param ContentObject $contentObject
     * @return bool
     */
    public function delete(User $user, ContentObject $contentObject): bool
    {
        return $user->hasPermission('delete_content') || $user->id === $contentObject->author_id;
    }
}

