{{--
    Read-only inspector pane for the module show shell.
    It mirrors the editor tabs while exposing saved source, settings, and file metadata.
--}}

@props([
    'module',
    'templateHtml' => '',
    'templateCss' => '',
    'templateJs' => '',
    'canEditScript' => false,
])

@php
    $schemaArray = is_array($module->data_schema) ? $module->data_schema : [];
    $moduleDataArray = is_array($module->module_data) ? $module->module_data : [];
    $dataSourceArray = is_array($module->data_source) ? $module->data_source : [];

    $settingsModuleTitle = (string) data_get($moduleDataArray, 'title', data_get($schemaArray, 'title.default', ''));
    $settingsProvider = (string) data_get($dataSourceArray, 'provider', '');
    $settingsTypeNames = collect(data_get($dataSourceArray, 'filters.type_names', []))
        ->map(fn ($name): string => is_string($name) ? trim($name) : '')
        ->filter()
        ->unique()
        ->values();
    $settingsStatusNames = collect(data_get($dataSourceArray, 'filters.status_names', []))
        ->map(fn ($name): string => is_string($name) ? trim($name) : '')
        ->filter()
        ->unique()
        ->values();
    $settingsTagIds = collect(data_get($dataSourceArray, 'filters.tag_ids', []))
        ->map(fn ($id): int => (int) $id)
        ->filter(fn (int $id): bool => $id > 0)
        ->values();

    if ($settingsTypeNames->isEmpty()) {
        $legacyTypeName = trim((string) data_get($dataSourceArray, 'filters.type_name', ''));
        if ($legacyTypeName !== '') {
            $settingsTypeNames = collect([$legacyTypeName]);
        }
    }

    if ($settingsStatusNames->isEmpty()) {
        $legacyStatusName = trim((string) data_get($dataSourceArray, 'filters.status_name', ''));
        if ($legacyStatusName !== '') {
            $settingsStatusNames = collect([$legacyStatusName]);
        }
    }

    $settingsTypeLabel = $settingsTypeNames->isNotEmpty() ? $settingsTypeNames->implode(', ') : __('Any type');
    $settingsStatusLabel = $settingsStatusNames->isNotEmpty()
        ? $settingsStatusNames->map(fn (string $name): string => (string) __($name))->implode(', ')
        : __('Published (default)');
    $settingsTagLabel = $settingsTagIds->isNotEmpty() ? $settingsTagIds->implode(', ') : __('No tag filter');
    $settingsOrderBy = (string) data_get($dataSourceArray, 'order_by', 'published_at');
    $settingsOrderDirection = strtolower((string) data_get($dataSourceArray, 'order_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
    $settingsLimit = max(1, (int) data_get($dataSourceArray, 'limit', 8));

    $schemaJson = json_encode($schemaArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    $moduleDataJson = json_encode($moduleDataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    $dataSourceJson = json_encode($dataSourceArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
@endphp

<aside class="min-h-0 min-w-0 shrink-0 border-b border-gray-200 bg-white lg:border-b-0 lg:border-r" :style="editorPaneStyle()">
    <div class="flex h-full min-h-0 flex-col overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 sm:px-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Inspector') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Browse saved source, effective settings, and revision metadata.') }}</p>
                </div>

                <div class="inline-flex items-center rounded-md border border-gray-300 bg-white p-1 shadow-sm">
                    <button type="button" @click="setSidebarTab('code')" :class="sidebarTabButtonClass('code')" class="rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest transition">{{ __('Code') }}</button>
                    <button type="button" @click="setSidebarTab('settings')" :class="sidebarTabButtonClass('settings')" class="rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest transition">{{ __('Settings') }}</button>
                    <button type="button" @click="setSidebarTab('meta')" :class="sidebarTabButtonClass('meta')" class="rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest transition">{{ __('Meta') }}</button>
                </div>
            </div>
        </div>

        <div x-show="sidebarTab === 'code'" x-cloak class="flex min-h-0 flex-1 flex-col overflow-hidden">
            <div class="border-b border-gray-200 bg-white px-4 py-3 sm:px-5">
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" @click="setActiveTab('html')" :class="editorTabClass('html')" class="border-b-2 px-1 pb-2 text-sm font-mono transition">HTML</button>
                    <button type="button" @click="setActiveTab('css')" :class="editorTabClass('css')" class="border-b-2 px-1 pb-2 text-sm font-mono transition">CSS</button>
                    @if ($canEditScript)
                        <button type="button" @click="setActiveTab('js')" :class="editorTabClass('js')" class="border-b-2 px-1 pb-2 text-sm font-mono transition">JS</button>
                    @endif
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-hidden">
                <div x-show="activeTab === 'html'" x-cloak class="flex h-full min-h-0 flex-col overflow-hidden px-4 py-4 sm:px-5">
                    <div class="shrink-0">
                        <x-input-label :value="__('Template HTML')" />
                        <p class="mt-1 text-sm text-gray-500">{{ __('Saved module markup with Mustache placeholders intact.') }}</p>
                    </div>
                    <div class="min-h-0 flex-1 pt-4">
                        <textarea rows="24" readonly data-module-code-editor data-editor-language="html" data-editor-readonly="1" data-editor-fill="1" class="block h-full w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs">{{ $templateHtml }}</textarea>
                    </div>
                </div>

                <div x-show="activeTab === 'css'" x-cloak class="flex h-full min-h-0 flex-col overflow-hidden px-4 py-4 sm:px-5">
                    <div class="shrink-0">
                        <x-input-label :value="__('Template CSS')" />
                        <p class="mt-1 text-sm text-gray-500">{{ __('Saved stylesheet after the current revision was written to storage.') }}</p>
                    </div>
                    <div class="min-h-0 flex-1 pt-4">
                        <textarea rows="24" readonly data-module-code-editor data-editor-language="css" data-editor-readonly="1" data-editor-fill="1" class="block h-full w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs">{{ $templateCss }}</textarea>
                    </div>
                </div>

                @if ($canEditScript)
                    <div x-show="activeTab === 'js'" x-cloak class="flex h-full min-h-0 flex-col overflow-hidden px-4 py-4 sm:px-5">
                        <div class="shrink-0">
                            <x-input-label :value="__('Template JS')" />
                            <p class="mt-1 text-sm text-gray-500">{{ __('Saved script for this revision. Runtime remains isolated from the shell.') }}</p>
                        </div>
                        <div class="min-h-0 flex-1 pt-4">
                            <textarea rows="24" readonly data-module-code-editor data-editor-language="javascript" data-editor-readonly="1" data-editor-fill="1" class="block h-full w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs">{{ $templateJs }}</textarea>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div x-show="sidebarTab === 'settings'" x-cloak class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 shadow-sm">
                    <p class="text-sm font-semibold text-gray-900">{{ __('Module settings overview') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('These saved payloads drive config.* values and data-source filters for the current revision.') }}</p>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Module Title') }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ $settingsModuleTitle !== '' ? $settingsModuleTitle : __('Not set') }}</p>
                        <p class="mt-1 text-sm text-gray-500"><code>@{{ config.title }}</code></p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Data Provider') }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ $settingsProvider !== '' ? $settingsProvider : __('Not set') }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Controls which backend dataset populates the items collection.') }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Type Filter') }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ $settingsTypeLabel }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Maps to data_source.filters.type_name / type_names.') }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Status Filter') }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ $settingsStatusLabel }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Maps to data_source.filters.status_name / status_names.') }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Tag Filter') }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ $settingsTagLabel }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Maps to data_source.filters.tag_ids.') }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Sort / Limit') }}</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ $settingsOrderBy }} / {{ strtoupper($settingsOrderDirection) }} / {{ $settingsLimit }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Maps to data_source.order_by, data_source.order_direction, and data_source.limit.') }}</p>
                    </div>
                </div>

                <details class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <summary class="cursor-pointer text-sm font-semibold text-gray-900">{{ __('Raw JSON Snapshots') }}</summary>
                    <div class="mt-4 space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Data Schema') }}</p>
                            <textarea rows="8" readonly data-module-code-editor data-editor-language="json" data-editor-readonly="1" class="mt-2 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs">{{ $schemaJson }}</textarea>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Module Data') }}</p>
                            <textarea rows="8" readonly data-module-code-editor data-editor-language="json" data-editor-readonly="1" class="mt-2 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs">{{ $moduleDataJson }}</textarea>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Data Source') }}</p>
                            <textarea rows="10" readonly data-module-code-editor data-editor-language="json" data-editor-readonly="1" class="mt-2 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs">{{ $dataSourceJson }}</textarea>
                        </div>
                    </div>
                </details>
            </div>
        </div>

        <div x-show="sidebarTab === 'meta'" x-cloak class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Revision') }}</p>
                    <p class="mt-2 text-sm font-semibold text-gray-900">{{ $module->revision }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Template Path') }}</p>
                    <p class="mt-2 break-all text-sm text-gray-700">{{ $module->template_path ?: __('Not generated') }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Style Path') }}</p>
                    <p class="mt-2 break-all text-sm text-gray-700">{{ $module->style_path ?: __('Not generated') }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Script Path') }}</p>
                    <p class="mt-2 break-all text-sm text-gray-700">{{ $module->script_path ?: __('Not generated') }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Created By') }}</p>
                    <p class="mt-2 text-sm font-semibold text-gray-900">{{ $module->createdBy?->name ?? __('System') }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Category Flags') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-widest">
                        @if ($module->moduleCategory?->is_widget)
                            <span class="rounded-md border border-[#5EB4EE] bg-[#e9faff] px-3 py-2 text-[#077CB6]">{{ __('Widget') }}</span>
                        @endif
                        @if ($module->moduleCategory?->is_screensaver)
                            <span class="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-indigo-700">{{ __('Screensaver') }}</span>
                        @endif
                        @if ($module->moduleCategory?->allow_embedded)
                            <span class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-green-700">{{ __('Embedded') }}</span>
                        @endif
                        @if (! $module->moduleCategory?->is_widget && ! $module->moduleCategory?->is_screensaver && ! $module->moduleCategory?->allow_embedded)
                            <span class="rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-500">{{ __('No category flags') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
