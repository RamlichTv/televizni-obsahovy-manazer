{{--
    Overview:
    This view composes the modules > widgets > content-carousel interface, arranging reusable components and route-driven actions so the page stays readable while presentation concerns remain separated from backend logic.
--}}

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\ContentObject> $items */
    $items = collect($payload['items'] ?? []);
    $title = trim((string) ($config['title'] ?? $module->name));
    $subtitle = trim((string) ($config['subtitle'] ?? ''));
    $showType = (bool) ($config['show_type'] ?? true);
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="mb-3 flex items-start justify-between gap-4">
        <div>
            <h3 class="text-base font-semibold text-gray-900">{{ $title !== '' ? $title : $module->name }}</h3>
            @if ($subtitle !== '')
                <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>
        <span class="text-xs text-gray-500">{{ __('Items:') }} {{ $items->count() }}</span>
    </div>

    @if ($items->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500">
            {{ __('No content matched this module query.') }}
        </div>
    @else
        <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2">
            @foreach ($items as $item)
                @php
                    $url = $item->fileUrl();
                @endphp

                <article class="min-w-55 max-w-55 snap-start overflow-hidden rounded-lg border border-gray-200 bg-white">
                    <div class="h-28 bg-gray-100">
                        @if ($url)
                            <img src="{{ $url }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-2xl text-gray-400">[ ]</div>
                        @endif
                    </div>
                    <div class="space-y-1 p-3">
                        <p class="truncate text-sm font-semibold text-gray-900" title="{{ $item->name }}">
                            {{ $item->name }}
                        </p>
                        @if ($showType && $item->type)
                            <p class="text-xs text-gray-500">{{ $item->type->name }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
