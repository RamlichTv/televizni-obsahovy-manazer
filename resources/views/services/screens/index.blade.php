{{--
    Screens service index page.
    It now uses the shared light-mode modal shell for location management while keeping the existing card and filter layout intact.
--}}

<x-app-layout title="{{ __('Screens') }}">
    <x-slot name="screensNavTitle">
        {{ __('Screens') }}
    </x-slot>

    <x-slot name="header">
        @php
            /**
             * Reopen the location manager after CRUD redirects or validation failures.
             */
            $locationManagerShouldOpen = (bool) session('location_manager_open', false) || $errors->any();
        @endphp

        <div
            class="w-full"
            x-data="{ locationModalOpen: @js($locationManagerShouldOpen) }"
            @keydown.escape.window="locationModalOpen = false"
        >
            <div class="flex items-start justify-between gap-4">
                <x-services.page-header
                    title="{{ __('Screen Services') }}"
                    description="{{ __('Create screens and manage TV endpoints.') }}"
                >
                </x-services.page-header>

                <x-secondary-button type="button" @click="locationModalOpen = true">
                    <x-icon name="cog" class="size-6 mr-2" />
                    {{ __('Manage Locations') }}
                </x-secondary-button>
            </div>

            {{-- Shared manager modal for buildings, floors, and rooms. --}}
            <div x-show="locationModalOpen" x-cloak class="fixed inset-0 z-50">
                <div
                    x-show="locationModalOpen"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-900/35 backdrop-blur-[1px]"
                    @click="locationModalOpen = false"
                ></div>

                <div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-6">
                    <div class="flex min-h-full items-center justify-center">
                        <div
                            x-show="locationModalOpen"
                            x-transition:enter="transform transition ease-out duration-300"
                            x-transition:enter-start="translate-y-4 opacity-0 sm:scale-95"
                            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                            x-transition:leave="transform transition ease-in duration-200"
                            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                            x-transition:leave-end="translate-y-4 opacity-0 sm:scale-95"
                            class="flex w-full max-w-7xl max-h-[min(86vh,900px)] min-h-[30rem] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                        >
                            <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Location Manager') }}</h3>
                                        <p class="mt-1 text-sm text-gray-500">{{ __('Manage buildings, floors, and rooms inside the same shared modal system used by the rest of the application.') }}</p>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                                        @click="locationModalOpen = false"
                                    >
                                        <span class="sr-only">{{ __('Close') }}</span>
                                        <x-icon name="x" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="min-h-0 flex-1 overflow-auto px-5 py-5 sm:px-6">
                                @include('services.screens.partials.location-manager')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <section class="flex flex-col grow p-4 max-w-screen w-full mx-auto">
        @php
            $selectedBuildingIds = collect((array) ($filters['building_ids'] ?? []))
                ->map(fn ($id) => (string) $id)
                ->filter()
                ->unique()
                ->values();
            $selectedFloorIds = collect((array) ($filters['floor_ids'] ?? []))
                ->map(fn ($id) => (string) $id)
                ->filter()
                ->unique()
                ->values();
            $selectedRoomIds = collect((array) ($filters['room_ids'] ?? []))
                ->map(fn ($id) => (string) $id)
                ->filter()
                ->unique()
                ->values();
            $buildingsForPicker = $buildings->map(fn ($building) => [
                'id' => (string) $building->id,
                'name' => $building->name,
            ])->values();
            $floorsForPicker = $floors->map(fn ($floor) => [
                'id' => (string) $floor->id,
                'name' => $floor->breadcrumbLabel(' > '),
            ])->values();
            $roomsForPicker = $rooms->map(fn ($room) => [
                'id' => (string) $room->id,
                'name' => $room->breadcrumbLabel(' > '),
            ])->values();
        @endphp

        {{-- Contextual screen validation remains inline because it is specific to the filter/content area below. --}}
        @if ($errors->has('screen'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first('screen') }}
            </div>
        @endif

        {{-- Reuse the shared filter panel pattern used by content/modules pages. --}}
        <x-filter-panel
            :search-value="$filters['search'] ?? ''"
            :search-placeholder="__('Screen name, hardware ID, profile, room...')"
            :search-suggestions="$searchSuggestions ?? []"
            :pickers="[
                [
                    'label' => __('Building'),
                    'input_name' => 'building_ids[]',
                    'items' => $buildingsForPicker,
                    'selected' => $selectedBuildingIds,
                    'placeholder' => __('Building'),
                    'no_selection_text' => __('All buildings'),
                    'no_results_text' => __('No buildings available to add.'),
                    'add_aria_label' => __('Add building filter'),
                    'remove_aria_label' => __('Remove building filter'),
                ],
                [
                    'label' => __('Floor'),
                    'input_name' => 'floor_ids[]',
                    'items' => $floorsForPicker,
                    'selected' => $selectedFloorIds,
                    'placeholder' => __('Floor'),
                    'no_selection_text' => __('All floors'),
                    'no_results_text' => __('No floors available to add.'),
                    'add_aria_label' => __('Add floor filter'),
                    'remove_aria_label' => __('Remove floor filter'),
                ],
                [
                    'label' => __('Room'),
                    'input_name' => 'room_ids[]',
                    'items' => $roomsForPicker,
                    'selected' => $selectedRoomIds,
                    'placeholder' => __('Room'),
                    'no_selection_text' => __('All rooms'),
                    'no_results_text' => __('No rooms available to add.'),
                    'add_aria_label' => __('Add room filter'),
                    'remove_aria_label' => __('Remove room filter'),
                ],
            ]"
            :reset-url="route('screens.index')"
        />

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
