<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\Services\UserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * User management controller for CRUD operations, ACL assignment, and audit logging.
 */
class UsersController extends Controller
{
    /**
     * Display the index listing.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();
        $search = trim((string) $request->string('search'));
        $selectedRoleIds = collect((array) $request->input('role_ids', []));

        // Backward compatibility for legacy single-role filter query parameter.
        if ($selectedRoleIds->isEmpty() && $request->filled('role')) {
            $selectedRoleIds = collect([(string) $request->input('role')]);
        }

        // Normalize incoming filter values into valid positive integer role IDs.
        $selectedRoleIds = $selectedRoleIds
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        // Non-admin users should never see Administrator in role filter choices.
        $roles = Role::query()
            ->when(!$currentUser->isAdmin(), fn ($query) => $query->where('name', '!=', 'Administrator'))
            ->orderBy('name')
            ->get(['id', 'name', 'is_hidden', 'color']);

        // Keep index payload eager loaded to avoid N+1 when rendering role/permission chips.
        $users = User::query()
            ->with(['roles:id,name,is_hidden,color', 'permissions:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($selectedRoleIds !== [], function ($query) use ($selectedRoleIds) {
                $query->whereHas('roles', function ($roleQuery) use ($selectedRoleIds) {
                    $roleQuery->whereIn('roles.id', $selectedRoleIds);
                });
            })
            ->orderBy('username')
            ->paginate(15)
            ->withQueryString();

        return view('services.users.index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role_ids' => $selectedRoleIds,
            ],
            // Suggestions are deduplicated across name/username/email for the search datalist.
            'searchSuggestions' => User::query()
                ->orderBy('username')
                ->get(['name', 'username', 'email'])
                ->flatMap(fn (User $user) => [
                    trim((string) $user->name),
                    trim((string) $user->username),
                    trim((string) $user->email),
                ])
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    /**
     * Display the requested resource.
     *
     * @param User $user
     * @return View
     */
    public function show(User $user): View
    {
        $user->load(['roles:id,name,is_hidden,color', 'permissions:id,name']);

        return view('services.users.show', ['user' => $user]);
    }

    /**
     * Create a new resource.
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        $currentUser = $request->user();
        $canAssignDirectPermissions = $currentUser->can('manage_acl');

        // Available role options are constrained by both visibility and assignability rules.
        $roles = $this->assignableRolesQuery($currentUser)
            ->with(['permissions:id,name'])
            ->orderBy('name')
            ->get(['roles.id', 'roles.name', 'roles.is_hidden', 'roles.color']);

        return view('services.users.create', [
            'roles' => $roles,
            'permissionGroups' => $canAssignDirectPermissions ? $this->permissionGroups() : collect(),
            'canAssignRoles' => $currentUser->can('manage_users'),
            'canAssignDirectPermissions' => $canAssignDirectPermissions,
        ]);
    }

    /**
     * Store a newly created resource.
     *
     * @param UserRequest $request
     * @return RedirectResponse
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $actorId = $request->user()?->id;

        $user = User::create([
            'username' => $validated['username'],
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $rolesToSync = [];
        $permissionsToSync = [];

        // Avatar upload is optional and handled separately from validated scalar fields.
        if ($request->hasFile('avatar')) {
            $user->avatar = $this->storeAvatar($request->file('avatar'), $user->username);
            $user->save();
        }

        // Role assignment is only available to actors with manage_users.
        if ($request->user()->can('manage_users')) {
            $rolesToSync = collect($validated['roles'] ?? [])
                ->map(fn ($roleId) => (int) $roleId)
                ->intersect($this->assignableRoleIds($request->user()))
                ->values()
                ->all();

            $user->roles()->sync($rolesToSync);

            // Direct permissions are stricter and require manage_acl.
            if ($request->user()->can('manage_acl')) {
                $permissionsToSync = collect($validated['permissions'] ?? [])
                    ->map(fn ($permissionId) => (int) $permissionId)
                    ->values()
                    ->all();

                $user->permissions()->sync($permissionsToSync);
            }
        }

        $this->logEvent(
            typeName: 'user_created',
            message: 'User "' . $user->username . '" was created.',
            context: [
                'action' => 'users.store',
                'target_user_id' => $user->id,
                'target_username' => $user->username,
                'assigned_role_ids' => $rolesToSync,
                'assigned_permission_ids' => $permissionsToSync,
            ],
            userId: $actorId,
        );

        if ($rolesToSync !== []) {
            $this->logEvent(
                typeName: 'role_assigned',
                message: 'Roles were assigned to user "' . $user->username . '".',
                context: [
                    'action' => 'users.store.roles.sync',
                    'target_user_id' => $user->id,
                    'target_username' => $user->username,
                    'role_ids' => $rolesToSync,
                ],
                userId: $actorId,
            );
        }

        if ($permissionsToSync !== []) {
            $this->logEvent(
                typeName: 'permission_assigned',
                message: 'Permissions were assigned to user "' . $user->username . '".',
                context: [
                    'action' => 'users.store.permissions.sync',
                    'target_user_id' => $user->id,
                    'target_username' => $user->username,
                    'permission_ids' => $permissionsToSync,
                ],
                userId: $actorId,
            );
        }

        return redirect()
            ->route('users.show', ['user' => $user])
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the edit form for the resource.
     *
     * @param Request $request
     * @param User $user
     * @return View
     */
    public function edit(Request $request, User $user): View
    {
        $currentUser = $request->user();
        $canAssignDirectPermissions = $currentUser->can('manage_acl');
        $user->load(['roles:id,name,is_hidden,color', 'permissions:id,name']);

        // Role picker receives only roles this actor is allowed to assign.
        $roles = $this->assignableRolesQuery($currentUser)
            ->with(['permissions:id,name'])
            ->orderBy('name')
            ->get(['roles.id', 'roles.name', 'roles.is_hidden', 'roles.color']);

        return view('services.users.edit', [
            'user' => $user,
            'roles' => $roles,
            'permissionGroups' => $canAssignDirectPermissions ? $this->permissionGroups() : collect(),
            'canAssignRoles' => $currentUser->can('manage_users'),
            'canAssignDirectPermissions' => $canAssignDirectPermissions,
        ]);
    }

