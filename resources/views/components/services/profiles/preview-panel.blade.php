{{--
    Component: Profile Preview Panel
    Purpose: Renders page-carousel preview with grid/module placements while keeping the page-level view concise.
--}}

@props(['profile'])

<section
    class="py-8 px-4"
    x-data="profilePreview({
        totalPages: {{ max(1, $profile->pages->count()) }},
        pageNames: @js($profile->pages->pluck('name')->values()),
        i18n: @js([
            'fullscreen' => __('Fullscreen'),
            'exitFullscreen' => __('Exit Fullscreen'),
        ]),
    })"
    x-init="init()"
>
    <div class="max-w-7xl mx-auto space-y-4">
        {{-- Profile-level metadata summary shown above page canvas. --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                <span class="rounded bg-gray-100 px-2 py-1">{{ __('Pages') }}: {{ $profile->pages->count() }}</span>
                <span class="rounded bg-gray-100 px-2 py-1">{{ __('Screens') }}: {{ $profile->screens->count() }}</span>
                <span class="rounded bg-gray-100 px-2 py-1">{{ __('Updated') }}: {{ $profile->updated_at?->diffForHumans() }}</span>
            </div>

            @if (!empty($profile->description))
                <p class="mt-3 text-sm text-gray-700">{{ $profile->description }}</p>
            @endif
        </div>

        @if ($profile->pages->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                {{ __('No pages available in this profile.') }}
            </div>
        @else
            <div x-ref="viewer" class="profile-show-shell flex flex-col flex-1 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm space-y-4">
                <div class="flex items-center justify-between gap-3 m-4">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                        <span class="rounded bg-gray-100 px-2 py-1 font-semibold" x-text="'{{ __('Page') }} ' + (activePage + 1) + ' / ' + totalPages"></span>
                        <span class="rounded bg-gray-100 px-2 py-1" x-text="pageNames[activePage] ?? ''"></span>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        @click="toggleFullscreen()"
                    >
                        <span x-text="isFullscreen ? i18n.exitFullscreen : i18n.fullscreen"></span>
                    </button>
                </div>

                <div class="relative overflow-hidden flex flex-col flex-1"
                     :class="{ 'm-4': !isFullscreen }">
                    @foreach ($profile->pages as $page)
                        <div x-show="activePage === {{ $loop->index }}" x-cloak :class="pageMotionClass" class="space-y-3 flex flex-col flex-1">
                        @php
                            /**
                             * Build CSS grid templates from persisted row/column percentages.
                             * Values are converted to `fr` so resizing remains proportional.
                             */
                            $rowSizes = collect(range(1, max(1, (int) $page->row_count)))
                                ->map(function (int $index) use ($page): float {
                                    $raw = (string) optional($page->rows->firstWhere('index', $index))->height;
                                    if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $raw, $match) === 1) {
                                        return (float) $match[1];
                                    }

                                    return 100 / max(1, (int) $page->row_count);
                                });

                            $columnSizes = collect(range(1, max(1, (int) $page->column_count)))
                                ->map(function (int $index) use ($page): float {
                                    $raw = (string) optional($page->columns->firstWhere('index', $index))->width;
                                    if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $raw, $match) === 1) {
                                        return (float) $match[1];
                                    }

                                    return 100 / max(1, (int) $page->column_count);
                                });

                            $rowTemplate = $rowSizes
                                ->map(fn (float $value): string => max(0.0001, round($value, 4)) . 'fr')
                                ->implode(' ');

                            $columnTemplate = $columnSizes
                                ->map(fn (float $value): string => max(0.0001, round($value, 4)) . 'fr')
                                ->implode(' ');
                        @endphp

                        <div x-show="!isFullscreen" class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                            <span class="rounded bg-gray-100 px-2 py-1">
                                {{ __('Grid') }}: {{ $page->row_count }} x {{ $page->column_count }}
                            </span>
                            <span class="rounded px-2 py-1 {{ $page->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                {{ $page->is_active ? __('Active') : __('Inactive') }}
                            </span>
                            @if ($page->duration)
                                <span class="rounded bg-gray-100 px-2 py-1">
                                    {{ __('Duration') }}: {{ $page->duration }}s
                                </span>
                            @endif
                        </div>

                        <div
                            :class="{ 'flex-1': isFullscreen }"
                            class="relative grid overflow-hidden gap-2"
                            style="grid-template-columns: {{ $columnTemplate }}; grid-template-rows: {{ $rowTemplate }}; height: clamp(520px, 72vh, 920px);"
                        >
                            <button
                                type="button"
                                class="absolute left-0 top-1/2 z-30 opacity-40 hover:opacity-100 -translate-y-1/2 rounded-r-full border border-gray-300 bg-white/95 p-1 py-4 text-gray-700 shadow-sm hover:bg-white"
                                x-show="totalPages > 1"
                                x-cloak
                                @click="previousPage()"
                                title="{{ __('Previous page') }}"
                            >
                                <x-icon name="arrow" class="h-4 w-4 rotate-180" />
                            </button>
                            <button
                                type="button"
                                class="absolute right-0 top-1/2 z-30 opacity-50 hover:opacity-100 -translate-y-1/2 rounded-l-full border border-gray-300 bg-white/95 p-1 py-4 text-gray-700 shadow-sm hover:bg-white"
                                x-show="totalPages > 1"
                                x-cloak
                                @click="nextPage()"
                                title="{{ __('Next page') }}"
                            >
                                <x-icon name="arrow" class="h-4 w-4" />
                            </button>
                            @forelse ($page->modules as $placement)
                                @php
                                    // Clamp placement bounds to prevent overflow and implicit grid track creation.
                                    $maxColumns = max(1, (int) $page->column_count);
                                    $maxRows = max(1, (int) $page->row_count);

                                    $columnStart = max(1, min($maxColumns, (int) ($placement->column_index ?? 1)));
                                    $rowStart = max(1, min($maxRows, (int) ($placement->row_index ?? 1)));

                                    $columnSpanRaw = max(1, (int) ($placement->column_span ?? 1));
                                    $rowSpanRaw = max(1, (int) ($placement->row_span ?? 1));

                                    $columnSpan = max(1, min($columnSpanRaw, $maxColumns - $columnStart + 1));
                                    $rowSpan = max(1, min($rowSpanRaw, $maxRows - $rowStart + 1));
                                @endphp
                                <article
                                    class="min-h-0 min-w-0 overflow-hidden rounded-md border border-gray-300 bg-white"
                                    style="grid-column: {{ $columnStart }} / span {{ $columnSpan }}; grid-row: {{ $rowStart }} / span {{ $rowSpan }};"
                                >
                                    <div class="profile-module-slot min-h-0 min-w-0 h-full w-full text-xs">
                                        @if ($placement->module)
                                            <x-modules.render :module="$placement->module" :config="$placement->config_overrides ?? []" />
                                        @else
                                            <p class="p-2 text-gray-500">{{ __('Module unavailable.') }}</p>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <div class="col-span-full grid min-h-35 place-items-center rounded-md border border-dashed border-gray-300 bg-white text-sm text-gray-500">
                                    {{ __('No modules placed on this page.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
