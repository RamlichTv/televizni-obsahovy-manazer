{{--
    Module editor sidebar shell.
    It owns submitted JSON payload fields and switches between the code and settings panes.
--}}

<aside class="min-h-0 min-w-0 shrink-0 border-b border-gray-200 bg-white lg:border-b-0 lg:border-r" :style="editorPaneStyle()">
    <div class="flex h-full min-h-0 flex-col overflow-hidden">
        {{-- Sidebar header and tab switcher between source code and module settings. --}}
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 sm:px-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Editor Panels') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Switch between source code and data configuration.') }}</p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button
                        type="button"
                        @click.prevent="clearCodeAndSettings()"
                        :disabled="@js((bool) $flags['embeddedAccessLocked'])"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        title="{{ __('Clear template code and module settings') }}"
                    >
                        {{ __('Clear Editor') }}
                    </button>

                    <div class="inline-flex items-center rounded-md border border-gray-300 bg-white p-1 shadow-sm">
                        <button
                            type="button"
                            @click="setSidebarTab('code')"
                            :class="sidebarTabButtonClass('code')"
                            class="rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest transition"
                        >
                            {{ __('Code') }}
                        </button>
                        <button
                            type="button"
                            @click="setSidebarTab('settings')"
                            :class="sidebarTabButtonClass('settings')"
                            class="rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest transition"
                        >
                            {{ __('Settings') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden fields mirror the authoritative JSON payload that the controller validates. --}}
        <input type="hidden" name="module_settings_mode" x-bind:value="settingsMode">
        <input type="hidden" name="data_schema" x-ref="dataSchemaInput" value="{{ $jsonPayloads['dataSchema'] }}" x-bind:value="submittedDataSchema()">
        <input type="hidden" name="module_data" x-ref="moduleDataInput" value="{{ $jsonPayloads['moduleData'] }}" x-bind:value="submittedModuleData()">
        <input type="hidden" name="data_source" x-ref="dataSourceInput" value="{{ $jsonPayloads['dataSource'] }}" x-bind:value="submittedDataSource()">

        {{-- Code pane for HTML, CSS, JS, and template preset application. --}}
        <div x-show="sidebarTab === 'code'" x-cloak class="flex min-h-0 flex-1 flex-col overflow-hidden">
            @include('services.modules.partials.form.code-pane', [
                'flags' => $flags,
                'templates' => $templates,
                'templatePresetOptions' => $templatePresetOptions,
            ])
        </div>

        {{-- Settings pane for guided/raw data source and module data authoring. --}}
        <div x-show="sidebarTab === 'settings'" x-cloak class="min-h-0 flex-1 overflow-y-auto overflow-x-visible px-4 py-4 sm:px-5">
            @include('services.modules.partials.form.settings-pane', [
                'jsonPayloads' => $jsonPayloads,
                'rawJson' => $rawJson,
                'settingsUi' => $settingsUi,
                'pickers' => $pickers,
                'providers' => $providers,
            ])
        </div>
    </div>
</aside>
