{{--
    Raw JSON accordions for advanced module editing.
    Each payload stays separate so authors can work on one contract at a time.
--}}
<div class="space-y-4">
    <x-services.modules.settings-accordion
        :title="__('Module Settings')"
        :description="__('Edit the raw data_schema and module_data payloads that shape config.* in the template.')"
        :expanded="false"
    >
        <div class="space-y-4" x-effect="moduleSettingsTab; refreshCodeEditors()">
            <div class="inline-flex items-center rounded-md border border-gray-300 bg-gray-50 p-1">
                <button
                    type="button"
                    @click="setModuleSettingsTab('schema')"
                    :class="moduleSettingsTabClass('schema')"
                    class="rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest transition"
                >
                    {{ __('Schema') }}
                </button>
                <button
                    type="button"
                    @click="setModuleSettingsTab('data')"
                    :class="moduleSettingsTabClass('data')"
                    class="rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest transition"
                >
                    {{ __('Data') }}
                </button>
            </div>

            <div x-show="moduleSettingsTab === 'schema'" x-cloak>
                <p class="mb-3 text-sm text-gray-500">{{ __('Edit the type rules, defaults, and constraints exactly as JSON.') }}</p>
                <textarea
                    x-ref="dataSchemaRawField"
                    x-on:module-code-editor:change="handleRawEditorChange('dataSchema', $event.detail.value, $event.detail.origin || 'raw')"
                    @input.debounce.75ms="handleRawEditorChange('dataSchema', $event.target.value, 'raw')"
                    @change="handleRawEditorChange('dataSchema', $event.target.value, 'raw')"
                    name="data_schema_raw"
                    rows="12"
                    data-module-code-editor
                    data-editor-language="json"
                    data-editor-sync-native="0"
                    class="block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                >{{ $rawJson['dataSchema'] }}</textarea>
            </div>

            <div x-show="moduleSettingsTab === 'data'" x-cloak>
                <p class="mb-3 text-sm text-gray-500">{{ __('Edit the actual default values the module will render with.') }}</p>
                <textarea
                    x-ref="moduleDataRawField"
                    x-on:module-code-editor:change="handleRawEditorChange('moduleData', $event.detail.value, $event.detail.origin || 'raw')"
                    @input.debounce.75ms="handleRawEditorChange('moduleData', $event.target.value, 'raw')"
                    @change="handleRawEditorChange('moduleData', $event.target.value, 'raw')"
                    name="module_data_raw"
                    rows="12"
                    data-module-code-editor
                    data-editor-language="json"
                    data-editor-sync-native="0"
                    class="block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                >{{ $rawJson['moduleData'] }}</textarea>
            </div>
        </div>
    </x-services.modules.settings-accordion>

    <x-services.modules.settings-accordion
        :title="__('Data Provider')"
        :description="__('Edit the full data_source payload manually.')"
        :expanded="false"
    >
        <textarea
            x-ref="dataSourceRawField"
            x-on:module-code-editor:change="handleRawEditorChange('dataSource', $event.detail.value, $event.detail.origin || 'raw')"
            @input.debounce.75ms="handleRawEditorChange('dataSource', $event.target.value, 'raw')"
            @change="handleRawEditorChange('dataSource', $event.target.value, 'raw')"
            name="data_source_raw"
            rows="12"
            data-module-code-editor
            data-editor-language="json"
            data-editor-sync-native="0"
            class="block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
        >{{ $rawJson['dataSource'] }}</textarea>
    </x-services.modules.settings-accordion>
</div>
