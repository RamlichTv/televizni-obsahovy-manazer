{{--
    Overview:
    This view composes the services > screens > index interface, arranging reusable components and route-driven actions so the page stays readable while presentation concerns remain separated from backend logic.
--}}

<x-app-layout title="{{ __('Screens') }}">
    <x-slot name="screensNavTitle">
        {{ __('Screens') }}
    </x-slot>

    <x-slot name="header">
        {{-- Keep modal open after successful CRUD by reading a flashed session flag. --}}
        <div x-data="{ locationModalOpen: @js((bool) session('location_manager_open', false) || $errors->any()) }" class="w-full">
            <div class="flex items-start justify-between gap-4">
                <x-services.page-header
                    title="{{ __('Screen Services') }}"
                    description="{{ __('Create screens and manage TV endpoints.') }}"
    
                >
                </x-services.page-header>

                <x-secondary-button @click="locationModalOpen = true">
                    <x-icon name="cog" class="size-6 mr-2" />
                    {{ __('Manage Locations') }}
                </x-secondary-button>
            </div>

            {{-- Shared manager modal for buildings/floors/rooms. --}}
            <div
                x-show="locationModalOpen"
                style="display: none;"
                class="fixed inset-0 z-50 overflow-y-auto"
                role="dialog"
                aria-modal="true">
                <div
                    x-show="locationModalOpen"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    @click="locationModalOpen = false"
                />

                <div class="relative z-10 flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        x-show="locationModalOpen"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-7xl">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100">
                            <div class="sm:flex sm:items-start justify-between">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900">
                                    {{ __('Location Manager') }}
                                </h3>
                                <button @click="locationModalOpen = false" class="text-gray-400 hover:text-gray-500">
                                    <span class="sr-only">{{ __('Close') }}</span>
                                    <x-icon name="x" class="w-6 h-6" />
                                </button>
                            </div>
                        </div>

                        <div class="px-4 py-4 sm:p-6">
                            @include('services.screens.partials.location-manager')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <section class="flex flex-col grow p-4 max-w-screen w-full mx-auto">
        {{-- Shared flash area for all screen/location operations. --}}
        @if (session('success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('screen'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first('screen') }}
            </div>
        @endif

        {{-- Lightweight search for screen cards. --}}
        <form method="GET" class="mb-4 rounded-lg bg-white p-4 shadow">
            <x-search-bar
                class="flex-1"
                :value="$filters['search'] ?? ''"
                :label="__('Search')"
                :placeholder="__('Screen name, hardware ID, profile, room...')"
                :suggestions="$searchSuggestions ?? []"
            />
        </form>

        {{-- Card grid: create card + existing screen cards. --}}
        <div class="flex-1 grid grid-cols-2 lg:grid-cols-5 grid-rows-5 lg:grid-rows-3 gap-4 auto-rows-fr">
            <x-service-card
                title="{{ __('Create Screen') }}"
                description="{{ __('Register a new TV or signage device.') }}"
                icon="plus-circle"
                link="{{ route('screen.create') }}"
            />

            @forelse ($screens as $screen)
                <x-services.screens.screen-card :screen="$screen" />
            @empty
                <div class="col-span-2 lg:col-span-4 rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                    {{ __('No TVs registered yet.') }}
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
