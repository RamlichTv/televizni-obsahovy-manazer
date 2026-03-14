{{--
    Full-screen module preview page.
    It shares the dedicated module editor shell, but stays read-only and preview-first.
--}}

<x-module-editor-layout title="{{ $module->name }}">
    <div class="flex h-full min-h-0 flex-col overflow-hidden" x-data="moduleEditor(@js($showShellState))">
        <header class="border-b border-gray-200 bg-white shadow-sm">
            <div class="flex min-h-16 flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3 lg:gap-4">
                    <a
                        href="{{ route('modules.index') }}"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                        aria-label="{{ __('Back to modules') }}"
                    >
                        <x-application-logo class="h-6 w-auto" :text="false" />
                    </a>

                    <a
                        href="{{ route('modules.index') }}"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                    >
                        <x-icon name="arrow" class="h-4 w-4 rotate-180" />
                        {{ __('Modules') }}
                    </a>

                    <div class="min-w-[16rem] flex-1 lg:max-w-2xl">
                        <div class="rounded-md border border-gray-300 bg-white px-4 py-3 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Module') }}</p>
                            <h1 class="mt-1 text-xl font-semibold text-gray-900">{{ $module->name }}</h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-widest">
                        @if ($module->moduleCategory)
                            <span class="rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-500 shadow-sm">
                                {{ ucfirst((string) $module->moduleCategory->name) }}
                            </span>
                        @endif

                        <span class="rounded-md border px-3 py-2 shadow-sm {{ $module->is_active ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-300 bg-white text-gray-500' }}">
                            {{ $module->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 lg:gap-3">
                    @if (count($availableRevisions) > 1)
                        <select
                            class="rounded-md border-gray-300 px-4 py-2 text-sm text-gray-700 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                            onchange="window.location.href = this.value"
                        >
                            @foreach ($availableRevisions as $revisionOption)
                                <option
                                    value="{{ route('module.show', ['module' => $module, 'revision' => $revisionOption]) }}"
                                    @selected((int) $selectedRevision === (int) $revisionOption)
                                >
                                    {{ __('Revision') }} {{ $revisionOption }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <span class="rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-500 shadow-sm">
                            {{ __('Revision') }} {{ $selectedRevision }}
                        </span>
                    @endif

                    <div class="inline-flex items-center rounded-md border border-gray-300 bg-white p-1 shadow-sm">
                        <button type="button" @click="setLayout('vertical')" :class="layoutButtonClass('vertical')" class="rounded-md px-3 py-2 text-sm transition" aria-label="{{ __('Vertical split') }}">
                            <span class="flex items-center gap-1.5">
                                <span class="h-3.5 w-3 rounded-sm border border-current"></span>
                                <span class="h-3.5 w-5 rounded-sm border border-current"></span>
                            </span>
                        </button>
                        <button type="button" @click="setLayout('horizontal')" :class="layoutButtonClass('horizontal')" class="rounded-md px-3 py-2 text-sm transition" aria-label="{{ __('Horizontal split') }}">
                            <span class="grid gap-1">
                                <span class="h-2 w-6 rounded-sm border border-current"></span>
                                <span class="h-2 w-6 rounded-sm border border-current"></span>
                            </span>
                        </button>
                        <button type="button" @click="setLayout('preview')" :class="layoutButtonClass('preview')" class="rounded-md px-3 py-2 text-sm transition" aria-label="{{ __('Preview only') }}">
                            <span class="h-3.5 w-6 rounded-sm border border-current"></span>
                        </button>
                    </div>

                    @can('update', $module)
                        <a
                            href="{{ route('module.edit', ['module' => $module, 'revision' => $selectedRevision]) }}"
                            class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                        >
                            <x-icon name="pen" class="h-4 w-4" />
                            {{ __('Edit') }}
                        </a>
                    @endcan

                    @can('delete', $module)
                        <form
                            action="{{ route('module.destroy', ['module' => $module]) }}"
                            method="POST"
                            data-confirm-title="{{ __('Delete module') }}"
                            data-confirm-message="{{ __('Are you sure you want to delete the module :name?', ['name' => $module->name]) }}"
                            data-confirm-label="{{ __('Delete') }}"
                            data-cancel-label="{{ __('Cancel') }}"
                            data-confirm-variant="danger"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-red-700 shadow-sm transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-200 focus:ring-offset-2"
                            >
                                <x-icon name="trash" class="h-4 w-4" />
                                {{ __('Delete') }}
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </header>

        <div class="flex min-h-0 flex-1 overflow-hidden" x-ref="editorWorkspace" :class="workspaceClass()">
            <x-services.modules.show.sidebar
                :module="$module"
                :template-html="$templateHtml"
                :template-css="$templateCss"
                :template-js="$templateJs"
                :can-edit-script="$canEditScript"
            />

            <div
                x-show="layout !== 'preview'"
                x-cloak
                @pointerdown.prevent="startDragging($event)"
                :class="gutterClass()"
                class="module-editor-gutter shrink-0 bg-gray-200/70 transition-colors hover:bg-[#5EB4EE]/40"
                :data-dragging="isDragging ? 'true' : 'false'"
            ></div>

            <x-services.modules.show.preview-pane :module="$module" />
        </div>
    </div>
</x-module-editor-layout>
