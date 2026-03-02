@props([
    'label' => '',
    'inputName',
    'items' => [],
    'selected' => [],
    'placeholder' => 'Item',
    'noResultsText' => 'No matching items found.',
    'addAriaLabel' => 'Add item',
    'removeAriaLabel' => 'Remove item',
    'chipPrefix' => null,
    'optionPrefix' => null,
    'panelPosition' => 'down',
    'searchInputId' => null,
    'compact' => false,
    'showColorDot' => false,
    'showBadge' => false,
])

@php
    $normalizedItems = collect($items)
        ->map(function ($item) {
            return [
                'id' => (string) data_get($item, 'id'),
                'name' => (string) data_get($item, 'name'),
                'color' => data_get($item, 'color'),
                'badge' => data_get($item, 'badge'),
            ];
        })
        ->filter(fn ($item) => $item['id'] !== '' && $item['name'] !== '')
        ->values();

    $normalizedSelected = collect($selected)
        ->map(fn ($id) => (string) $id)
        ->filter(fn ($id) => $id !== '')
        ->unique()
        ->values();

    $inputId = $searchInputId ?: ('picker-search-' . uniqid());

    $opensUp = $panelPosition === 'up';
    $panelClasses = $opensUp
        ? 'absolute bottom-full z-40 mb-2 w-full rounded-xl border border-[#5EB4EE]/50 bg-white p-3 shadow-xl'
        : 'absolute z-40 mt-2 w-full rounded-xl border border-[#5EB4EE]/50 bg-white p-3 shadow-xl';
    $enterStart = $opensUp ? 'opacity-0 translate-y-2 scale-[0.99]' : 'opacity-0 -translate-y-2 scale-[0.99]';
    $leaveEnd = $opensUp ? 'opacity-0 translate-y-1 scale-[0.99]' : 'opacity-0 -translate-y-1 scale-[0.99]';
    $surfaceClasses = $compact
        ? 'rounded-xl border border-gray-200 bg-white px-3 py-1.5 transition-all duration-300 hover:border-[#5EB4EE]/50 hover:shadow-sm min-h-[42px]'
        : 'rounded-xl border border-gray-200 bg-white p-3 transition-all duration-300 hover:border-[#5EB4EE]/50 hover:shadow-sm';
    $addButtonClasses = $compact
        ? 'inline-flex h-7 w-7 items-center justify-center rounded-full border border-[#077CB6]/30 text-[#077CB6] transition-all duration-200 hover:bg-[#077CB6]/10'
        : 'inline-flex h-7 w-7 items-center justify-center rounded-full border border-[#077CB6]/30 text-[#077CB6] transition-all duration-200 hover:bg-[#077CB6]/10';
    $addIconClasses = $compact ? 'size-7' : 'size-8';
@endphp

<div
    x-data="{
        open: false,
        search: '',
        items: @js($normalizedItems),
        selectedIds: @js($normalizedSelected),

        get selectedItems() {
            return this.items.filter((item) => this.selectedIds.includes(String(item.id)));
        },

        get filteredItems() {
            const term = this.search.trim().toLowerCase();

            return this.items.filter((item) => {
                const id = String(item.id);
                if (this.selectedIds.includes(id)) {
                    return false;
                }

                if (!term) {
                    return true;
                }

                return String(item.name).toLowerCase().includes(term);
            });
        },

        openPicker() {
            this.open = true;
            this.$nextTick(() => this.$refs.search?.focus());
        },

        selectItem(itemId) {
            const id = String(itemId);
            if (this.selectedIds.includes(id)) {
                return;
            }

            this.selectedIds.push(id);
        },

        removeItem(itemId) {
            const id = String(itemId);
            this.selectedIds = this.selectedIds.filter((selectedId) => selectedId !== id);
        },
    }" 
    {{ $attributes }}
>
    @if ($label !== '')
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif

    <div class="mt-1 relative" @click.outside="open = false">
        <div class="{{ $surfaceClasses }}">
            <div class="flex flex-wrap items-center gap-2">
                <template x-for="item in selectedItems" :key="`chip-${item.id}`">
                    <span class="inline-flex items-center gap-2 rounded-full border border-[#077CB6]/25 bg-[#077CB6]/10 px-2 py-1 text-sm text-[#055f8d] transition-colors duration-200">
                        @if ($showColorDot)
                            <span
                                x-show="item.color"
                                class="h-2.5 w-2.5 rounded-full border border-black/10"
                                :style="item.color ? `background-color: ${item.color}` : ''"
                            ></span>
                        @endif

                        @if ($chipPrefix !== null && $chipPrefix !== '')
                            <span class="text-xs font-semibold text-[#077CB6]">{{ $chipPrefix }}</span>
                        @endif

                        <span class="max-w-40 truncate" x-text="item.name"></span>

                        @if ($showBadge)
                            <span
                                x-show="item.badge"
                                class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700"
                                x-text="item.badge"
                            ></span>
                        @endif

                        <button
                            type="button"
                            class="text-[#077CB6] transition-colors duration-200 hover:text-[#E69C24]"
                            @click="removeItem(item.id)"
                            aria-label="{{ $removeAriaLabel }}"
                        >
                            <x-icon name="x" class="size-3" width="2.75"/>
                        </button>
                    </span>
                </template>

                <button
                    type="button"
                    class="{{ $addButtonClasses }}"
                    @click="openPicker()"
                    aria-label="{{ $addAriaLabel }}"
                >
                    <x-icon name="+" class="{{ $addIconClasses }}" width="1.5"/>
                </button>
            </div>
        </div>

        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="{{ $enterStart }}"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="{{ $leaveEnd }}"
            class="{{ $panelClasses }}"
        >
            <input
                id="{{ $inputId }}"
                type="text"
                x-ref="search"
                x-model="search"
                placeholder="{{ $placeholder }}"
                class="w-full rounded-lg border border-[#5EB4EE] bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#077CB6] focus:ring-[#5EB4EE]"
            >

            <div class="mt-2 max-h-56 overflow-y-auto space-y-1">
                <template x-for="item in filteredItems" :key="`option-${item.id}`">
                    <button
                        type="button"
                        class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-gray-800 transition-all duration-200 hover:bg-[#5EB4EE]/15"
                        @click="selectItem(item.id)"
                    >
                        <span class="inline-flex min-w-0 items-center gap-2">
                            @if ($showColorDot)
                                <span
                                    x-show="item.color"
                                    class="h-2.5 w-2.5 rounded-full border border-black/10"
                                    :style="item.color ? `background-color: ${item.color}` : ''"
                                ></span>
                            @endif

                            @if ($optionPrefix !== null && $optionPrefix !== '')
                                <span class="text-xs font-semibold text-[#077CB6]">{{ $optionPrefix }}</span>
                            @endif

                            <span class="truncate" x-text="item.name"></span>

                            @if ($showBadge)
                                <span
                                    x-show="item.badge"
                                    class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700"
                                    x-text="item.badge"
                                ></span>
                            @endif
                        </span>
                    </button>
                </template>

                <p class="px-2 py-2 text-xs text-gray-500" x-show="filteredItems.length === 0">
                    {{ $noResultsText }}
                </p>
            </div>
        </div>

        <template x-for="selectedId in selectedIds" :key="`hidden-${selectedId}`">
            <input type="hidden" name="{{ $inputName }}" :value="selectedId">
        </template>
    </div>
</div>
