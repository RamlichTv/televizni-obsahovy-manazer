<x-app-layout title="{{ __('Roles') }}">
    {{-- Roles Sub-Navigation Label --}}
    <x-slot name="rolesNavTitle">
        {{ __('Roles') }}
    </x-slot>

    {{-- Page Header --}}
    <x-slot name="header">
        <x-services.page-header
            title="{{ __('Role Services') }}"
            description="{{ __('Configure roles, ACL visibility, and permission bundles.') }}"
        />
    </x-slot>

    {{-- Main Content --}}
    <section class="py-8 px-4">
        <div class="max-w-7xl mx-auto space-y-4">
            
            {{-- Feedback Messages --}}
            @if (session('success'))
                <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->has('role'))
                <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first('role') }}
                </div>
            @endif

            {{-- Roles-specific search/filter UI --}}
            <x-services.roles.index-search
                :filters="$filters"
                :search-suggestions="$searchSuggestions ?? []"
            />

            {{-- Primary Create Action --}}
            <x-services.create-action
                href="{{ route('role.create') }}"
                label="{{ __('Create Role') }}"
                class="w-full justify-center"
            />

            {{-- Roles-specific table UI --}}
            <x-services.roles.index-table :roles="$roles" />

            {{-- Pagination --}}
            <div>
                {{ $roles->links() }}
            </div>
        </div>
    </section>
</x-app-layout>
