<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'username',
        'email',
        'password',
        'name',
        'avatar',
    ];

    protected static function booted() : void {
        static::creating(fn ($model) => $model->id ??= (string) Str::uuid());
    }

    public function roles() : BelongsToMany {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function permissions() : BelongsToMany {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    public function events() : HasMany {
        return $this->hasMany(Event::class);
    }

    public function modules() : HasMany {
        return $this->hasMany(Module::class);
    }

    public function profiles() : HasMany {
        return $this->hasMany(Profile::class);
    }

    public function content_objects() : HasMany {
        return $this->hasMany(ContentObject::class, 'author_id');
    }

    public function isAdmin(): bool
    {
        return $this->roles->contains('name', 'Administrator');
    }

    public function hasPermission(string $permission) : bool {
        if ($this->isAdmin())
            return true;

        if ($this->permissions->contains('name', $permission))
            return true;

        return $this->roles->flatMap->permissions->contains('name', $permission);
    }
}
