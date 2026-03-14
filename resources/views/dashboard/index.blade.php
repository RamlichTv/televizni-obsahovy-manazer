{{--
    Dashboard preview page.
    It now relies on the global toast host for generic success feedback while keeping the read-only grid preview unchanged.
--}}

<x-app-layout title="{{ Auth::user()->name }} {{ __('Dashboard') }}">

    <x-primary-link href="{{ route('dashboard.edit') }}" class="absolute right-2 mt-2 z-20 flex justify-center items-center p-2! rounded-xl">
        <x-icon name="pen" class="size-5"/>
    </x-primary-link>

    {{-- Precompute grid templates and viewport minimums for stable rendering. --}}
    @php
        $rowCount = max(1, (int) ($dashboardLayout['row_count'] ?? 1));
        $columnCount = max(1, (int) ($dashboardLayout['column_count'] ?? 1));
        $rowTemplate = collect($dashboardLayout['row_sizes'] ?? [100])
            ->map(fn ($value): string => max(0.0001, (float) $value) . '%')
            ->implode(' ');
        $columnTemplate = collect($dashboardLayout['column_sizes'] ?? [100])
            ->map(fn ($value): string => max(0.0001, (float) $value) . '%')
            ->implode(' ');
        $minGridWidth = max(360, $columnCount * 170);
        $minGridHeight = max(420, $rowCount * 140);
        $placements = collect($dashboardLayout['placements'] ?? [])->sortBy('order')->values();
        $occupiedCells = [];

        foreach ($placements as $placement) {
            $columnStart = max(1, min($columnCount, (int) ($placement['column_index'] ?? 1)));
            $rowStart = max(1, min($rowCount, (int) ($placement['row_index'] ?? 1)));
            $columnSpan = max(1, min((int) ($placement['column_span'] ?? 1), $columnCount - $columnStart + 1));
            $rowSpan = max(1, min((int) ($placement['row_span'] ?? 1), $rowCount - $rowStart + 1));

            for ($row = $rowStart; $row < ($rowStart + $rowSpan); $row++) {
                for ($column = $columnStart; $column < ($columnStart + $columnSpan); $column++) {
                    $occupiedCells[$row . ':' . $column] = true;
                }
            }
        }
    @endphp

    <section class="flex flex-1 min-h-0 z-0">
        <div class="flex w-full min-h-0 flex-col">
            <div class="flex flex-1 min-h-0 overflow-hidden bg-white">
                <div class="h-full w-full overflow-auto bg-slate-100">
                    {{-- Read-only dashboard grid preview. --}}
                    <div
                        class="relative grid gap-0 border border-slate-200 bg-white shadow-inner"
                        style="grid-template-rows: {{ $rowTemplate }}; grid-template-columns: {{ $columnTemplate }}; width: max(100%, {{ $minGridWidth }}px); height: max(100%, {{ $minGridHeight }}px);"
                    >
                        {{-- Base grid cells provide visual coordinate guidance. --}}
                        @for ($row = 1; $row <= $rowCount; $row++)
                            @for ($column = 1; $column <= $columnCount; $column++)
                                @if (!isset($occupiedCells[$row . ':' . $column]))
                                    <div
                                        class="relative z-0 border border-dashed border-slate-200 bg-white/80"
                                        style="grid-row: {{ $row }} / span 1; grid-column: {{ $column }} / span 1;"
                                    >
                                        <span class="pointer-events-none absolute left-1 top-1 text-[9px] text-slate-300">{{ $row }},{{ $column }}</span>
                                    </div>
                                @endif
                            @endfor
                        @endfor

                        {{-- Render each placed widget with clamped bounds. --}}
                        @forelse ($placements as $placement)
                            @php
                                $columnStart = max(1, min($columnCount, (int) ($placement['column_index'] ?? 1)));
                                $rowStart = max(1, min($rowCount, (int) ($placement['row_index'] ?? 1)));
                                $columnSpan = max(1, min((int) ($placement['column_span'] ?? 1), $columnCount - $columnStart + 1));
                                $rowSpan = max(1, min((int) ($placement['row_span'] ?? 1), $rowCount - $rowStart + 1));
                            @endphp
                            <article
                                class="relative z-20 min-h-0 min-w-0 overflow-hidden bg-white"
                                style="grid-column: {{ $columnStart }} / span {{ $columnSpan }}; grid-row: {{ $rowStart }} / span {{ $rowSpan }};"
                            >
                                <div class="dashboard-module-slot h-full min-h-0 min-w-0 w-full text-xs">
                                    {!! $placement['rendered_html'] ?? '' !!}
                                </div>
                            </article>
                        @empty
                            {{-- Full-grid empty state when no widgets are placed. --}}
                            <div class="grid place-items-center border border-dashed border-gray-300 bg-white text-sm text-gray-500" style="grid-column: 1 / -1; grid-row: 1 / -1;">
                                {{ __('No widgets placed yet. Open editor to build your dashboard layout.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
