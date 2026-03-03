{{--
    View: Profile Preview
    Goal: Keep page-level preview shell short while heavy carousel/grid markup is handled by a component.
--}}

<x-app-layout title="{{ $profile->name }}">
    <x-slot name="profilesNavTitle">
        {{ __('Preview') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between w-full">
            <div class="flex items-center gap-3">
                <x-secondary-link href="{{ route('profiles.index') }}">
                    <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                    {{ __('Back to Profiles') }}
                </x-secondary-link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $profile->name }}</h1>
                    <p class="text-xs text-gray-500">{{ __('Slug') }}: {{ $profile->slug }}</p>
                </div>
            </div>

            @can('update', $profile)
                <x-primary-link href="{{ route('profile.edit', ['profile' => $profile]) }}">
                    {{ __('Edit Profile') }}
                </x-primary-link>
            @endcan
        </div>
    </x-slot>

    {{-- Profile summary + page carousel + fullscreen preview canvas. --}}
    <x-services.profiles.preview-panel :profile="$profile" />
</x-app-layout>
