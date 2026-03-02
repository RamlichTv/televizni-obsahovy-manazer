<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;

class UserRequest extends \Illuminate\Foundation\Http\FormRequest
{
    // User management request:
    // validates profile fields and guards role/permission assignment scope by actor capability.

    /**
     * Determine whether the request is authorized.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_users') ?? false;
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isCreate = $this->isMethod('post');
        $isAdmin = $this->user()?->isAdmin() ?? false;
        $canManageAcl = $this->user()?->can('manage_acl') ?? false;
        // Restrict role assignment to what current actor is allowed to assign.
        $assignableRoleIds = $this->assignableRoleIds($isAdmin, $canManageAcl);

        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($userId),
            ],
            'avatar' => ['nullable', 'image:allow_svg', 'max:2048'],
            'password' => [$isCreate ? 'required' : 'nullable', 'confirmed', Rules\Password::defaults()],
        ];

        if ($isCreate) {
            $rules['username'] = [
                'required',
                'confirmed',
                'string',
                'min:3',
                'max:32',
                'alpha_dash:ascii',
                Rule::unique(User::class, 'username'),
            ];
        }

        $rules['roles'] = ['nullable', 'array'];
        $rules['roles.*'] = ['integer', Rule::in($assignableRoleIds)];

        if ($canManageAcl) {
            // Direct user permissions are allowed only for ACL managers.
            $rules['permissions'] = ['nullable', 'array'];
            $rules['permissions.*'] = ['integer', Rule::exists(Permission::class, 'id')];
        } else {
            // Explicitly reject direct permissions payload for non-ACL managers.
            $rules['permissions'] = ['prohibited'];
        }

        return $rules;
    }

    /**
     * Assignable role ids.
     *
     * @param bool $isAdmin
     * @param bool $canManageAcl
     * @return array
     */
    private function assignableRoleIds(bool $isAdmin, bool $canManageAcl): array
    {
        return Role::query()
            // Non-admins cannot assign hidden or Administrator role.
            ->when(!$isAdmin, fn ($query) => $query
                ->where('is_hidden', false)
                ->where('name', '!=', 'Administrator'))
            // Non-ACL managers cannot assign roles that carry manage_acl permission.
            ->when(!$canManageAcl, fn ($query) => $query
                ->whereDoesntHave('permissions', fn ($permissionQuery) => $permissionQuery
                    ->where('permissions.name', 'manage_acl')))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}

