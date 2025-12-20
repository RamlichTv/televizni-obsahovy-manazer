<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'is_hidden'];

    public function permissions() : BelongsToMany {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users() : BelongsToMany {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    public function createdBy() : BelongsTo {
        return $this->belongsTo(User::class);
    }
}
