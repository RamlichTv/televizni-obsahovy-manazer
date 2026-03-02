<x-app-layout title="{{ __('Users') }}">
    <x-slot name="usersNavTitle">
        {{ __('Users') }}
    </x-slot>

    <x-slot name="header">
        <x-services.page-header
            title="{{ __('User Services') }}"
            description="{{ __('Manage platform users, their roles, and assigned permissions.') }}"
        />
    </x-slot>

    <section class="py-8 px-4">
        @php
            // Normalize selected role filters to stable string IDs so the picker can rehydrate reliably.
            $selectedRoleIds = collect((array) ($filters['role_ids'] ?? []))
                ->map(fn ($id) => (string) $id)
                ->filter()
                ->unique()
                ->values();

            // Keep picker payload minimal and serializable for the shared filter panel component.
            $rolesForPicker = $roles->map(fn ($role) => [
                'id' => (string) $role->id,
                'name' => (string) $role->name,
            ])->values();
        @endphp

        <div class="max-w-7xl mx-auto space-y-4">
            
            {{-- Flash and validation feedback for destructive operations (delete/sync failures). --}}
            @if (session('success'))
                <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->has('user'))
                <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first('user') }}
                </div>
            @endif

            {{-- Shared search + multi-select filter toolbar reused across service listings. --}}
            <x-filter-panel
                :search-value="$filters['search'] ?? ''"
                :search-placeholder="__('Name, username or email...')"
                :search-suggestions="$searchSuggestions ?? []"
                :pickers="[
                    [
                        'label' => __('Roles'),
                        'input_name' => 'role_ids[]',
                        'items' => $rolesForPicker,
                        'selected' => $selectedRoleIds,
                        'placeholder' => __('Role'),
                        'no_selection_text' => __('All roles'),
                        'no_results_text' => __('No roles available to add.'),
                        'add_aria_label' => __('Add role filter'),
                        'remove_aria_label' => __('Remove role filter'),
                    ],
                ]"
                :reset-url="route('users.index')"
            />

            @can('manage_users')
                <x-services.create-action
                    href="{{ route('users.create') }}"
                    label="{{ __('Create User') }}"
                    class="w-full justify-center"
                />
            @endcan

            {{-- Main listing table extracted to component to keep this page focused on composition. --}}
            <x-services.users.table :users="$users" />

            <div>
                {{ $users->links() }}
            </div>
        </div>
    </section>
</x-app-layout>
