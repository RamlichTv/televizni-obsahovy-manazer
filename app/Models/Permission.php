<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [];

    /**
     * Roles.
     *
     * @return BelongsToMany
     */
    public function roles() : BelongsToMany {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Users.
     *
     * @return BelongsToMany
     */
    public function users() : BelongsToMany {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id');
    }
}
