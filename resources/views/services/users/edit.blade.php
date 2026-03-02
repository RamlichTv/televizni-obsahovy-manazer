<x-app-layout title="{{ __('Edit User') }}">
    <x-slot name="usersNavTitle">
        {{ __('Edit') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between w-full gap-3">
            <div class="flex items-center gap-4">
                <x-secondary-link href="{{ route('users.show', ['user' => $user]) }}" class="text-gray-500 hover:text-gray-700 transition">
                    <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180"/>
                    {{ __('Back to Detail') }}
                </x-secondary-link>
                <h1 class="text-2xl font-bold text-gray-800">{{ __('Edit User') }}</h1>
            </div>
            <span class="text-sm text-gray-600">{{ $user->username }}</span>
        </div>
    </x-slot>

    <section class="py-8 px-4">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @include('services.users.partials.form', [
                    'action' => route('users.update', ['user' => $user]),
                    'submitLabel' => __('Save Changes'),
                    'user' => $user,
                    'roles' => $roles,
                    'permissionGroups' => $permissionGroups,
                    'canAssignRoles' => $canAssignRoles,
                    'canAssignDirectPermissions' => $canAssignDirectPermissions,
                ])
            </div>
        </div>
    </section>
</x-app-layout>
