{{--
    Overview:
    This view composes the components > services > users > access-panels interface, summarizing visible roles and direct permissions on the user detail page.
--}}

@props([
    'user',
])

@php
    // Non-admin viewers must not see hidden/administrator roles in the detail summary.
    $visibleRoles = Auth::user()->isAdmin()
        ? $user->roles
        : $user->roles
            ->where('is_hidden', false)
            ->where('name', '!=', 'Administrator');
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Effective role assignments available to the current viewer. --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <h2 class="text-base font-semibold text-gray-900">{{ __('Roles') }}</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse ($visibleRoles as $role)
                <x-services.roles.role :role="$role" />
            @empty
                <p class="text-sm text-gray-500">{{ __('No assigned roles.') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Direct permissions are shown separately from role-derived permissions. --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <h2 class="text-base font-semibold text-gray-900">{{ __('Direct Permissions') }}</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse ($user->permissions as $permission)
                <x-services.roles.permission :permission="$permission" />
            @empty
                <p class="text-sm text-gray-500">{{ __('No direct permissions.') }}</p>
            @endforelse
        </div>
    </div>
</div>
