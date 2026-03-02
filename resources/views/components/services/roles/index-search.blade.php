@props([
    'filters' => [],
    'searchSuggestions' => [],
])

<form method="GET" class="rounded-lg bg-white p-4 shadow">
    <div class="flex flex-col gap-3 md:flex-row md:items-end">
        <x-search-bar
            class="flex-1"
            :value="$filters['search'] ?? ''"
            :label="__('Search')"
            :placeholder="__('Role name...')"
            :suggestions="$searchSuggestions ?? []"
        />
    </div>
</form>
