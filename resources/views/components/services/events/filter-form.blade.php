@props([
    'types' => collect(),
    'severityTypes' => collect(),
    'users' => collect(),
    'filters' => [],
    'searchSuggestions' => [],
])

{{--
    Filter panel for the events index.
    Builds normalized picker/search state from backend filters and keeps query params GET-friendly.
--}}

@php
    // Normalize ids to strings so selected-state checks are consistent in the picker.
    $selectedTypeIds = collect((array) data_get($filters, 'type_ids', []))
        ->map(fn ($id) => (string) $id)
        ->filter()
        ->unique()
        ->values();

    $selectedSeverityIds = collect((array) data_get($filters, 'severity_ids', []))
        ->map(fn ($id) => (string) $id)
        ->filter()
        ->unique()
        ->values();

    $selectedUserIds = collect((array) data_get($filters, 'user_ids', []))
        ->map(fn ($id) => (string) $id)
        ->filter()
        ->unique()
        ->values();

    $searchValue = trim((string) data_get($filters, 'search', ''));
    // Prepare a clean, deduplicated suggestion list for Alpine autocomplete.
    $normalizedSuggestions = collect($searchSuggestions)
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->unique()
        ->values();

    // Multi-select picker consumes items in a simple {id, name} shape.
    $typeOptionsForPicker = collect($types)->map(fn ($type) => [
        'id' => (string) $type->id,
        'name' => (string) $type->name,
    ])->values();

    $severityOptionsForPicker = collect($severityTypes)->map(fn ($severityType) => [
        'id' => (string) $severityType->id,
        'name' => ucfirst((string) $severityType->name),
    ])->values();

    $userOptionsForPicker = collect($users)->map(fn ($user) => [
        'id' => (string) $user->id,
        'name' => (string) ($user->name ?: $user->username),
    ])->values();

    // Keep per-instance ids unique in case multiple filter forms exist on one page.
    $searchInputId = 'events-search-' . uniqid();
@endphp

<form
    method="GET"
    class="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
    x-data="{
        search: @js($searchValue),
        suggestions: @js($normalizedSuggestions->all()),
        searchOpen: false,
        get filteredSuggestions() {
            // Keep list short to stay readable and fast while typing.
            const term = this.search.trim().toLowerCase();
            if (!term) {
                return this.suggestions.slice(0, 8);
            }

            return this.suggestions
                .filter((item) => item.toLowerCase().includes(term))
                .slice(0, 8);
        },
        pickSuggestion(value) {
            // Write selected suggestion into the bound search input and close dropdown.
            this.search = value;
            this.searchOpen = false;
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },
        closeSuggestions() {
            this.searchOpen = false;
        }
    }"
>
    <div class="xl:col-span-2">
        <x-input-label for="{{ $searchInputId }}" :value="__('Search message')" class="text-sm" />

        <div class="relative mt-1" @click.outside="closeSuggestions()">
            <x-text-input
                id="{{ $searchInputId }}"
                x-ref="searchInput"
                name="search"
                x-model="search"
                type="text"
                class="block w-full text-sm"
                :value="$searchValue"
                :placeholder="__('Find text in event message')"
                autocomplete="off"
                @focus="searchOpen = true"
                @input="searchOpen = true"
            />

            <div
                x-show="searchOpen && filteredSuggestions.length > 0"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.99]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.99]"
                class="absolute z-40 mt-2 w-full rounded-xl border border-[#5EB4EE]/50 bg-white p-3 shadow-xl"
            >
                <div class="max-h-56 space-y-1 overflow-y-auto">
                    <template x-for="item in filteredSuggestions" :key="`events-search-${item}`">
                        <button
                            type="button"
                            class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-gray-800 transition-all duration-200 hover:bg-[#5EB4EE]/15"
                            @click="pickSuggestion(item)"
                        >
                            <span class="truncate" x-text="item"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div>
        <x-multi-select-picker
            :label="__('Severity')"
            input-name="severity_ids[]"
            :items="$severityOptionsForPicker"
            :selected="$selectedSeverityIds"
            :placeholder="__('Severity')"
            :no-results-text="__('No severities available to add.')"
            :add-aria-label="__('Add severity filter')"
            :remove-aria-label="__('Remove severity filter')"
            compact
        />
    </div>

    <div>
        <x-multi-select-picker
            :label="__('Event type')"
            input-name="type_ids[]"
            :items="$typeOptionsForPicker"
            :selected="$selectedTypeIds"
            :placeholder="__('Event type')"
            :no-results-text="__('No event types available to add.')"
            :add-aria-label="__('Add event type filter')"
            :remove-aria-label="__('Remove event type filter')"
            compact
        />
    </div>

    <div>
        <x-multi-select-picker
            :label="__('Actor')"
            input-name="user_ids[]"
            :items="$userOptionsForPicker"
            :selected="$selectedUserIds"
            :placeholder="__('Actor')"
            :no-results-text="__('No actors available to add.')"
            :add-aria-label="__('Add actor filter')"
            :remove-aria-label="__('Remove actor filter')"
            compact
        />
    </div>

    <div class="md:col-span-2 xl:col-span-5 flex items-center gap-3">
        <x-primary-button type="submit" class="text-sm">
            {{ __('Apply filters') }}
        </x-primary-button>
        <x-secondary-link href="{{ route('events.index') }}" class="text-sm">
            {{ __('Reset') }}
        </x-secondary-link>
    </div>
</form>
