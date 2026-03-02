<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'color', 'is_hidden', 'created_by'];

    protected $casts = [
        'is_hidden' => 'boolean'
    ];

    /**
     * Permissions.
     *
     * @return BelongsToMany
     */
    public function permissions() : BelongsToMany {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /**
     * Users.
     *
     * @return BelongsToMany
     */
    public function users() : BelongsToMany {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    /**
     * Created by.
     *
     * @return BelongsTo
     */
    public function createdBy() : BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
}

