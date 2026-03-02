<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Validation\Rule;

class RoleRequest extends \Illuminate\Foundation\Http\FormRequest
{
    public const ADMIN_PERMISSION_NAMES = [
        'manage_acl',
        'manage_users',
        'access_database',
    ];

    /**
     * Determine whether the request is authorized.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null
            && $user->can('manage_acl')
            && (!$this->isManagingAdministratorRole() || $user->isAdmin())
            && (!$this->containsAdminPermissions() || $user->isAdmin());
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;
        $permissionExistsRule = Rule::exists(Permission::class, 'id');

        if (!($this->user()?->isAdmin() ?? false)) {
            $permissionExistsRule = Rule::exists(Permission::class, 'id')
                ->whereNotIn('name', self::ADMIN_PERMISSION_NAMES);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Role::class, 'name')->ignore($roleId),
            ],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_hidden' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', $permissionExistsRule],
        ];
    }

    /**
     * Check if request targets Administrator role.
     *
     * @return bool
     */
    private function isManagingAdministratorRole(): bool
    {
        return $this->route('role')?->name === 'Administrator';
    }

    /**
     * Check if requested permissions include admin-level permissions.
     *
     * @return bool
     */
    private function containsAdminPermissions(): bool
    {
        $permissionIds = collect((array) $this->input('permissions', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($permissionIds->isEmpty()) {
            return false;
        }

        return Permission::query()
            ->whereIn('id', $permissionIds)
            ->whereIn('name', self::ADMIN_PERMISSION_NAMES)
            ->exists();
    }
}

