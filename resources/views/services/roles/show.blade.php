{{--
    Role detail page.
    It keeps the role metadata layout intact while generic success feedback moves to the shared toast host.
--}}

<x-app-layout title="{{ __('Role Detail') }}">
    {{-- Roles Sub-Navigation Label --}}
    <x-slot name="rolesNavTitle">
        {{ __('Detail') }}
    </x-slot>

    {{-- Page Header --}}
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">

            {{-- Back Link + Title --}}
            <div class="flex items-center gap-4">
                <x-secondary-link href="{{ route('roles.index') }}">
                    <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                    {{ __('Back to Roles') }}
                </x-secondary-link>
                <h1 class="text-2xl font-bold text-gray-800">{{ __('Role Detail') }}</h1>
            </div>

            {{-- Primary Edit Action --}}
            <x-secondary-link href="{{ route('role.edit', ['role' => $role]) }}">
                <x-icon name="pen" class="w-4 h-4 mr-2" />
                {{ __('Edit') }}
            </x-secondary-link>
        </div>
    </x-slot>

    {{-- Main Content --}}
    <section class="py-8 px-4">
        <div class="max-w-5xl mx-auto space-y-6">

            {{-- Role Metadata Card --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Name') }}</p>
                        <p class="mt-1 flex items-center gap-2 text-sm text-gray-900">
                            <span class="h-3 w-3 rounded-full border border-black/10" style="background-color: {{ $role->color }}"></span>
                            {{ $role->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Created By') }}</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $role->createdBy?->name ?? $role->createdBy?->username ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Visibility') }}</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $role->is_hidden ? __('Hidden') : __('Visible') }}</p>
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Created') }}</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $role->created_at?->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- Assigned Permissions + Users --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- Permissions Group --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-base font-semibold text-gray-900">{{ __('Permissions') }}</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($role->permissions as $permission)
                            @php
                                $permissionLabel = __($permission->name);
                                if ($permissionLabel === $permission->name) {
                                    $permissionLabel = (string) \Illuminate\Support\Str::of($permission->name)->replace('_', ' ')->title();
                                }
                            @endphp
                            <span class="inline-flex items-center rounded-full bg-[#077CB6]/10 px-3 py-1 text-xs font-medium text-[#077CB6]">
                                {{ $permissionLabel }}
                            </span>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('No permissions assigned.') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- Users Group --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-base font-semibold text-gray-900">{{ __('Users') }}</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($role->users as $assignedUser)
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                {{ $assignedUser->name ?: $assignedUser->username }}
                            </span>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('No users assigned.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