    /**
     * Update the current resource.
     *
     * @param UserRequest $request
     * @param User $user
     * @return RedirectResponse
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $actorId = $request->user()?->id;

        // Snapshot old values to emit precise audit entries after update.
        $before = [
            'name' => $user->name,
            'email' => $user->email,
        ];
        $previousRoleIds = $user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->all();
        $previousPermissionIds = $user->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();

        // Defaults preserve existing ACL when actor lacks assignment permissions.
        $rolesToSync = $previousRoleIds;
        $permissionsToSync = $previousPermissionIds;

        $user->fill(Arr::only($validated, ['name', 'email']));

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            $this->deleteAvatar($user);
            $user->avatar = $this->storeAvatar($request->file('avatar'), $user->username);
        }

        $user->save();

        if ($request->user()->can('manage_users')) {
            $rolesToSync = collect($validated['roles'] ?? [])
                ->map(fn ($roleId) => (int) $roleId)
                ->intersect($this->assignableRoleIds($request->user()))
                ->values()
                ->all();

            // Limited managers cannot accidentally strip protected roles from the target user.
            if (!$request->user()->can('manage_acl')) {
                $protectedRoleIds = $this->protectedRoleIdsForLimitedManager($user);
                $rolesToSync = array_values(array_unique([...$rolesToSync, ...$protectedRoleIds]));
            }

            $user->roles()->sync($rolesToSync);

            if ($request->user()->can('manage_acl')) {
                $permissionsToSync = collect($validated['permissions'] ?? [])
                    ->map(fn ($permissionId) => (int) $permissionId)
                    ->values()
                    ->all();

                $user->permissions()->sync($permissionsToSync);
            }
        }

        // Compute ACL deltas for granular event logging.
        $currentRoleIds = $user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->all();
        $currentPermissionIds = $user->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();
        $addedRoleIds = array_values(array_diff($currentRoleIds, $previousRoleIds));
        $removedRoleIds = array_values(array_diff($previousRoleIds, $currentRoleIds));
        $addedPermissionIds = array_values(array_diff($currentPermissionIds, $previousPermissionIds));
        $removedPermissionIds = array_values(array_diff($previousPermissionIds, $currentPermissionIds));

        $this->logEvent(
            typeName: 'user_edited',
            message: 'User "' . $user->username . '" was updated.',
            context: [
                'action' => 'users.update',
                'target_user_id' => $user->id,
                'target_username' => $user->username,
                'old_name' => $before['name'],
                'new_name' => $user->name,
                'old_email' => $before['email'],
                'new_email' => $user->email,
                'added_role_ids' => $addedRoleIds,
                'removed_role_ids' => $removedRoleIds,
                'added_permission_ids' => $addedPermissionIds,
                'removed_permission_ids' => $removedPermissionIds,
            ],
            userId: $actorId,
        );

        if ($addedRoleIds !== []) {
            $this->logEvent(
                typeName: 'role_assigned',
                message: 'Roles were assigned to user "' . $user->username . '".',
                context: [
                    'action' => 'users.update.roles.assign',
                    'target_user_id' => $user->id,
                    'target_username' => $user->username,
                    'role_ids' => $addedRoleIds,
                ],
                userId: $actorId,
            );
        }

        if ($removedRoleIds !== []) {
            $this->logEvent(
                typeName: 'role_removed',
                message: 'Roles were removed from user "' . $user->username . '".',
                context: [
                    'action' => 'users.update.roles.remove',
                    'target_user_id' => $user->id,
                    'target_username' => $user->username,
                    'role_ids' => $removedRoleIds,
                ],
                userId: $actorId,
            );
        }

        if ($addedPermissionIds !== []) {
            $this->logEvent(
                typeName: 'permission_assigned',
                message: 'Permissions were assigned to user "' . $user->username . '".',
                context: [
                    'action' => 'users.update.permissions.assign',
                    'target_user_id' => $user->id,
                    'target_username' => $user->username,
                    'permission_ids' => $addedPermissionIds,
                ],
                userId: $actorId,
            );
        }

        if ($removedPermissionIds !== []) {
            $this->logEvent(
                typeName: 'permission_removed',
                message: 'Permissions were removed from user "' . $user->username . '".',
                context: [
                    'action' => 'users.update.permissions.remove',
                    'target_user_id' => $user->id,
                    'target_username' => $user->username,
                    'permission_ids' => $removedPermissionIds,
                ],
                userId: $actorId,
            );
        }

        return redirect()
            ->route('users.show', ['user' => $user])
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete the current resource.
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Safety guard: prevent self-deletion.
        if ($request->user()->is($user)) {
            $this->logFailure(
                message: 'User deletion failed: attempted to delete own account.',
                context: [
                    'action' => 'users.destroy',
                    'target_user_id' => $user->id,
                    'target_username' => $user->username,
                ],
                userId: $request->user()?->id,
            );

            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        // Safety guard: keep at least one administrator account in the system.
        if ($this->isLastAdministrator($user)) {
            $this->logFailure(
                message: 'User deletion failed: attempted to delete last administrator account.',
                context: [
                    'action' => 'users.destroy',
                    'target_user_id' => $user->id,
                    'target_username' => $user->username,
                ],
                userId: $request->user()?->id,
            );

            return back()->withErrors(['user' => 'You cannot delete the last administrator account.']);
        }

        $targetUserId = $user->id;
        $targetUsername = $user->username;

        // Free unique constraints before delete so historical collisions cannot occur.
        $user->fill([
            'username' => 'deleted_user_' . $user->username,
            'email' => 'deleted_email_' . $user->email
        ]);

        $user->delete();

        $this->logEvent(
            typeName: 'user_deleted',
            message: 'User "' . $targetUsername . '" was deleted.',
            context: [
                'action' => 'users.destroy',
                'target_user_id' => $targetUserId,
                'target_username' => $targetUsername,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Store a newly created resource.
     *
     * @param UploadedFile $avatar
     * @param string $username
     * @return string
     */
    private function storeAvatar(UploadedFile $avatar, string $username): string
    {
        // Store each avatar with a unique filename under a user-specific directory.
        $filename = uniqid('avatar_', true) . '.' . $avatar->getClientOriginalExtension();
        $avatar->storeAs('users/' . $username, $filename, 'public');

        return $filename;
    }

