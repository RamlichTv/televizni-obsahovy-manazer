{{--
    Roles service component:
    Adapts role permission groups to the universal grouped checkbox selector component.
--}}

@props([
    'permissionGroups' => collect(),
    'permissions' => [],
    'selectedPermissionIds' => collect(),
])

@php
    use Illuminate\Support\Str;

    $groups = collect($permissionGroups);

    // Fallback grouping when the parent does not provide pre-grouped data.
    if ($groups->isEmpty()) {
        $groups = collect($permissions)->groupBy(function ($permission) {
            $name = (string) $permission->name;

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

    // Normalize group structure for the universal checkbox-selector contract.
    $normalizedGroups = $groups
        ->map(function ($groupPermissions, $groupName) {
            return [
                'key' => Str::slug((string) $groupName, '_'),
                'name' => (string) $groupName,
                'items' => collect($groupPermissions)->map(function ($permission) {
                    $permissionLabel = __((string) $permission->name);
                    if ($permissionLabel === (string) $permission->name) {
                        $permissionLabel = (string) Str::of($permission->name)->replace('_', ' ')->title();
                    }

                    return [
                        'id' => (string) $permission->id,
                        'label' => $permissionLabel,
                    ];
                })->values()->all(),
            ];
        })
        ->values()
        ->all();
@endphp

{{-- Reused universal selector component; this file only adapts roles domain data. --}}
<x-grouped-checkbox-selector
    :label="__('Permissions')"
    :description="__('Grouped by domain, each group can be collapsed.')"
    input-name="permissions[]"
    :selected="$selectedPermissionIds"
    :groups="$normalizedGroups"
    :empty-text="__('No permissions available.')"
/>

{{-- Validation messages for permission array payload --}}
<x-input-error class="mt-2" :messages="$errors->get('permissions')" />
<x-input-error class="mt-2" :messages="$errors->get('permissions.*')" />
