@props([
    'name' => 'search',
    'value' => '',
    'label' => __('Search'),
    'placeholder' => __('Search...'),
    'suggestions' => [],
    'inputId' => null,
])

@php
    $normalizedValue = trim((string) $value);
    $resolvedInputId = $inputId ?: ('search-input-' . uniqid());
    $normalizedSuggestions = collect($suggestions)
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->unique()
        ->values();
@endphp

<div
    x-data="{
        search: @js($normalizedValue),
        suggestions: @js($normalizedSuggestions->all()),
        searchOpen: false,
        get filteredSuggestions() {
            const term = this.search.trim().toLowerCase();
            if (!term) {
                return this.suggestions.slice(0, 8);
            }

            return this.suggestions
                .filter((item) => item.toLowerCase().includes(term))
                .slice(0, 8);
        },
        pickSuggestion(value) {
            this.search = value;
            this.searchOpen = false;
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },
        closeSuggestions() {
            this.searchOpen = false;
        }
    }"
    {{ $attributes }}
>
    @if ($label !== '')
        <x-input-label :for="$resolvedInputId" :value="$label" />
    @endif

    <div class="relative mt-1" @click.outside="closeSuggestions()">
        <x-text-input
            :id="$resolvedInputId"
            x-ref="searchInput"
            :name="$name"
            x-model="search"
            type="text"
            class="block w-full"
            :value="$normalizedValue"
            :placeholder="$placeholder"
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
                <template x-for="item in filteredSuggestions" :key="`search-suggestion-${item}`">
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
