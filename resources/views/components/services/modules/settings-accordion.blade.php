@props([
    'title',
    'description' => null,
    'expanded' => false,
    'badge' => null,
    'bodyClass' => '',
])

{{--
    Reusable accordion shell for module settings.
    It keeps long editor panels compact while preserving a consistent light app look.
--}}
<div x-data="{ expanded: @js((bool) $expanded) }" {{ $attributes->class('overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm') }}>
    <button
        type="button"
        @click="expanded = ! expanded"
        class="flex w-full items-start justify-between gap-4 px-4 py-4 text-left transition hover:bg-gray-50"
    >
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold text-gray-900">{{ $title }}</p>
                @if (filled($badge))
                    <span class="rounded-md bg-gray-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-widest text-gray-500">
                        {{ $badge }}
                    </span>
                @endif
            </div>

            @if (filled($description))
                <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
            @endif
        </div>

        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 transition" :class="expanded ? 'rotate-180 text-[#077CB6]' : ''">
            <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" aria-hidden="true">
                <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
    </button>

    <div
        x-show="expanded"
        x-transition.opacity.duration.150ms
        x-cloak
        class="border-t border-gray-200"
    >
        <div class="px-4 py-4 {{ $bodyClass }}">
            {{ $slot }}
        </div>
    </div>
</div>
