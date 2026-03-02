<x-app-layout title="{{ __('Create User') }}">
    <x-slot name="usersNavTitle">
        {{ __('Create') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex items-center gap-4 w-full">
            <x-secondary-link href="{{ route('users.index') }}">
                <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                {{ __('Back to Users') }}
            </x-secondary-link>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Create User') }}</h1>
        </div>
    </x-slot>

    <section class="py-8 px-4">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @include('services.users.partials.form', [
                    'action' => route('users.store'),
                    'submitLabel' => __('Create User'),
                    'user' => null,
                    'roles' => $roles,
                    'permissionGroups' => $permissionGroups,
                    'canAssignRoles' => $canAssignRoles,
                    'canAssignDirectPermissions' => $canAssignDirectPermissions,
                ])
            </div>
        </div>
    </section>
</x-app-layout>
