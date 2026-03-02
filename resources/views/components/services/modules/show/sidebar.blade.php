{{--
    Read-only inspection sidebar for module show view.
    Keeps code, effective settings, and file metadata in collapsible tabs.
--}}

@props([
    'module',
    'templateHtml' => '',
    'templateCss' => '',
    'templateJs' => '',
    'canEditScript' => false,
])

@php
    $schemaArray = is_array($module->config_schema) ? $module->config_schema : [];
    $defaultConfigArray = is_array($module->default_config) ? $module->default_config : [];
    $dataSourceArray = is_array($module->data_source) ? $module->data_source : [];

    $settingsModuleTitle = (string) data_get($defaultConfigArray, 'title', data_get($schemaArray, 'title.default', ''));
    $settingsProvider = (string) data_get($dataSourceArray, 'provider', '');
    $settingsTypeName = (string) data_get($dataSourceArray, 'filters.type_name', '');
    $settingsStatusName = (string) data_get($dataSourceArray, 'filters.status_name', '');
    $settingsOrderBy = (string) data_get($dataSourceArray, 'order_by', 'published_at');
    $settingsOrderDirection = strtolower((string) data_get($dataSourceArray, 'order_direction', 'desc')) === 'asc'
        ? 'asc'
        : 'desc';
    $settingsLimit = max(1, (int) data_get($dataSourceArray, 'limit', 8));

    $schemaJson = json_encode($schemaArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    $defaultConfigJson = json_encode($defaultConfigArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    $dataSourceJson = json_encode($dataSourceArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
@endphp

<aside
    class="w-full shrink-0 border-t border-gray-200 bg-white transition-all duration-300 lg:w-152 lg:border-t-0 lg:border-l"
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-4"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-4"
>
    <div class="border-b border-gray-200 px-4 py-3">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                @click="sidebarTab = 'code'"
                :class="sidebarTab === 'code' ? 'bg-[#077CB6] text-white border-[#077CB6]' : 'bg-white text-gray-700 border-gray-300'"
                class="rounded-md border px-3 py-1.5 text-xs font-semibold transition"
            >
                {{ __('Code') }}
            </button>
            <button
                type="button"
                @click="sidebarTab = 'settings'"
                :class="sidebarTab === 'settings' ? 'bg-[#077CB6] text-white border-[#077CB6]' : 'bg-white text-gray-700 border-gray-300'"
                class="rounded-md border px-3 py-1.5 text-xs font-semibold transition"
            >
                {{ __('Settings') }}
            </button>
            <button
                type="button"
                @click="sidebarTab = 'meta'"
                :class="sidebarTab === 'meta' ? 'bg-[#077CB6] text-white border-[#077CB6]' : 'bg-white text-gray-700 border-gray-300'"
                class="rounded-md border px-3 py-1.5 text-xs font-semibold transition"
            >
                {{ __('Meta') }}
            </button>
        </div>
    </div>

    <div class="max-h-[78vh] overflow-y-auto p-4 space-y-5">
        <div x-show="sidebarTab === 'code'" x-cloak class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="codeTab = 'html'"
                    :class="codeTab === 'html' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300'"
                    class="rounded-md border px-3 py-1.5 text-xs font-semibold transition"
                >
                    HTML
                </button>
                <button
                    type="button"
                    @click="codeTab = 'css'"
                    :class="codeTab === 'css' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300'"
                    class="rounded-md border px-3 py-1.5 text-xs font-semibold transition"
                >
                    CSS
                </button>
                @if ($canEditScript)
                    <button
                        type="button"
                        @click="codeTab = 'js'"
                        :class="codeTab === 'js' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300'"
                        class="rounded-md border px-3 py-1.5 text-xs font-semibold transition"
                    >
                        JS
                    </button>
                @endif
            </div>

            <div x-show="codeTab === 'html'" x-cloak>
                <x-input-label :value="__('Template HTML')" />
                <textarea
                    rows="22"
                    readonly
                    data-module-code-editor
                    data-editor-language="html"
                    data-editor-readonly="1"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs"
                >{{ $templateHtml }}</textarea>
            </div>

            <div x-show="codeTab === 'css'" x-cloak>
                <x-input-label :value="__('Template CSS')" />
                <textarea
                    rows="22"
                    readonly
                    data-module-code-editor
                    data-editor-language="css"
                    data-editor-readonly="1"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs"
                >{{ $templateCss }}</textarea>
            </div>

            @if ($canEditScript)
                <div x-show="codeTab === 'js'" x-cloak>
                    <x-input-label :value="__('Template JS')" />
                    <textarea
                        rows="22"
                        readonly
                        data-module-code-editor
                        data-editor-language="javascript"
                        data-editor-readonly="1"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs"
                    >{{ $templateJs }}</textarea>
                </div>
            @endif
        </div>

        <div x-show="sidebarTab === 'settings'" x-cloak class="space-y-4">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs">
                <p class="font-semibold text-gray-700">{{ __('Template config overview') }}</p>
                <p class="mt-1 text-gray-500">{{ __('These values drive `config.*` and provider query filters used by the module renderer.') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Module Title') }}</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $settingsModuleTitle !== '' ? $settingsModuleTitle : __('Not set') }}</p>
                    <p class="mt-1 text-xs text-gray-500"><code>@{{ config.title }}</code></p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Data Provider') }}</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $settingsProvider !== '' ? $settingsProvider : __('Not set') }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Controls which backend dataset populates `items`.') }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Type Filter') }}</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $settingsTypeName !== '' ? $settingsTypeName : __('Any type') }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Maps to `data_source.filters.type_name`.') }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Status Filter') }}</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $settingsStatusName !== '' ? $settingsStatusName : __('Published (default)') }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Maps to `data_source.filters.status_name`.') }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Sort') }}</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $settingsOrderBy }} / {{ strtoupper($settingsOrderDirection) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Maps to `data_source.order_by` and `data_source.order_direction`.') }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Limit') }}</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $settingsLimit }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Maximum number of rows loaded into `items`.') }}</p>
                </div>
            </div>

            <details class="rounded-lg border border-gray-200 bg-white p-3">
                <summary class="cursor-pointer text-xs font-semibold text-gray-700">{{ __('Raw JSON (advanced)') }}</summary>
                <div class="mt-3 space-y-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ __('Schema') }}</p>
                        <textarea
                            rows="8"
                            readonly
                            data-module-code-editor
                            data-editor-language="json"
                            data-editor-readonly="1"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs"
                        >{{ $schemaJson }}</textarea>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ __('Defaults') }}</p>
                        <textarea
                            rows="8"
                            readonly
                            data-module-code-editor
                            data-editor-language="json"
                            data-editor-readonly="1"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs"
                        >{{ $defaultConfigJson }}</textarea>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ __('Data Source') }}</p>
                        <textarea
                            rows="8"
                            readonly
                            data-module-code-editor
                            data-editor-language="json"
                            data-editor-readonly="1"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs"
                        >{{ $dataSourceJson }}</textarea>
                    </div>
                </div>
            </details>
        </div>

        <div x-show="sidebarTab === 'meta'" x-cloak class="space-y-3 text-sm">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Category') }}</p>
                <p class="mt-1 font-semibold text-gray-900">{{ ucfirst($module->moduleCategory?->name ?? __('none')) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Author') }}</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $module->createdBy?->name ?? __('System') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Template Path') }}</p>
                <p class="mt-1 break-all text-xs text-gray-700">{{ $module->template_path ?: __('Not generated') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Style Path') }}</p>
                <p class="mt-1 break-all text-xs text-gray-700">{{ $module->style_path ?: __('Not generated') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Script Path') }}</p>
                <p class="mt-1 break-all text-xs text-gray-700">{{ $module->script_path ?: __('Not generated') }}</p>
            </div>
        </div>
    </div>
</aside>
