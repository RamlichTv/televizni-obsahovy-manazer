{{--
    Dashboard editor page.
    It now relies on the shared toast host for both redirect flashes and in-canvas save feedback.
--}}

<x-app-layout title="{{ Auth::user()->name }} {{ __('Dashboard Editor') }}">
    <x-slot name="header">
        <div class="flex w-full flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-3">
                <x-secondary-link href="{{ route('dashboard') }}">
                    <x-icon name="arrow" class="mr-2 h-4 w-4 rotate-180" />
                    {{ __('Back') }}
                </x-secondary-link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ __('Dashboard Editor') }}</h1>
                    <p class="text-xs text-gray-500">{{ __('Build your personal dashboard grid.') }}</p>
                </div>
            </div>

            <x-primary-link href="{{ route('dashboard') }}">
                {{ __('Preview') }}
            </x-primary-link>
        </div>
    </x-slot>

    {{-- Alpine dashboard editor bootstrap (layout payload + UI labels). --}}
    <section
        class="flex flex-1 min-h-0 px-2 pb-2 sm:px-4 sm:pb-4"
        x-data="dashboardEditor({
            layout: @js($dashboardLayout),
            modules: @js($moduleCatalog),
            routes: @js($dashboardRoutes),
            csrfToken: '{{ csrf_token() }}',
            maxTracks: 24,
            i18n: @js([
                'moduleLabel' => __('Module'),
                'uncategorized' => __('Uncategorized'),
                'showSidebar' => __('Show Sidebar'),
                'hideSidebar' => __('Hide Sidebar'),
                'maxRowsReached' => __('Maximum row count reached.'),
                'rowOccupied' => __('Cannot remove row because a module occupies it.'),
                'maxColumnsReached' => __('Maximum column count reached.'),
                'columnOccupied' => __('Cannot remove column because a module occupies it.'),
                'cellOccupied' => __('Selected grid space is already occupied.'),
                'spanOutOfBounds' => __('Placement cannot expand into occupied or out-of-bounds cells.'),
                'requestFailed' => __('Request failed.'),
                'layoutSaved' => __('Dashboard layout saved.'),
            ])
        })"
        x-init="init()"
    >
        <div class="flex w-full min-h-0 flex-col gap-2">
            {{-- Main editor shell: module catalog (left) + grid canvas (right). --}}
            <div class="flex flex-1 min-h-0 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-full min-h-0 w-full flex-col lg:flex-row">

                    {{-- Draggable widget catalog. --}}
                    <aside
                        x-show="sidebarOpen"
                        x-cloak
                        x-transition.opacity.duration.200ms
                        class="flex w-full shrink-0 flex-col border-b border-gray-200 bg-slate-50/70 p-4 lg:w-80 lg:border-b-0 lg:border-r"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-semibold text-gray-900">{{ __('Widget Modules') }}</h2>
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600" x-text="'{{ __('Total') }}: ' + modules.length"></span>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2 py-1 text-[11px] font-semibold text-gray-700 hover:bg-gray-50"
                                @click="toggleSidebar()"
                            >
                                <span x-text="i18n.hideSidebar"></span>
                            </button>
                        </div>

                        <div class="mt-3">
                            <x-text-input
                                type="text"
                                class="block w-full text-xs"
                                x-model="moduleSearch"
                                placeholder="{{ __('Search modules...') }}"
                            />
                        </div>

                        <div class="mt-3 flex-1 min-h-0 overflow-auto pr-1">
                            <div class="space-y-2">
                                <template x-for="group in groupedModules()" :key="'module-group-' + group.key">
                                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white/90 shadow-sm">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left"
                                            @click="toggleModuleGroup(group.key)"
                                        >
                                            <div class="min-w-0">
                                                <p class="truncate text-xs font-semibold uppercase tracking-[0.18em] text-gray-500" x-text="group.label"></p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="rounded bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600" x-text="group.modules.length"></span>
                                                <x-icon
                                                    name="arr"
                                                    class="h-4 w-4 shrink-0 text-gray-400 transition"
                                                    x-bind:class="isModuleGroupOpen(group.key) ? 'rotate-180' : ''"
                                                />
                                            </div>
                                        </button>

                                        <div
                                            x-show="isModuleGroupOpen(group.key)"
                                            x-transition.opacity.duration.150ms
                                            class="border-t border-gray-200 p-2"
                                        >
                                            <div class="space-y-2">
                                                <template x-for="module in group.modules" :key="'module-' + group.key + '-' + module.id">
                                                    <article
                                                        draggable="true"
                                                        @dragstart="startModuleDrag(module, $event)"
                                                        class="cursor-grab rounded-lg border border-gray-200 bg-white p-3 shadow-sm transition hover:border-[#5EB4EE]"
                                                    >
                                                        <h3 class="text-sm font-semibold text-gray-900" x-text="module.name"></h3>
                                                    </article>
                                                </template>
                                            </div>
                                        </div>
                                    </section>
                                </template>
                            </div>

                            <p x-show="groupedModules().length === 0" x-cloak class="text-xs text-gray-500">
                                {{ __('No modules match your search.') }}
                            </p>
                        </div>
                    </aside>

                    <div class="flex min-h-0 flex-1 flex-col">

                        {{-- Grid controls and save action. --}}
                        <div class="border-b border-gray-200 p-3">
                            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                                    <span class="rounded bg-gray-100 px-2 py-0.5" x-text="'{{ __('Rows') }}: ' + layout.row_count"></span>
                                    <span class="rounded bg-gray-100 px-2 py-0.5" x-text="'{{ __('Columns') }}: ' + layout.column_count"></span>
                                    <span class="rounded bg-gray-100 px-2 py-0.5" x-text="'{{ __('Placed Widgets') }}: ' + layout.placements.length"></span>
                                    <span class="rounded bg-gray-100 px-2 py-0.5">{{ __('Drag modules from left sidebar into the canvas.') }}</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        @click="toggleSidebar()"
                                    >
                                        <span x-text="sidebarOpen ? i18n.hideSidebar : i18n.showSidebar"></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        @click="addRow()"
                                    >
                                        <x-icon name="plus-circle" class="h-4 w-4" />
                                        {{ __('Row') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        @click="removeRow()"
                                    >
                                        <x-icon name="x" class="h-4 w-4" />
                                        {{ __('Row') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        @click="addColumn()"
                                    >
                                        <x-icon name="plus-circle" class="h-4 w-4" />
                                        {{ __('Column') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        @click="removeColumn()"
                                    >
                                        <x-icon name="x" class="h-4 w-4" />
                                        {{ __('Column') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-md border border-transparent bg-[#077CB6] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#056796] disabled:opacity-60"
                                        @click="saveLayout()"
                                        :disabled="isSaving"
                                    >
                                        <span x-show="!isSaving">{{ __('Save Layout') }}</span>
                                        <span x-show="isSaving" x-cloak>{{ __('Saving...') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 min-h-0 bg-slate-100">
                            <div class="h-full w-full overflow-auto">
                                <div class="relative" :style="canvasStyle()">
                                    
                                    {{-- Grid background cells and placed module cards. --}}
                                    <div
                                        x-ref="grid"
                                        class="grid h-full w-full gap-0 border border-slate-200 bg-white shadow-inner"
                                        :style="gridStyle()"
                                    >
                                        <template x-for="cell in gridCells()" :key="'cell-' + cell.row + '-' + cell.column">
                                            <div
                                                x-show="!isCellCovered(cell.row, cell.column)"
                                                class="relative z-0 border border-dashed border-slate-200 bg-white/80 transition hover:bg-sky-50/80"
                                                :style="cellStyle(cell.row, cell.column)"
                                                @dragover.prevent
                                                @drop.prevent="dropOnCell(cell.row, cell.column)"
                                            >
                                                <span class="pointer-events-none absolute left-1 top-1 text-[9px] text-slate-300" x-text="cell.row + ',' + cell.column"></span>
                                            </div>
                                        </template>

                                        <template x-for="placement in layout.placements" :key="'placement-' + placement.id">
                                            <article
                                                draggable="true"
                                                @dragstart="startPlacementDrag(placement, $event)"
                                                @click="selectedPlacementId = placement.id"
                                                class="group relative z-20 min-h-0 min-w-0 overflow-hidden rounded-md border bg-white shadow-sm transition"
                                                :class="selectedPlacementId === placement.id ? 'border-[#077CB6] ring-2 ring-[#5EB4EE]/50' : 'border-gray-300 hover:border-[#5EB4EE]'"
                                                :style="placementStyle(placement)"
                                            >
                                                <div
                                                    class="dashboard-module-slot h-full min-h-0 min-w-0 w-full text-[11px]"
                                                    x-init="mountModulePreview($el, placement)"
                                                    x-effect="mountModulePreview($el, placement)"
                                                ></div>
                                                <template x-if="!placement.rendered_html || placement.rendered_html === ''">
                                                    <div class="grid h-full place-items-center bg-slate-50 p-3 text-center text-xs text-gray-500">
                                                        <span>{{ __('Module preview unavailable.') }}</span>
                                                    </div>
                                                </template>

                                                <div class="absolute bottom-1 right-1 flex items-center gap-1 opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">
                                                    <span class="rounded bg-white/90 px-1.5 py-0.5 text-[10px] text-gray-600 shadow-sm" x-text="'R' + placement.row_span + ' × C' + placement.column_span"></span>
                                                    <button
                                                        type="button"
                                                        class="rounded bg-white/90 px-1 text-[9px] font-semibold text-gray-600 shadow-sm hover:bg-white"
                                                        @click.stop="changePlacementSpan(placement, 'row', -1)"
                                                        title="{{ __('Decrease row span') }}"
                                                    >-R</button>
                                                    <button
                                                        type="button"
                                                        class="rounded bg-white/90 px-1 text-[9px] font-semibold text-gray-600 shadow-sm hover:bg-white"
                                                        @click.stop="changePlacementSpan(placement, 'row', 1)"
                                                        title="{{ __('Increase row span') }}"
                                                    >+R</button>
                                                    <button
                                                        type="button"
                                                        class="rounded bg-white/90 px-1 text-[9px] font-semibold text-gray-600 shadow-sm hover:bg-white"
                                                        @click.stop="changePlacementSpan(placement, 'column', -1)"
                                                        title="{{ __('Decrease column span') }}"
                                                    >-C</button>
                                                    <button
                                                        type="button"
                                                        class="rounded bg-white/90 px-1 text-[9px] font-semibold text-gray-600 shadow-sm hover:bg-white"
                                                        @click.stop="changePlacementSpan(placement, 'column', 1)"
                                                        title="{{ __('Increase column span') }}"
                                                    >+C</button>
                                                    <button
                                                        type="button"
                                                        class="rounded bg-white/90 p-0.5 text-red-600 shadow-sm hover:bg-white"
                                                        @click.stop="removePlacement(placement)"
                                                        title="{{ __('Remove module') }}"
                                                    >
                                                        <x-icon name="x" class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </article>
                                        </template>
                                    </div>

                                    {{-- Interactive row/column resize handles. --}}
                                    <template x-for="(position, boundaryIndex) in columnBoundaries()" :key="'col-handle-' + boundaryIndex">
                                        <button
                                            type="button"
                                            class="absolute top-0 z-30 h-full w-3 -translate-x-1/2 cursor-col-resize bg-transparent transition hover:bg-sky-200/40 touch-none"
                                            :style="'left:' + position + '%'"
                                            @pointerdown.prevent="startResize('column', boundaryIndex, $event)"
                                            title="{{ __('Resize columns') }}"
                                        ></button>
                                    </template>

                                    <template x-for="(position, boundaryIndex) in rowBoundaries()" :key="'row-handle-' + boundaryIndex">
                                        <button
                                            type="button"
                                            class="absolute left-0 z-30 h-3 w-full -translate-y-1/2 cursor-row-resize bg-transparent transition hover:bg-sky-200/40 touch-none"
                                            :style="'top:' + position + '%'"
                                            @pointerdown.prevent="startResize('row', boundaryIndex, $event)"
                                            title="{{ __('Resize rows') }}"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
</x-app-layout>
