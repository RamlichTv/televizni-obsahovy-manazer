{{--
    View: Profile Create
    Goal: Keep page composition short by delegating full form rendering to the shared profiles form partial.
--}}

<x-app-layout title="{{ __('Create Profile') }}">
    <x-slot name="profilesNavTitle">
        {{ __('Create') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ __('Create Profile') }}</h1>
                <p class="text-sm text-gray-500">{{ __('A profile starts with one page and can grow into a carousel layout.') }}</p>
            </div>

            <x-secondary-link href="{{ route('profiles.index') }}">
                <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                {{ __('Back to Profiles') }}
            </x-secondary-link>
        </div>
    </x-slot>

    <section class="py-8 px-4">
        <div class="max-w-4xl mx-auto">
            
            {{-- Shared profiles create form, aligned with the project's partial pattern. --}}
            @include('services.profiles.partials.form', [
                'action' => route('profile.store'),
                'submitLabel' => __('Create Profile'),
                'cancelUrl' => route('profiles.index'),
            ])
        </div>
    </section>
</x-app-layout>
