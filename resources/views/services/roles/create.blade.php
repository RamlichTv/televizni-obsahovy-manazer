<x-app-layout title="{{ __('Create Role') }}">
    {{-- Roles Sub-Navigation Label --}}
    <x-slot name="rolesNavTitle">
        {{ __('Create') }}
    </x-slot>

    {{-- Page Header --}}
    <x-slot name="header">
        <div class="flex items-center gap-4 w-full">
            <x-secondary-link href="{{ route('roles.index') }}">
                <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                {{ __('Back to Roles') }}
            </x-secondary-link>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Create Role') }}</h1>
        </div>
    </x-slot>

    {{-- Main Content --}}
    <section class="py-8 px-4">
        <div class="max-w-5xl mx-auto">
            {{-- Role Form Card --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @include('services.roles.partials.form', [
                    'action' => route('role.store'),
                    'submitLabel' => __('Create Role'),
                    'role' => null,
                    'permissions' => $permissions,
                    'permissionGroups' => $permissionGroups,
                    'canManageHiddenRoles' => $canManageHiddenRoles,
                ])
            </div>
        </div>
    </section>
</x-app-layout>
