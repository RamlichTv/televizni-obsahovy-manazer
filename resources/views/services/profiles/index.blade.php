{{--
    View: Profiles Index
    Goal: Keep shell structure readable while the data-table UI lives in a reusable component.
--}}

<x-app-layout title="{{ __('Profiles') }}">
    <x-slot name="profilesNavTitle">
        {{ __('Profiles') }}
    </x-slot>

    <x-slot name="header">
        <x-services.page-header
            title="{{ __('Profile Services') }}"
            description="{{ __('Reusable page-based layouts for screens.') }}"
        />
    </x-slot>

    {{-- Search/filter + profiles table + row actions. --}}
    <x-services.profiles.index-table
        :profiles="$profiles"
        :filters="$filters"
        :search-suggestions="$searchSuggestions ?? []"
    />
</x-app-layout>
