@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\ContentObject> $items */
    $items = collect($payload['items'] ?? []);
    $title = trim((string) ($config['title'] ?? $module->name));
    $emptyText = trim((string) ($config['empty_text'] ?? __('No content available.')));
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900">{{ $title !== '' ? $title : $module->name }}</h3>
        <span class="text-xs text-gray-500">{{ __('Count:') }} {{ $items->count() }}</span>
    </div>

    @if ($items->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500">
            {{ $emptyText }}
        </div>
    @else
        <ul class="space-y-2">
            @foreach ($items as $item)
                <li class="rounded-lg border border-gray-200 px-3 py-2">
                    <p class="truncate text-sm font-medium text-gray-900">{{ $item->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $item->type?->name ?? __('Unknown type') }}
                        @if ($item->published_at)
                            | {{ $item->published_at->format('d.m.Y H:i') }}
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</section>
