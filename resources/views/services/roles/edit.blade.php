<x-app-layout title="{{ __('Edit Role') }}">

    {{-- Roles Sub-Navigation Label --}}
    <x-slot name="rolesNavTitle">
        {{ __('Edit') }}
    </x-slot>

    {{-- Page Header --}}
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between w-full gap-3">

            {{-- Back Link + Title --}}
            <div class="flex items-center gap-4">
                <x-secondary-link href="{{ route('roles.index') }}" class="text-gray-500 hover:text-gray-700 transition">
                    <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180"/>
                    {{ __('Back to Roles') }}
                </x-secondary-link>

                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full border border-black/10" style="background-color: {{ $role->color }}"></span>
                    <h1 class="text-2xl font-bold text-gray-800">{{ __('Edit Role') }}</h1>
                </div>
            </div>

            <span class="text-sm text-gray-600">{{ $role->name }}</span>
        </div>
    </x-slot>

    {{-- Main Content --}}
    <section class="py-8 px-4">
        <div class="max-w-5xl mx-auto">
            
            {{-- Role Form Card --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @include('services.roles.partials.form', [
                    'action' => route('role.update', ['role' => $role]),
                    'submitLabel' => __('Save Changes'),
                    'role' => $role,
                    'permissions' => $permissions,
                    'permissionGroups' => $permissionGroups,
                    'canManageHiddenRoles' => $canManageHiddenRoles,
                ])
            </div>
        </div>
    </section>
</x-app-layout>
