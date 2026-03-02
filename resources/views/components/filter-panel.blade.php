@props([
    'searchName' => 'search',
    'searchValue' => '',
    'searchLabel' => __('Search'),
    'searchPlaceholder' => __('Search...'),
    'searchSuggestions' => [],
    'searchInputId' => null,
    'pickers' => [],
    'resetUrl' => null,
    'applyLabel' => __('Apply Filters'),
    'resetLabel' => __('Reset'),
])

@php
    // Normalize incoming filter values so the UI receives stable data types.
    $normalizedSearchValue = trim((string) $searchValue);
    $resolvedSearchInputId = $searchInputId ?: ('service-filter-search-' . uniqid());
    $resolvedResetUrl = $resetUrl ?: url()->current();

    $normalizedPickers = collect($pickers)
        ->map(function ($picker) {
            $selectedValues = collect(data_get($picker, 'selected', []))
                ->map(fn ($id) => (string) $id)
                ->filter(fn ($id) => $id !== '')
                ->unique()
                ->values()
                ->all();

            return [
                'label' => (string) data_get($picker, 'label', ''),
                'input_name' => (string) data_get($picker, 'input_name', ''),
                'items' => data_get($picker, 'items', []),
                'selected' => $selectedValues,
                'placeholder' => (string) data_get($picker, 'placeholder', __('Item')),
                'no_selection_text' => (string) data_get($picker, 'no_selection_text', __('No items selected yet.')),
                'no_results_text' => (string) data_get($picker, 'no_results_text', __('No matching items found.')),
                'add_aria_label' => (string) data_get($picker, 'add_aria_label', __('Add item filter')),
                'remove_aria_label' => (string) data_get($picker, 'remove_aria_label', __('Remove item filter')),
                'chip_prefix' => data_get($picker, 'chip_prefix'),
                'option_prefix' => data_get($picker, 'option_prefix'),
                'panel_position' => (string) data_get($picker, 'panel_position', 'down'),
            ];
        })
        ->filter(fn ($picker) => $picker['input_name'] !== '')
        ->values();

    $activePickerCount = $normalizedPickers->sum(fn ($picker) => count($picker['selected']));
    $activeFiltersCount = $activePickerCount + ($normalizedSearchValue !== '' ? 1 : 0);
    $hasPickerFilters = $normalizedPickers->isNotEmpty();
    $hasAnyFilters = $activeFiltersCount > 0;
@endphp

{{-- Universal component: collapsible filter panel with picker and search slots. --}}
<div
    class="rounded-lg bg-white shadow hover:bg-gray-50 mb-5"
    x-data="{ showFilters: @js($hasAnyFilters) }"
    >
    {{-- Panel header with active-filter count and expand/collapse control. --}}
    <div @click="showFilters = !showFilters" class="p-4">
        <div class="flex items-center justify-between gap-3">
            <button
                type="button"
                class="inline-flex items-center gap-2 text-left font-semibold text-gray-700"
            >
                <x-icon name="filter" class="h-4 w-4" />
                <span>{{ __('Filters') }}</span>
    
                @if ($activeFiltersCount > 0)
                    <span class="inline-flex rounded-full bg-[#077CB6]/10 px-2 py-0.5 text-xs font-semibold text-[#077CB6]">
                        {{ $activeFiltersCount }}
                    </span>
                @endif
            </button>
            <x-icon name="arr" class="size-5 text-gray-500 transition-transform" x-bind:class="{ 'rotate-180': showFilters }"/>
        </div>
    </div>

    {{-- Filter form body: multi-select pickers + search + submit/reset actions. --}}
    <form method="GET" x-show="showFilters" x-transition class="mx-4 mb-4 flex flex-col">
        {{-- Optional domain pickers configured by parent screen. --}}
        <div class="flex flex-1 my-2 gap-2">
            @foreach ($normalizedPickers as $picker)
                <x-multi-select-picker
                    :label="$picker['label']"
                    :input-name="$picker['input_name']"
                    :items="$picker['items']"
                    :selected="$picker['selected']"
                    :placeholder="$picker['placeholder']"
                    :no-results-text="$picker['no_results_text']"
                    :add-aria-label="$picker['add_aria_label']"
                    :remove-aria-label="$picker['remove_aria_label']"
                    :chip-prefix="$picker['chip_prefix']"
                    :option-prefix="$picker['option_prefix']"
                    :panel-position="$picker['panel_position']"
                    class="flex-1"
                />
            @endforeach
        </div>

        {{-- Shared search input with optional suggestions. --}}
        <x-search-bar
            class="my-4"
            :name="$searchName"
            :value="$normalizedSearchValue"
            :label="$searchLabel"
            :placeholder="$searchPlaceholder"
            :suggestions="$searchSuggestions"
            :input-id="$resolvedSearchInputId"
        />
        
        {{-- Action row is shown only when at least one picker filter exists. --}}
        @if ($hasPickerFilters)
            <div class="flex items-end justify-start gap-2 mb-4">
                <x-primary-button class="justify-center">{{ $applyLabel }}</x-primary-button>
                <x-secondary-link href="{{ $resolvedResetUrl }}">{{ $resetLabel }}</x-secondary-link>
            </div>
        @endif
    </form>
</div>
