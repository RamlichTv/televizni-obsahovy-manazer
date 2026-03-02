@php
    use Illuminate\Support\Str;

    /** @var \App\Models\User|null $user */
    $isEdit = isset($user) && $user !== null;

    // Seed picker state from old input so validation errors preserve selections.
    $selectedRoleIds = collect((array) old('roles', $isEdit ? $user->roles->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)
        ->values();
    $selectedPermissionIds = collect((array) old('permissions', $isEdit ? $user->permissions->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)
        ->values();

    $canAssignRoles = $canAssignRoles ?? false;
    $canAssignDirectPermissions = $canAssignDirectPermissions ?? false;
    $permissionGroups = $permissionGroups ?? collect();

    // Shape role records for the multi-select picker (supports color dot + ACL badge).
    $rolesForPicker = collect($roles ?? [])->map(function ($role) {
        return [
            'id' => (string) $role->id,
            'name' => (string) $role->name,
            'color' => (string) ($role->color ?? '#D0D0D0'),
            'badge' => ($role->relationLoaded('permissions') && $role->permissions->contains('name', 'manage_acl')) ? 'ACL' : null,
        ];
    })->values();

    // Normalize permission groups once for both reactive counters and rendering.
    $permissionGroupsPrepared = collect($permissionGroups)->mapWithKeys(function ($groupPermissions, $groupName) {
        $groupKey = Str::slug((string) $groupName, '_');

        return [
            $groupKey => [
                'name' => $groupName,
                'permissions' => collect($groupPermissions)->values(),
            ],
        ];
    });

    $permissionGroupOpen = $permissionGroupsPrepared->keys()->mapWithKeys(fn ($key) => [$key => false]);
    $permissionGroupMap = $permissionGroupsPrepared->mapWithKeys(function ($group, $groupKey) {
        return [
            $groupKey => collect($group['permissions'])
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all(),
        ];
    });
    $totalPermissionsCount = $permissionGroupsPrepared->sum(fn ($group) => $group['permissions']->count());
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="space-y-6"
    {{-- Alpine state only manages collapsible permission UI and selection counters. --}}
    x-data='{
        permissionsOpen: false,
        selectedPermissionIds: @json($selectedPermissionIds->values()),
        permissionGroupOpen: @json($permissionGroupOpen),
        permissionGroupMap: @json($permissionGroupMap),
        togglePermissionGroup(groupKey) {
            this.permissionGroupOpen[groupKey] = !this.permissionGroupOpen[groupKey];
        },
        groupSelectedCount(groupKey) {
            const ids = this.permissionGroupMap[groupKey] || [];
            return ids.filter((id) => this.selectedPermissionIds.includes(String(id))).length;
        },
    }'
>
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @endif

    {{-- Avatar uploader is edit-only because create starts from default avatar. --}}
    @if ($isEdit)
        <x-services.users.form.avatar-editor :user="$user" />
    @endif

    {{-- Core identity and credential fields shared between create and edit pages. --}}
    <x-services.users.form.basic-fields :is-edit="$isEdit" :user="$user" />

    {{-- Authorization-gated ACL section (roles and optional direct permissions). --}}
    @if ($canAssignRoles)
        <div class="space-y-6 rounded-xl border border-gray-200 bg-gray-50/60 p-4 sm:p-5">
            <x-services.users.form.role-picker
                :roles-for-picker="$rolesForPicker"
                :selected-role-ids="$selectedRoleIds"
            />

            @if ($canAssignDirectPermissions)
                <x-services.users.form.direct-permissions
                    :permission-groups-prepared="$permissionGroupsPrepared"
                    :total-permissions-count="$totalPermissionsCount"
                />
            @else
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    {{ __('Direct permissions can only be assigned by users with Manage ACL permission.') }}
                </div>
            @endif
        </div>
    @endif

    <div class="flex items-center gap-4 border-t border-gray-200 pt-6">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <x-secondary-link href="{{ route('users.index') }}">
            {{ __('Cancel') }}
        </x-secondary-link>
    </div>
</form>
