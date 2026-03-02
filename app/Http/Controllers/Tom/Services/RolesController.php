<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\Services\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class RolesController extends Controller
{
    /**
     * Display the index listing.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request) : View
    {
        $currentUser = $request->user();
        $search = trim((string) $request->string('search'));

        // Non-admin users cannot browse hidden or Administrator role entries.
        $visibleRolesQuery = Role::query()
            ->when(!$currentUser->isAdmin(), fn ($query) => $query
                ->where('is_hidden', false)
                ->where('name', '!=', 'Administrator'));

        $roles = (clone $visibleRolesQuery)
            ->withCount(['permissions', 'users'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('services.roles.index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
            'searchSuggestions' => (clone $visibleRolesQuery)
                ->orderBy('name')
                ->pluck('name')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    /**
     * Display the requested resource.
     *
     * @param Request $request
     * @param Role $role
     * @return View
     */
    public function show(Request $request, Role $role) : View
    {
        $this->authorizeRoleVisibility($request, $role);
        $this->authorizeAdministratorRoleManagement($request, $role);

        $role->load([
            'permissions:id,name',
            'users:id,username,name',
            'createdBy:id,username,name',
        ]);

        return view('services.roles.show', ['role' => $role]);
    }

    /**
     * Create a new resource.
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request) : View
    {
        // Permissions are pre-filtered by current actor capabilities.
        $permissions = $this->getAssignablePermissions($request);

        return view('services.roles.create', [
            'permissions' => $permissions,
            'permissionGroups' => $this->permissionGroups($permissions),
            'canManageHiddenRoles' => $request->user()->isAdmin(),
        ]);
    }

    /**
     * Store a newly created resource.
     *
     * @param RoleRequest $request
     * @return RedirectResponse
     */
    public function store(RoleRequest $request) : RedirectResponse
    {
        $validated = $request->validated();
        $permissionIds = $this->resolvePermissionIds($request);

        $role = Role::create([
            'name' => $validated['name'],
            'color' => strtoupper($validated['color'] ?? '#D0D0D0'),
            'is_hidden' => $request->user()->isAdmin() ? (bool) ($validated['is_hidden'] ?? false) : false,
            'created_by' => $request->user()->id,
        ]);

        $role->permissions()->sync($permissionIds);

        $this->logEvent(
            typeName: 'role_created',
            message: 'Role "' . $role->name . '" was created.',
            context: [
                'action' => 'roles.store',
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permission_ids' => $permissionIds,
            ],
            userId: $request->user()?->id,
        );

        if ($permissionIds !== []) {
            $this->logEvent(
                typeName: 'permission_assigned',
                message: 'Permissions were assigned to role "' . $role->name . '".',
                context: [
                    'action' => 'roles.store.permissions.sync',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permission_ids' => $permissionIds,
                ],
                userId: $request->user()?->id,
            );
        }

        return redirect()
            ->route('role.edit', ['role' => $role])
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show the edit form for the resource.
     *
     * @param Request $request
     * @param Role $role
     * @return View
     */
    public function edit(Request $request, Role $role) : View
    {
        $this->authorizeRoleVisibility($request, $role);
        $this->authorizeAdministratorRoleManagement($request, $role);
        $role->load('permissions:id,name');
        // Use same filtered permission source as create to keep UX and backend consistent.
        $permissions = $this->getAssignablePermissions($request);

        return view('services.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'permissionGroups' => $this->permissionGroups($permissions),
            'canManageHiddenRoles' => $request->user()->isAdmin(),
        ]);
    }

    /**
     * Update the current resource.
     *
     * @param RoleRequest $request
     * @param Role $role
     * @return RedirectResponse
     */
    public function update(RoleRequest $request, Role $role) : RedirectResponse
    {
        $this->authorizeRoleVisibility($request, $role);
        $this->authorizeAdministratorRoleManagement($request, $role);

        $validated = $request->validated();
        $before = [
            'name' => $role->name,
            'color' => $role->color,
            'is_hidden' => (bool) $role->is_hidden,
        ];
        $previousPermissionIds = $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();

        $role->fill([
            'name' => $validated['name'],
            'color' => strtoupper($validated['color'] ?? '#D0D0D0'),
        ]);

        if ($request->user()->isAdmin()) {
            $role->is_hidden = (bool) ($validated['is_hidden'] ?? false);
        }

        $role->save();

        $permissionIds = $this->resolvePermissionIds($request, $role);

        $role->permissions()->sync($permissionIds);

        $currentPermissionIds = $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();
        $addedPermissionIds = array_values(array_diff($currentPermissionIds, $previousPermissionIds));
        $removedPermissionIds = array_values(array_diff($previousPermissionIds, $currentPermissionIds));

        $this->logEvent(
            typeName: 'role_edited',
            message: 'Role "' . $role->name . '" was updated.',
            context: [
                'action' => 'roles.update',
                'role_id' => $role->id,
                'role_name' => $role->name,
                'old_name' => $before['name'],
                'new_name' => $role->name,
                'old_color' => $before['color'],
                'new_color' => $role->color,
                'old_is_hidden' => $before['is_hidden'],
                'new_is_hidden' => (bool) $role->is_hidden,
                'added_permission_ids' => $addedPermissionIds,
                'removed_permission_ids' => $removedPermissionIds,
            ],
            userId: $request->user()?->id,
        );

        if ($addedPermissionIds !== []) {
            $this->logEvent(
                typeName: 'permission_assigned',
                message: 'Permissions were assigned to role "' . $role->name . '".',
                context: [
                    'action' => 'roles.update.permissions.assign',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permission_ids' => $addedPermissionIds,
                ],
                userId: $request->user()?->id,
            );
        }

        if ($removedPermissionIds !== []) {
            $this->logEvent(
                typeName: 'permission_removed',
                message: 'Permissions were removed from role "' . $role->name . '".',
                context: [
                    'action' => 'roles.update.permissions.remove',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permission_ids' => $removedPermissionIds,
                ],
                userId: $request->user()?->id,
            );
        }

        return redirect()
            ->route('role.edit', ['role' => $role])
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Delete the current resource.
     *
     * @param Request $request
     * @param Role $role
     * @return RedirectResponse
     */
    public function destroy(Request $request, Role $role) : RedirectResponse
    {
        $this->authorizeRoleVisibility($request, $role);
        $this->authorizeAdministratorRoleManagement($request, $role);

        if ($role->name === 'Administrator') {
            $this->logFailure(
                message: 'Role deletion failed: Administrator role cannot be deleted.',
                context: [
                    'action' => 'roles.destroy',
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                ],
                userId: $request->user()?->id,
            );

            return back()->withErrors(['role' => 'The Administrator role cannot be deleted.']);
        }

        $roleId = $role->id;
        $roleName = $role->name;
        $role->delete();

        $this->logEvent(
            typeName: 'role_deleted',
            message: 'Role "' . $roleName . '" was deleted.',
            context: [
                'action' => 'roles.destroy',
                'role_id' => $roleId,
                'role_name' => $roleName,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Authorize role visibility.
     *
     * @param Request $request
     * @param Role $role
     * @return void
     */
    private function authorizeRoleVisibility(Request $request, Role $role) : void
    {
        if ($role->is_hidden && !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Authorize administrator role management.
     *
     * @param Request $request
     * @param Role $role
     * @return void
     */
    private function authorizeAdministratorRoleManagement(Request $request, Role $role) : void
    {
        if ($role->name === 'Administrator' && !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Get permissions assignable by the current user.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection<int, Permission>
     */
    private function getAssignablePermissions(Request $request)
    {
        return Permission::query()
            ->when(
                // Limited ACL managers can work with roles,
                // but cannot assign admin-level permissions.
                !$request->user()->isAdmin(),
                fn ($query) => $query->whereNotIn('name', RoleRequest::ADMIN_PERMISSION_NAMES)
            )
            ->get(['id', 'name']);
    }

    /**
     * Resolve permission ids for sync with admin-permission protection.
     *
     * @param RoleRequest $request
     * @param Role|null $role
     * @return array<int, int>
     */
    private function resolvePermissionIds(RoleRequest $request, ?Role $role = null): array
    {
        // Normalize incoming ids from request payload.
        $submittedPermissionIds = collect($request->validated('permissions', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        // Admins can sync exactly what they submit.
        if ($request->user()->isAdmin()) {
            return $submittedPermissionIds->all();
        }

        $adminPermissionIds = Permission::query()
            ->whereIn('name', RoleRequest::ADMIN_PERMISSION_NAMES)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($adminPermissionIds === []) {
            return $submittedPermissionIds->all();
        }

        // During create, non-admins cannot add admin permissions.
        if ($role === null) {
            return $submittedPermissionIds
                ->reject(fn ($id) => in_array($id, $adminPermissionIds, true))
                ->values()
                ->all();
        }

        // During update, preserve already assigned admin permissions;
        // non-admins cannot remove those by omission.
        $existingAdminPermissionIds = $role->permissions()
            ->whereIn('permissions.id', $adminPermissionIds)
            ->pluck('permissions.id')
            ->map(fn ($id) => (int) $id);

        return $submittedPermissionIds
            ->merge($existingAdminPermissionIds)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Permission groups.
     *
     * @param Collection<int, Permission> $permissions
     * @return Collection
     */
    private function permissionGroups(Collection $permissions): Collection
    {
        // Keep group labels aligned with user direct-permissions grouping.
        return $permissions->groupBy(function (Permission $permission) {
            $name = $permission->name;

            if (str_contains($name, 'content') || in_array($name, ['manage_archived', 'manage_tags', 'bypass_max_size'], true)) {
                return 'Content';
            }

            if (str_contains($name, 'screen')) {
                return 'Screens';
            }

            if (str_contains($name, 'profile')) {
                return 'Profiles';
            }

            if (str_contains($name, 'module')) {
                return 'Modules';
            }

            if (in_array($name, ['manage_acl', 'manage_users', 'access_database', 'view_events'], true)) {
                return 'Administration';
            }

            return 'Other';
        });
    }
}
