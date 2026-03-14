{{--
    Overview:
    This view composes the services > screens > show interface, arranging reusable components and route-driven actions so the page stays readable while presentation concerns remain separated from backend logic.
--}}

@php
    $profile = $screen->profile;
    // Prefer active pages; fallback to all profile pages if none are marked active.
    $pages = collect($profile?->pages ?? [])->filter(fn ($page) => (bool) $page->is_active)->values();

    if ($pages->isEmpty() && $profile !== null) {
        $pages = $profile->pages->values();
    }

    $autoRotate = (bool) data_get($profile?->settings, 'playback.auto_rotate', true);
    $loopPages = (bool) data_get($profile?->settings, 'playback.loop_pages', true);
    $showPageName = (bool) data_get($profile?->settings, 'display.show_page_name', true);
    $showPageIndicators = (bool) data_get($profile?->settings, 'display.show_page_indicators', true);
    $transitionType = strtolower((string) data_get($profile?->settings, 'animation.transition', 'slide'));
    if (!in_array($transitionType, ['slide', 'fade', 'none'], true)) {
        $transitionType = 'slide';
    }

    $transitionDurationMs = (int) data_get($profile?->settings, 'animation.transition_duration_ms', 300);
    $transitionDurationMs = max(100, min(10000, $transitionDurationMs));

    // Per-page rotation duration with a safe fallback when page duration is empty.
    $durations = $pages
        ->map(function ($page): int {
            $duration = (int) ($page->duration ?? 0);

            return $duration > 0 ? min($duration, 86400) : 10;
        })
        ->values();

    if ($durations->isEmpty()) {
        $durations = collect([10]);
    }

    // Use configured pixel dimensions as the monitor canvas baseline.
    $renderWidth = (int) ($screen->width ?? 0);
    $renderHeight = (int) ($screen->height ?? 0);
    $orientationName = strtolower((string) ($screen->orientation?->name ?? ''));

    if ($renderWidth <= 0 && $renderHeight <= 0) {
        $renderWidth = 1920;
        $renderHeight = 1080;
    } elseif ($renderWidth > 0 && $renderHeight <= 0) {
        $renderHeight = (int) max(100, round($renderWidth * 9 / 16));
    } elseif ($renderHeight > 0 && $renderWidth <= 0) {
        $renderWidth = (int) max(100, round($renderHeight * 16 / 9));
    }

    $renderWidth = max(100, min($renderWidth, 16384));
    $renderHeight = max(100, min($renderHeight, 16384));

    // Normalize the playback canvas to the selected orientation.
    if ($orientationName === 'portrait' && $renderWidth > $renderHeight) {
        [$renderWidth, $renderHeight] = [$renderHeight, $renderWidth];
    } elseif ($orientationName === 'landscape' && $renderHeight > $renderWidth) {
        [$renderWidth, $renderHeight] = [$renderHeight, $renderWidth];
    }

    // Non-online statuses render dedicated fallback pages instead of profile playback.
    $statusName = strtolower((string) ($screen->status?->name ?? 'online'));
    $screenMode = in_array($statusName, ['offline', 'maintenance'], true) ? $statusName : 'online';
    $screensaverModule = collect($screen->screensaverModules ?? [])
        ->first(fn ($module): bool => (bool) ($module->is_active ?? false));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Screen Monitor') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-screen w-screen overflow-hidden bg-gray-100 text-gray-900">
        <main
            class="relative h-full w-full"
            {{-- Bootstrap the Alpine player with pre-normalized server data so the client only handles timing/state. --}}
            x-data="screenPlayer({
                pageCount: {{ max(1, $pages->count()) }},
                pageNames: @js($pages->pluck('name')->values()),
                durations: @js($durations),
                autoRotate: @js($autoRotate),
                loopPages: @js($loopPages),
                showPageName: @js($showPageName),
                showPageIndicators: @js($showPageIndicators),
                transitionType: @js($transitionType),
                transitionDuration: @js($transitionDurationMs),
                refreshInterval: @js($screen->auto_refresh_interval),
                renderWidth: @js($renderWidth),
                renderHeight: @js($renderHeight),
            })"
            x-init="init()"
            x-on:resize.window.debounce.100ms="fitCanvas()"
            x-on:pointerdown.window="handleUserActivity()"
            x-on:keydown.window="handleUserActivity()"
            x-on:wheel.window.passive="handleUserActivity()"
            x-on:touchstart.window.passive="handleUserActivity()"
        >
            @if ($screenMode === 'offline')
                {{-- Offline/maintenance modes bypass normal profile playback completely. --}}
                @if ($screensaverModule)
                    <section class="h-full w-full bg-slate-900 p-2">
                        <article class="h-full w-full min-h-0 overflow-hidden rounded-lg bg-white shadow-sm">
                            <x-modules.render :module="$screensaverModule" />
                        </article>
                    </section>
                @else
                    <section class="grid h-full w-full place-items-center bg-slate-900 px-8 text-center text-white">
                        <div class="max-w-2xl">
                            <p class="text-xs font-semibold tracking-[0.18em] text-slate-300">{{ __('SCREEN STATUS') }}</p>
                            <h1 class="mt-3 text-5xl font-bold">{{ __('OFFLINE') }}</h1>
                            <p class="mt-4 text-base text-slate-300">{{ __('Playback is currently disabled for this screen.') }}</p>
                        </div>
                    </section>
                @endif
            @elseif ($screenMode === 'maintenance')
                @if ($screensaverModule)
                    <section class="h-full w-full bg-amber-50 p-2">
                        <article class="h-full w-full min-h-0 overflow-hidden rounded-lg bg-white shadow-sm">
                            <x-modules.render :module="$screensaverModule" />
                        </article>
                    </section>
                @else
                    <section class="grid h-full w-full place-items-center bg-amber-50 px-8 text-center text-amber-900">
                        <div class="max-w-2xl">
                            <p class="text-xs font-semibold tracking-[0.18em] text-amber-600">{{ __('SCREEN STATUS') }}</p>
                            <h1 class="mt-3 text-5xl font-bold">{{ __('MAINTENANCE') }}</h1>
                            <p class="mt-4 text-base text-amber-700">{{ __('This screen is temporarily unavailable while maintenance is in progress.') }}</p>
                        </div>
                    </section>
                @endif
            @else
                {{-- The viewport scales a fixed pixel canvas so rendered modules respect the configured screen size. --}}
                <div class="absolute inset-0 overflow-hidden bg-gray-100" x-ref="viewport">
                    <div class="absolute overflow-hidden border border-gray-300 bg-gray-100 shadow-sm" :style="canvasStyle">
                        @if ($profile === null)
                            {{-- Screen is reachable, but has no profile assigned yet. --}}
                            <section class="grid h-full place-items-center px-8 text-center">
                                <div>
                                    <h1 class="text-3xl font-semibold">{{ __('Screen not configured') }}</h1>
                                    <p class="mt-3 text-base text-gray-600">{{ __('No profile assigned to this screen.') }}</p>
                                </div>
                            </section>
                        @elseif ($pages->isEmpty())
                            {{-- Profile exists but has no pages to render. --}}
                            <section class="grid h-full place-items-center px-8 text-center">
                                <div>
                                    <h1 class="text-3xl font-semibold">{{ __('No profile pages') }}</h1>
                                    <p class="mt-3 text-base text-gray-600">{{ __('The assigned profile has no pages yet.') }}</p>
                                </div>
                            </section>
                        @else
                            <div class="relative h-full w-full">
                                @foreach ($pages as $page)
                                    @php
                                        // Build CSS grid templates from normalized row/column weights.
                                        $rowCount = max(1, (int) $page->row_count);
                                        $columnCount = max(1, (int) $page->column_count);

                                        $rowTemplate = collect(range(1, $rowCount))
                                            ->map(function (int $index) use ($page, $rowCount): string {
                                                $raw = (string) optional($page->rows->firstWhere('index', $index))->height;
                                                if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $raw, $match) === 1) {
                                                    return max(0.0001, (float) $match[1]) . 'fr';
                                                }

                                                return round(100 / $rowCount, 4) . 'fr';
                                            })
                                            ->implode(' ');

                                        $columnTemplate = collect(range(1, $columnCount))
                                            ->map(function (int $index) use ($page, $columnCount): string {
                                                $raw = (string) optional($page->columns->firstWhere('index', $index))->width;
                                                if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $raw, $match) === 1) {
                                                    return max(0.0001, (float) $match[1]) . 'fr';
                                                }

                                                return round(100 / $columnCount, 4) . 'fr';
                                            })
                                            ->implode(' ');
                                    @endphp

                                    {{-- x-if keeps only one page mounted, preventing overlay artifacts. --}}
                                    <template x-if="activePage === {{ $loop->index }}">
                                        <section
                                            x-cloak
                                            class="absolute h-full w-full"
                                            :class="pageMotionClass"
                                            :style="pageMotionStyle"
                                        >
                                            <div class="grid h-full w-full gap-1 border border-gray-300 bg-gray-200 p-1" style="grid-template-columns: {{ $columnTemplate }}; grid-template-rows: {{ $rowTemplate }};">
                                                @forelse ($page->modules->sortBy('order') as $placement)
                                                    @php
                                                        // Clamp placement coordinates/spans so invalid data cannot break the grid.
                                                        $columnStart = max(1, min($columnCount, (int) ($placement->column_index ?? 1)));
                                                        $rowStart = max(1, min($rowCount, (int) ($placement->row_index ?? 1)));

                                                        $columnSpanRaw = max(1, (int) ($placement->column_span ?? 1));
                                                        $rowSpanRaw = max(1, (int) ($placement->row_span ?? 1));

                                                        $columnSpan = max(1, min($columnSpanRaw, $columnCount - $columnStart + 1));
                                                        $rowSpan = max(1, min($rowSpanRaw, $rowCount - $rowStart + 1));
                                                    @endphp
                                                    <article
                                                        class="min-h-0 min-w-0 overflow-hidden rounded-lg bg-white text-gray-900 shadow-sm"
                                                        style="grid-column: {{ $columnStart }} / span {{ $columnSpan }}; grid-row: {{ $rowStart }} / span {{ $rowSpan }};"
                                                    >
                                                        <div class="profile-module-slot h-full w-full">
                                                            @if ($placement->module)
                                                                <x-modules.render :module="$placement->module" :config="$placement->config_overrides ?? []" />
                                                            @else
                                                                <div class="grid h-full place-items-center p-4 text-xs text-gray-600">
                                                                    {{ __('Module unavailable.') }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </article>
                                                @empty
                                                    <div class="col-span-full row-span-full grid place-items-center bg-white text-center text-sm text-gray-600">
                                                        {{ __('No modules placed on this page.') }}
                                                    </div>
                                                @endforelse
                                            </div>
                                        </section>
                                    </template>
                                @endforeach
                            </div>
                        @endif

                        @if ($screen->is_interactive)
                            {{-- Manual page navigation is available only on interactive screens. --}}
                            <button
                                type="button"
                                x-show="pageCount > 1"
                                x-cloak
                                class="absolute left-0 top-1/2 z-30 -translate-y-1/2 rounded-r-full border border-transparent bg-[#077cb6] p-1 py-4 text-white shadow-sm transition ease-in-out duration-150 hover:bg-[#E69C24] focus:bg-[#D78500] active:bg-[#D78500] focus:outline-none focus:ring-2 focus:ring-[#D78500] focus:ring-offset-2"
                                @click="previousPage()"
                                aria-label="{{ __('Previous page') }}"
                                title="{{ __('Previous page') }}"
                            >
                                <x-icon name="arrow" class="size-6 rotate-180" />
                            </button>

                            <button
                                type="button"
                                x-show="pageCount > 1"
                                x-cloak
                                class="absolute right-0 top-1/2 z-30 -translate-y-1/2 rounded-l-full border border-transparent bg-[#077cb6] p-1 py-4 text-white shadow-sm transition ease-in-out duration-150 hover:bg-[#E69C24] focus:bg-[#D78500] active:bg-[#D78500] focus:outline-none focus:ring-2 focus:ring-[#D78500] focus:ring-offset-2"
                                @click="nextPage()"
                                aria-label="{{ __('Next page') }}"
                                title="{{ __('Next page') }}"
                            >
                                <x-icon name="arrow" class="size-6" />
                            </button>
                        @endif

                        @if ($showPageName || $showPageIndicators)
                            <div
                                x-cloak
                                class="pointer-events-none absolute bottom-3 right-3 rounded-md border border-gray-200 bg-white/90 px-3 py-1.5 text-xs text-gray-700 shadow-sm"
                            >
                                <p x-show="showPageName" x-text="pageNames[activePage] ?? ''"></p>
                                <p x-show="showPageIndicators" x-text="(activePage + 1) + ' / ' + pageCount"></p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </main>

        <script>
            function screenPlayer(config) {
                return {
                    activePage: 0,
                    pageCount: Number(config.pageCount) || 1,
                    pageNames: Array.isArray(config.pageNames) ? config.pageNames : [],
                    durations: Array.isArray(config.durations) ? config.durations : [10],
                    autoRotate: Boolean(config.autoRotate),
                    loopPages: Boolean(config.loopPages),
                    showPageName: Boolean(config.showPageName),
                    showPageIndicators: Boolean(config.showPageIndicators),
                    transitionType: String(config.transitionType || 'slide'),
                    transitionDuration: Math.max(100, Math.min(10000, Number(config.transitionDuration) || 300)),
                    refreshInterval: Number(config.refreshInterval),
                    renderWidth: Math.max(100, Number(config.renderWidth) || 1920),
                    renderHeight: Math.max(100, Number(config.renderHeight) || 1080),
                    rotationTimer: null,
                    refreshTimer: null,
                    motionTimer: null,
                    pageMotionClass: '',
                    pageMotionStyle: '',
                    canvasStyle: '',

                    init() {
                        this.fitCanvas();
                        // Start both timers once Alpine initializes the player.
                        this.scheduleRotation();
                        this.scheduleRefresh();
                    },

                    fitCanvas() {
                        const viewport = this.$refs.viewport;
                        const viewportWidth = (viewport && viewport.clientWidth) ? viewport.clientWidth : window.innerWidth;
                        const viewportHeight = (viewport && viewport.clientHeight) ? viewport.clientHeight : window.innerHeight;

                        const scale = Math.min(
                            viewportWidth / this.renderWidth,
                            viewportHeight / this.renderHeight,
                        );
                        const safeScale = Number.isFinite(scale) && scale > 0 ? scale : 1;

                        const scaledWidth = this.renderWidth * safeScale;
                        const scaledHeight = this.renderHeight * safeScale;
                        const left = Math.max(0, (viewportWidth - scaledWidth) / 2);
                        const top = Math.max(0, (viewportHeight - scaledHeight) / 2);

                        this.canvasStyle = `width: ${this.renderWidth}px; height: ${this.renderHeight}px; left: ${left}px; top: ${top}px; transform: scale(${safeScale}); transform-origin: top left;`;
                    },

                    scheduleRotation() {
                        window.clearTimeout(this.rotationTimer);

                        if (!this.autoRotate || this.pageCount <= 1) {
                            return;
                        }

                        const seconds = Math.max(1, Number(this.durations[this.activePage]) || 10);
                        this.rotationTimer = window.setTimeout(() => {
                            this.nextPage();
                        }, seconds * 1000);
                    },

                    nextPage() {
                        if (this.pageCount <= 1) {
                            return;
                        }

                        const nextIndex = this.activePage + 1;
                        if (nextIndex >= this.pageCount) {
                            if (!this.loopPages) {
                                this.activePage = this.pageCount - 1;
                                return;
                            }

                            this.activePage = 0;
                        } else {
                            this.activePage = nextIndex;
                        }

                        this.applyTransitionMotion('next');
                        this.scheduleRotation();
                    },

                    previousPage() {
                        if (this.pageCount <= 1) {
                            return;
                        }

                        const previousIndex = this.activePage - 1;
                        if (previousIndex < 0) {
                            if (!this.loopPages) {
                                this.activePage = 0;
                                return;
                            }

                            this.activePage = this.pageCount - 1;
                        } else {
                            this.activePage = previousIndex;
                        }

                        this.applyTransitionMotion('prev');
                        this.scheduleRotation();
                    },

                    applyTransitionMotion(direction) {
                        window.clearTimeout(this.motionTimer);
                        this.pageMotionClass = '';
                        this.pageMotionStyle = '';

                        if (this.transitionType === 'none') {
                            return;
                        }

                        // Re-apply animation class on next frame so CSS keyframes retrigger reliably.
                        requestAnimationFrame(() => {
                            this.pageMotionClass = this.transitionType === 'fade'
                                ? 'screen-page-fade'
                                : (direction === 'prev' ? 'profile-page-slide-prev' : 'profile-page-slide-next');
                            this.pageMotionStyle = `animation-duration: ${this.transitionDuration}ms;`;

                            this.motionTimer = window.setTimeout(() => {
                                this.pageMotionClass = '';
                                this.pageMotionStyle = '';
                            }, this.transitionDuration + 40);
                        });
                    },

                    scheduleRefresh() {
                        window.clearTimeout(this.refreshTimer);

                        if (!Number.isFinite(this.refreshInterval) || this.refreshInterval < 5) {
                            return;
                        }

                        this.refreshTimer = window.setTimeout(() => {
                            window.location.reload();
                        }, this.refreshInterval * 1000);
                    },

                    handleUserActivity() {
                        this.scheduleRefresh();
                    },
                };
            }
        </script>
    </body>
</html>
