@props([
    'label' => __('Items'),
    'description' => __('Grouped items with collapsible sections.'),
    'inputName' => 'items[]',
    'selected' => [],
    'groups' => [],
    'emptyText' => __('No items available.'),
])

@php
    // Normalize selected ids to strings so Alpine checkbox binding stays consistent.
    $selectedIds = collect($selected)
        ->map(fn ($id) => (string) $id)
        ->values();

    // Normalize external group payload into a strict shape used by this component.
    $preparedGroups = collect($groups)
        ->map(function ($group, $index) {
            $items = collect(data_get($group, 'items', []))
                ->map(fn ($item) => [
                    'id' => (string) data_get($item, 'id'),
                    'label' => (string) data_get($item, 'label'),
                ])
                ->filter(fn ($item) => $item['id'] !== '' && $item['label'] !== '')
                ->values();

            $groupName = (string) data_get($group, 'name', __('Group'));
            $groupKey = (string) data_get($group, 'key', 'group_' . $index);

            return [
                'key' => $groupKey,
                'name' => $groupName,
                'items' => $items,
            ];
        })
        ->filter(fn ($group) => $group['items']->isNotEmpty())
        ->values();

    // Track collapsible state and id maps per group for counters/highlights.
    $groupOpen = $preparedGroups
        ->pluck('key')
        ->mapWithKeys(fn ($key) => [$key => false]);

    $groupMap = $preparedGroups->mapWithKeys(function ($group) {
        return [
            $group['key'] => $group['items']
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all(),
        ];
    });

    $totalCount = $preparedGroups->sum(fn ($group) => $group['items']->count());
@endphp

{{-- Universal component: grouped checkbox selector with nested collapsible sections. --}}
<div
    x-data='{
        open: false,
        selectedIds: @json($selectedIds->values()),
        groupOpen: @json($groupOpen),
        groupMap: @json($groupMap),
        toggleGroup(groupKey) {
            this.groupOpen[groupKey] = !this.groupOpen[groupKey];
        },
        selectedCount(groupKey) {
            const ids = this.groupMap[groupKey] || [];
            return ids.filter((id) => this.selectedIds.includes(String(id))).length;
        },
    }'
    class="rounded-xl border border-gray-200 bg-white p-4 transition-all duration-300 hover:border-[#5EB4EE]/50"
>
    {{-- Root section header with overall selected/total counter. --}}
    <button
        type="button"
        class="flex w-full items-center justify-between gap-3 text-left transition-colors duration-200 hover:text-[#077CB6]"
        @click="open = !open"
    >
        <span>
            <span class="block text-base font-semibold text-gray-900">{{ $label }}</span>
            <span class="block text-xs text-gray-500">{{ $description }}</span>
        </span>
        <span class="inline-flex items-center gap-3">
            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600">
                <span x-text="selectedIds.length"></span> / {{ $totalCount }}
            </span>
            <x-icon name="arr" class="h-4 w-4 text-gray-500 transition-transform" x-bind:class="open ? 'rotate-180' : ''"/>
        </span>
    </button>

    {{-- Collapsible content with one card per group. --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-250"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-180"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="mt-4 space-y-3"
    >
        @forelse ($preparedGroups as $group)
            {{-- Group card with per-group selected/total counter. --}}
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 transition-all duration-200 hover:border-[#5EB4EE]/50"
                x-bind:class="selectedCount('{{ $group['key'] }}') > 0 ? 'border-[#5EB4EE]/70 bg-[#5EB4EE]/5' : ''"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition-colors duration-200 hover:text-[#077CB6]"
                    @click="toggleGroup('{{ $group['key'] }}')"
                >
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">{{ __($group['name']) }}</span>
                        <span class="block text-xs text-gray-500">{{ __('Permission Group') }}</span>
                    </span>
                    <span class="inline-flex items-center gap-3">
                        <span class="rounded-full bg-white px-2 py-1 text-xs text-gray-600 border border-gray-200">
                            <span x-text="selectedCount('{{ $group['key'] }}')"></span> / {{ $group['items']->count() }}
                        </span>
                        <x-icon name="arr" class="h-4 w-4 text-gray-500 transition-transform" x-bind:class="groupOpen['{{ $group['key'] }}'] ? 'rotate-180' : ''"/>
                    </span>
                </button>

                {{-- Group item list; each row is a checkbox option. --}}
                <div
                    x-show="groupOpen['{{ $group['key'] }}']"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="px-3 pb-3 space-y-2"
                >
                    @foreach ($group['items'] as $item)
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-white px-3 py-2 cursor-pointer transition-all duration-200 hover:border-[#5EB4EE]/60 hover:bg-[#5EB4EE]/5">
                            <span class="text-sm text-gray-800">{{ $item['label'] }}</span>
                            <input
                                type="checkbox"
                                name="{{ $inputName }}"
                                value="{{ $item['id'] }}"
                                class="rounded border-gray-300 text-[#077CB6] shadow-sm focus:ring-[#5EB4EE]"
                                x-model="selectedIds"
                            >
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            {{-- Empty state when no valid groups/items were provided. --}}
            <p class="text-sm text-gray-500">{{ $emptyText }}</p>
        @endforelse
    </div>
</div>
