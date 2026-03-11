{{--
    Component: Layout Editor Canvas
    Purpose: Encapsulates the visual drag-and-drop page grid editor so the main edit view stays focused on composition.
    Notes: All interactivity is driven by the surrounding Alpine state from `profileLayoutEditor`.
--}}

<div x-ref="editorViewport" class="profile-editor-shell overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="profile-editor-stage flex min-h-[76vh] flex-col lg:flex-row">
        {{-- Collapsible left rail: page tabs plus grouped draggable module catalog. --}}
        <aside
            x-show="sidebarOpen"
            x-cloak
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-x-3"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-3"
            class="flex w-full shrink-0 flex-col border-b border-gray-200 bg-slate-50/70 p-4 lg:w-80 lg:border-b-0 lg:border-r"
        >
            {{-- Rail header exposes the visibility toggle and explains what lives in the sidebar. --}}
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('Workspace') }}</h2>
                    <p class="text-[11px] text-gray-500">{{ __('Pages and module library.') }}</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                    @click="toggleSidebar()"
                    x-bind:aria-expanded="sidebarOpen.toString()"
                    title="{{ __('Hide sidebar') }}"
                >
                    {{ __('Hide') }}
                </button>
            </div>

            {{-- Page tabs remain at the top of the rail for quick carousel navigation. --}}
            <div class="mt-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('Pages') }}</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        @click="addPage()"
                        title="{{ __('Add page') }}"
                    >
                        <x-icon name="plus-circle" class="h-4 w-4" />
                        {{ __('Add') }}
                    </button>
                </div>

                <div class="mt-3 space-y-2 max-h-56 overflow-auto pr-1">
                    <template x-for="(page, index) in pages" :key="'page-tab-' + page.id">
                        <button
                            type="button"
                            @click="setActivePage(index)"
                            :class="activePageIndex === index ? 'border-[#077CB6] bg-[#077CB6] text-white' : 'border-gray-300 bg-white text-gray-700'"
                            class="w-full rounded-md border px-3 py-2 text-left text-xs font-semibold transition"
                        >
                            <span x-text="page.name"></span>
                            <span class="ml-2 text-[10px] opacity-80" x-text="'(' + page.row_count + 'x' + page.column_count + ')'"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Module catalog stays searchable, but results are grouped into category dropdowns. --}}
            <div class="mt-5 flex min-h-0 flex-1 flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">{{ __('Modules') }}</h2>
                        <p class="text-[11px] text-gray-500">{{ __('Grouped by category for drag and drop.') }}</p>
                    </div>
                    <span class="rounded bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600" x-text="'{{ __('Total') }}: ' + filteredModules().length"></span>
                </div>

                <div class="mt-2">
                    <x-text-input
                        type="text"
                        class="block w-full text-xs"
                        x-model="moduleSearch"
                        placeholder="{{ __('Search modules...') }}"
                    />
                </div>

                <div class="mt-3 flex-1 min-h-0 space-y-2 overflow-auto pr-1">
                    <template x-for="group in groupedFilteredModules()" :key="'module-group-' + group.key">
                        <section class="rounded-lg border border-gray-200 bg-gray-50 transition-all duration-200 hover:border-[#5EB4EE]/40">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 px-3 py-3 text-left transition-colors duration-200 hover:text-[#077CB6]"
                                @click="toggleModuleCategory(group.key)"
                                x-bind:aria-expanded="isModuleCategoryOpen(group.key).toString()"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-800" x-text="group.label"></span>
                                    <span class="block text-[11px] text-gray-500">{{ __('Module Category') }}</span>
                                </span>
                                <span class="inline-flex items-center gap-2">
                                    <span class="rounded-full border border-gray-200 bg-white px-2 py-1 text-[11px] text-gray-600" x-text="group.modules.length"></span>
                                    <x-icon name="arr" class="h-4 w-4 text-gray-500 transition-transform" x-bind:class="isModuleCategoryOpen(group.key) ? 'rotate-180' : ''" />
                                </span>
                            </button>

                            <div
                                x-show="isModuleCategoryOpen(group.key)"
                                x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                class="space-y-2 px-3 pb-3"
                            >
                                <template x-for="module in group.modules" :key="'module-' + group.key + '-' + module.id">
                                    <article
                                        draggable="true"
                                        @dragstart="startModuleDrag(module, $event)"
                                        class="cursor-grab rounded-lg border border-gray-200 bg-white p-3 shadow-sm transition hover:border-[#5EB4EE]"
                                    >
                                        <h3 class="text-sm font-semibold text-gray-900" x-text="module.name"></h3>
                                        <p class="mt-1 text-[11px] text-gray-500">{{ __('Drag into layout') }}</p>
                                    </article>
                                </template>
                            </div>
                        </section>
                    </template>

                    <p x-show="groupedFilteredModules().length === 0" class="text-xs text-gray-500" x-cloak>
                        {{ __('No modules match your search.') }}
                    </p>
                </div>
            </div>
        </aside>

        {{-- Main workspace: active page toolbar + interactive grid canvas. --}}
        <div class="relative flex flex-1 flex-col">
            {{-- Floating reopen control keeps the module rail accessible after it is hidden. --}}
            <button
                type="button"
                class="absolute left-3 top-3 z-40 inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white/95 px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm backdrop-blur hover:bg-white"
                x-show="!sidebarOpen"
                x-cloak
                @click="toggleSidebar()"
                x-bind:aria-expanded="sidebarOpen.toString()"
                title="{{ __('Show sidebar') }}"
            >
                {{ __('Show Sidebar') }}
            </button>

            <template x-if="activePage()">
                <div class="flex h-full flex-col" :class="pageMotionClass">
                    <div class="border-b border-gray-200 p-3">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                            <div class="flex flex-1 items-center gap-2">
                                <x-input-label for="editor_page_name" :value="__('Page')" />
                                <x-text-input
                                    id="editor_page_name"
                                    type="text"
                                    class="block w-full max-w-xs text-sm"
                                    x-model="pages[activePageIndex].name"
                                    @change="persistActivePage()"
                                />
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    @click="addRow()"
                                    title="{{ __('Add row') }}"
                                >
                                    <x-icon name="plus-circle" class="h-4 w-4" />
                                    {{ __('Row') }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    @click="removeRow()"
                                    title="{{ __('Remove row') }}"
                                >
                                    <x-icon name="x" class="h-4 w-4" />
                                    {{ __('Row') }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    @click="addColumn()"
                                    title="{{ __('Add column') }}"
                                >
                                    <x-icon name="plus-circle" class="h-4 w-4" />
                                    {{ __('Column') }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    @click="removeColumn()"
                                    title="{{ __('Remove column') }}"
                                >
                                    <x-icon name="x" class="h-4 w-4" />
                                    {{ __('Column') }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    @click="removeActivePage()"
                                    title="{{ __('Delete page') }}"
                                >
                                    <x-icon name="trash" class="h-4 w-4" />
                                    {{ __('Delete Page') }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    @click="resetGridSizes()"
                                    title="{{ __('Reset row and column sizes') }}"
                                >
                                    <x-icon name="min-cog" class="h-4 w-4" />
                                    {{ __('Reset Grid') }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    @click="toggleFullscreen()"
                                >
                                    <span x-text="isFullscreen ? '{{ __('Exit Fullscreen') }}' : '{{ __('Fullscreen') }}'"></span>
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-md border border-transparent bg-[#077CB6] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#056796] disabled:opacity-60"
                                    @click="persistActivePage()"
                                    :disabled="isSaving"
                                >
                                    <span x-show="!isSaving">{{ __('Save Layout') }}</span>
                                    <span x-show="isSaving" x-cloak>{{ __('Saving...') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                            <span class="rounded bg-gray-100 px-2 py-0.5 font-semibold" x-text="'{{ __('Page') }} ' + (activePageIndex + 1) + ' / ' + pages.length"></span>
                            <span class="rounded bg-gray-100 px-2 py-0.5" x-text="'{{ __('Rows') }}: ' + activePage().row_count"></span>
                            <span class="rounded bg-gray-100 px-2 py-0.5" x-text="'{{ __('Columns') }}: ' + activePage().column_count"></span>
                            <span class="rounded bg-gray-100 px-2 py-0.5" x-text="'{{ __('Modules') }}: ' + activePage().placements.length"></span>
                            <span
                                class="rounded bg-gray-100 px-2 py-0.5"
                                x-text="sidebarOpen ? '{{ __('Drag modules from the sidebar into the canvas.') }}' : '{{ __('Open the sidebar to drag modules into the canvas.') }}'"
                            ></span>
                        </div>
                    </div>

                    <div class="relative flex-1 bg-slate-100 p-3">
                        <div class="relative h-[72vh] min-h-135 rounded-xl border border-gray-300 bg-white shadow-inner">
                            {{-- Carousel-like page navigation arrows. --}}
                            <button
                                type="button"
                                class="absolute left-0 top-1/2 z-40 opacity-40 hover:opacity-100 -translate-y-1/2 rounded-r-full border border-gray-300 bg-white/95 p-1 py-4 text-gray-700 shadow-sm hover:bg-white"
                                x-show="pages.length > 1"
                                x-cloak
                                @click="previousPage()"
                                title="{{ __('Previous page') }}"
                            >
                                <x-icon name="arrow" class="h-4 w-4 rotate-180" />
                            </button>
                            <button
                                type="button"
                                class="absolute right-0 top-1/2 z-40 opacity-40 hover:opacity-100 -translate-y-1/2 rounded-l-full border border-gray-300 bg-white/95 p-1 py-4 text-gray-700 shadow-sm hover:bg-white"
                                x-show="pages.length > 1"
                                x-cloak
                                @click="nextPage()"
                                title="{{ __('Next page') }}"
                            >
                                <x-icon name="arrow" class="h-4 w-4" />
                            </button>

                            <div
                                x-ref="grid"
                                class="grid h-full w-full overflow-hidden rounded-xl"
                                :style="gridStyle()"
                            >
                                {{-- Background editable cells accept module drops and placement moves. --}}
                                <template x-for="cell in gridCells()" :key="'cell-' + cell.row + '-' + cell.column">
                                    <div
                                        class="relative z-0 border border-dashed border-slate-200 bg-white/70 transition hover:bg-sky-50/80"
                                        :style="cellStyle(cell.row, cell.column)"
                                        @dragover.prevent
                                        @drop.prevent="dropOnCell(cell.row, cell.column)"
                                    >
                                        <span class="pointer-events-none absolute left-1 top-1 text-[9px] text-slate-300" x-text="cell.row + ',' + cell.column"></span>
                                    </div>
                                </template>

                                {{-- Foreground module placements rendered with quick span controls. --}}
                                <template x-for="placement in activePage().placements" :key="'placement-' + placement.id">
                                    <article
                                        draggable="true"
                                        @dragstart="startPlacementDrag(placement, $event)"
                                        class="group relative z-20 overflow-hidden rounded-md border bg-white shadow-sm transition"
                                        :class="selectedPlacementId === placement.id ? 'border-[#077CB6] ring-2 ring-[#5EB4EE]/50' : 'border-gray-300 hover:border-[#5EB4EE]'"
                                        :style="placementStyle(placement)"
                                        @click="selectedPlacementId = placement.id"
                                    >
                                        <div class="profile-module-slot h-full w-full text-[11px]">
                                            <div x-html="placement.rendered_html || ''"></div>
                                        </div>

                                        <div class="pointer-events-none absolute left-1 top-1 rounded bg-white/85 px-1.5 py-0.5 text-[10px] font-semibold text-gray-700 shadow-sm">
                                            <span x-text="placement.module_name"></span>
                                        </div>

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

                            {{-- Row/column boundary handles for direct percentage resizing. --}}
                            <template x-for="(position, boundaryIndex) in columnBoundaries()" :key="'col-handle-' + boundaryIndex">
                                <button
                                    type="button"
                                    class="absolute top-0 z-30 h-full w-2 -translate-x-1/2 cursor-col-resize bg-transparent hover:bg-sky-200/40"
                                    :style="'left:' + position + '%'"
                                    @mousedown.prevent="startResize('column', boundaryIndex, $event)"
                                    title="{{ __('Resize columns') }}"
                                ></button>
                            </template>

                            <template x-for="(position, boundaryIndex) in rowBoundaries()" :key="'row-handle-' + boundaryIndex">
                                <button
                                    type="button"
                                    class="absolute left-0 z-30 w-full h-2 -translate-y-1/2 cursor-row-resize bg-transparent hover:bg-sky-200/40"
                                    :style="'top:' + position + '%'"
                                    @mousedown.prevent="startResize('row', boundaryIndex, $event)"
                                    title="{{ __('Resize rows') }}"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!activePage()">
                <div class="grid flex-1 place-items-center text-sm text-gray-500">
                    {{ __('No pages available. Create a page from the sidebar.') }}
                </div>
            </template>
        </div>
    </div>
</div>
