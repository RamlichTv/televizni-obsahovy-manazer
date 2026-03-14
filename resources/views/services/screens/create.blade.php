<x-app-layout title="{{ __('Create Screen') }}">
    <x-slot name="screensNavTitle">
        {{ __('Create') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ __('Create Screen') }}</h1>
                <p class="text-sm text-gray-500">{{ __('Register a TV endpoint and assign its playback profile.') }}</p>
            </div>

            <x-secondary-link href="{{ route('screens.index') }}">
                <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                {{ __('Back to Screens') }}
            </x-secondary-link>
        </div>
    </x-slot>

    <section class="py-8 px-4">
        <div class="mx-auto max-w-7xl">
            {{-- Reuse the shared screen form partial for create flow. --}}
            @include('services.screens.partials.form', [
                'action' => route('screen.store'),
                'submitLabel' => __('Create Screen'),
            ])
        </div>
    </section>
</x-app-layout>
