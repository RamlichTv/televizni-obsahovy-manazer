@props([
    'title',
    'description' => null,
])

<div class="w-full">
    <h1 class="text-2xl font-bold text-gray-800">{{ $title }}</h1>

    @if (is_string($description) && trim($description) !== '')
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    @endif

    @if (trim((string) $slot) !== '')
        <div class="mt-3 flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