    /**
     * Delete avatar.
     *
     * @param User $user
     * @return void
     */
    private function deleteAvatar(User $user): void
    {
        if ($user->avatar === null) {
            return;
        }

        // Missing files are tolerated to keep updates resilient.
        $path = 'users/' . $user->username . '/' . $user->avatar;
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Determine whether last administrator.
     *
     * @param User $user
     * @return bool
     */
    private function isLastAdministrator(User $user): bool
    {
        // Fast-path when target user is not an administrator.
        if (!$user->roles()->where('roles.name', 'Administrator')->exists()) {
            return false;
        }

        $otherAdmins = User::query()
            ->whereKeyNot($user->id)
            ->whereHas('roles', fn ($query) => $query->where('roles.name', 'Administrator'))
            ->count();

        return $otherAdmins === 0;
    }

    /**
     * Assignable roles query.
     *
     * @param User $actor
     * @return mixed
     */
    private function assignableRolesQuery(User $actor)
    {
        return Role::query()
            // Non-admin actors cannot assign hidden or Administrator roles.
            ->when(!$actor->isAdmin(), fn ($query) => $query
                ->where('roles.is_hidden', false)
                ->where('roles.name', '!=', 'Administrator'))
            // Actors without manage_acl cannot assign roles that include manage_acl permission.
            ->when(!$actor->can('manage_acl'), fn ($query) => $query
                ->whereDoesntHave('permissions', fn ($permissionQuery) => $permissionQuery
                    ->where('permissions.name', 'manage_acl')));
    }

    /**
     * Assignable role ids.
     *
     * @param User $actor
     * @return array
     */
    private function assignableRoleIds(User $actor): array
    {
        return $this->assignableRolesQuery($actor)
            ->pluck('roles.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Protected role ids for limited manager.
     *
     * @param User $managedUser
     * @return array
     */
    private function protectedRoleIdsForLimitedManager(User $managedUser): array
    {
        // Roles a limited manager is not allowed to remove from a target user.
        return $managedUser->roles()
            ->where(function ($query) {
                $query->where('roles.is_hidden', true)
                    ->orWhere('roles.name', 'Administrator')
                    ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery
                        ->where('permissions.name', 'manage_acl'));
            })
            ->pluck('roles.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Permission groups.
     *
     * @return Collection
     */
    private function permissionGroups(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->groupBy(function (Permission $permission) {
                // Grouping drives collapsible UI sections in create/edit forms.
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
