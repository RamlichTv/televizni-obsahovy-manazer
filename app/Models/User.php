<?php

namespace App\Models;

use App\Support\Avatar\DefaultAvatar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'username',
        'email',
        'password',
        'name',
        'avatar',
        'dashboard_layout',
    ];

    protected $casts = [
        'dashboard_layout' => 'array',
    ];

    /**
     * Booted.
     *
     * @return void
     */
    protected static function booted() : void {
        static::creating(fn ($model) => $model->id ??= (string) Str::uuid());
    }

    /**
     * Roles.
     *
     * @return BelongsToMany
     */
    public function roles() : BelongsToMany {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Permissions.
     *
     * @return BelongsToMany
     */
    public function permissions() : BelongsToMany {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    /**
     * Events.
     *
     * @return HasMany
     */
    public function events() : HasMany {
        return $this->hasMany(Event::class);
    }

    /**
     * Modules.
     *
     * @return HasMany
     */
    public function modules() : HasMany
    {
        return $this->hasMany(Module::class, 'created_by');
    }

    /**
     * Profiles.
     *
     * @return HasMany
     */
    public function profiles() : HasMany
    {
        return $this->hasMany(Profile::class, 'created_by');
    }

    /**
     * Content objects.
     *
     * @return HasMany
     */
    public function content_objects() : HasMany {
        return $this->hasMany(ContentObject::class, 'author_id');
    }

    /**
     * Determine whether admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->roles->contains('name', 'Administrator');
    }

    /**
     * Determine whether permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission) : bool {
        if ($this->isAdmin())
            return true;

        if ($this->permissions->contains('name', $permission))
            return true;

        return $this->roles->flatMap->permissions->contains('name', $permission);
    }

    /**
     * Avatar path.
     *
     * @return string|null
     */
    public function avatarPath(): ?string
    {
        if ($this->avatar === null || $this->avatar === '') {
            return null;
        }

        return 'users/' . $this->username . '/' . $this->avatar;
    }

    /**
     * Avatar url.
     *
     * @return string
     */
    public function avatarUrl(): string
    {
        $path = $this->avatarPath();
        if ($path !== null && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return $this->defaultAvatarDataUri();
    }

    /**
     * Default avatar data uri.
     *
     * @return string
     */
    public function defaultAvatarDataUri(): string
    {
        return DefaultAvatar::forUser($this);
    }
}

