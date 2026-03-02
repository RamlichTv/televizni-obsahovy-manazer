{{--
    Sidebar pane of the module editor.
    Right side holds code editors and the settings UI that generates JSON payloads.
--}}

{{-- Secondary workspace: code and settings side panel --}}
<aside
    class="w-full shrink-0 border-t border-gray-200 bg-white transition-all duration-300 lg:w-160 lg:border-t-0 lg:border-l"
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-4"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-4"
>
    {{-- Sidebar tab switches --}}
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
                @click="sidebarTab = 'json'"
                :class="sidebarTab === 'json' ? 'bg-[#077CB6] text-white border-[#077CB6]' : 'bg-white text-gray-700 border-gray-300'"
                class="rounded-md border px-3 py-1.5 text-xs font-semibold transition"
            >
                {{ __('Settings') }}
            </button>
        </div>
    </div>

    <div class="max-h-[78vh] overflow-y-auto p-4 space-y-5">
        
        {{-- Code editors and preset picker --}}
        <div x-show="sidebarTab === 'code'" x-cloak class="space-y-4">
            @if (count($resolvedTemplatePresets) > 0)
                <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <x-input-label :value="__('Template Preset')" />
                    <div class="mt-1 flex items-center gap-2">
                        <select
                            x-model="selectedTemplatePreset"
                            class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                        >
                            <option value="">{{ __('Choose preset') }}</option>
                            @foreach ($resolvedTemplatePresets as $presetKey => $preset)
                                <option value="{{ $presetKey }}">{{ $preset['label'] }}</option>
                            @endforeach
                        </select>

                        <button
                            type="button"
                            @click="applyTemplatePreset()"
                            :disabled="selectedTemplatePreset === ''"
                            class="shrink-0 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ __('Apply') }}
                        </button>
                    </div>
                    <p
                        class="text-xs text-gray-500"
                        x-show="selectedTemplatePreset && templatePresets[selectedTemplatePreset]"
                        x-text="templatePresets[selectedTemplatePreset] && templatePresets[selectedTemplatePreset].description ? templatePresets[selectedTemplatePreset].description : ''"
                    ></p>
                </div>
            @endif

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
                @if ($resolvedCanEditScript)
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
                <x-input-label for="template_html" :value="__('Template HTML')" :required="true"/>
                <textarea
                    id="template_html"
                    x-ref="templateHtmlField"
                    name="template_html"
                    rows="24"
                    data-module-code-editor
                    data-editor-language="html"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                >{{ $resolvedTemplateHtml }}</textarea>
                <p class="mt-1 text-xs text-gray-500">
                    {{ __('Mustache placeholders:') }}
                    @php $feach = "{{#each item}}"; @endphp
                    <code>@{{ module.name }}</code>,
                    <code>@{{ config.title }}</code>,
                    <code>{{ $feach }}...@{{/each}}</code>.
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('template_html')" />
            </div>

            <div x-show="codeTab === 'css'" x-cloak>
                <x-input-label for="template_css" :value="__('Template CSS')" />
                <textarea
                    id="template_css"
                    x-ref="templateCssField"
                    name="template_css"
                    rows="24"
                    data-module-code-editor
                    data-editor-language="css"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                >{{ $resolvedTemplateCss }}</textarea>
                <p class="mt-1 text-xs text-gray-500">
                    {{ __('Use this for pseudo-elements or advanced styles that are hard to express with utilities alone.') }}
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('template_css')" />
            </div>

            @if ($resolvedCanEditScript)
                <div x-show="codeTab === 'js'" x-cloak>
                    <x-input-label for="template_js" :value="__('Template JS (Admin Only)')" />
                    <textarea
                        id="template_js"
                        x-ref="templateJsField"
                        name="template_js"
                        rows="24"
                        data-module-code-editor
                        data-editor-language="javascript"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                    >{{ $resolvedTemplateJs }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('Keep scripts scoped to the module output and avoid global side effects.') }}
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('template_js')" />
                </div>
            @else
                <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    {{ __('JavaScript editing is restricted to administrators.') }}
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('template_js')" />
            @endif
        </div>

        {{-- Friendly settings UI that generates hidden JSON payloads --}}
        <div x-show="sidebarTab === 'json'" x-cloak class="space-y-4">
            
            {{-- Hidden JSON payloads submitted to server-side requests --}}
            <input
                type="hidden"
                name="config_schema"
                value="{{ $schemaJson }}"
                x-bind:value="buildConfigSchema()"
            >
            <input
                type="hidden"
                name="default_config"
                value="{{ $defaultConfigJson }}"
                x-bind:value="buildDefaultConfig()"
            >
            <input
                type="hidden"
                name="data_source"
                value="{{ $dataSourceJson }}"
                x-bind:value="buildDataSource()"
            >

            {{-- Human-friendly settings controls --}}
            <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div>
                    <x-input-label :value="__('Module Title')" />
                    <x-text-input
                        name="settings_title"
                        type="text"
                        class="mt-1 block w-full"
                        x-model="createSettings.title"
                        placeholder="{{ __('Featured Content') }}"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('Displayed as `config.title` in your template.') }}
                        <code>@{{ config.title }}</code>
                    </p>
                </div>

                <div>
                    <x-input-label :value="__('Data Provider')" />
                    <select
                        name="settings_provider"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                        x-model="createSettings.provider"
                    >
                        @foreach ($providers as $key => $label)
                            <option value="{{ $key }}">{{ $label }} ({{ $key }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('Selects where module content comes from.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <x-input-label :value="__('Content Type Filter')" />
                        <select
                            name="settings_type_name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                            x-model="createSettings.typeName"
                        >
                            <option value="">{{ __('Any type') }}</option>
                            @foreach ($contentTypeOptions as $typeName)
                                <option value="{{ $typeName }}">{{ $typeName }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('Optional. Restricts results to one content type.') }}
                        </p>
                    </div>

                    <div>
                        <x-input-label :value="__('Status Filter')" />
                        <select
                            name="settings_status_name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                            x-model="createSettings.statusName"
                        >
                            <option value="">{{ __('Any status') }}</option>
                            @foreach ($contentStatusOptions as $statusName)
                                <option value="{{ $statusName }}">{{ $statusName }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('Recommended: Published, so unpublished items are not displayed.') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <x-input-label :value="__('Sort Field')" />
                        <select
                            name="settings_order_by"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                            x-model="createSettings.orderBy"
                        >
                            <option value="published_at">{{ __('Published Date') }}</option>
                            <option value="created_at">{{ __('Created Date') }}</option>
                            <option value="priority">{{ __('Priority') }}</option>
                            <option value="name">{{ __('Name') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('Defines which field controls result order.') }}
                        </p>
                    </div>

                    <div>
                        <x-input-label :value="__('Sort Direction')" />
                        <select
                            name="settings_order_direction"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                            x-model="createSettings.orderDirection"
                        >
                            <option value="desc">{{ __('Descending (newest first)') }}</option>
                            <option value="asc">{{ __('Ascending (oldest first)') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('Controls whether highest/newest values appear first.') }}
                        </p>
                    </div>

                    <div>
                        <x-input-label :value="__('Item Limit')" />
                        <x-text-input
                            name="settings_limit"
                            type="number"
                            min="1"
                            max="30"
                            class="mt-1 block w-full"
                            x-model.number="createSettings.limit"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('Maximum number of records loaded into `items`.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Live serialization preview of the generated data_source payload --}}
            <div class="rounded-lg border border-gray-200 bg-white p-3">
                <p class="text-xs font-semibold text-gray-700">{{ __('Generated Settings Preview') }}</p>
                <pre class="mt-2 overflow-x-auto rounded-md bg-gray-50 p-2 text-xs text-gray-600" x-text="buildDataSource()"></pre>
            </div>

            @if ($isEdit)
                {{-- Snapshot of currently persisted JSON values --}}
                <details class="rounded-lg border border-gray-200 bg-white p-3">
                    <summary class="cursor-pointer text-xs font-semibold text-gray-700">{{ __('Current Saved JSON') }}</summary>
                    <div class="mt-3 space-y-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ __('Schema') }}</p>
                            <pre class="mt-1 overflow-x-auto rounded-md bg-gray-50 p-2 text-xs text-gray-600">{{ $schemaJson }}</pre>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ __('Defaults') }}</p>
                            <pre class="mt-1 overflow-x-auto rounded-md bg-gray-50 p-2 text-xs text-gray-600">{{ $defaultConfigJson }}</pre>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ __('Data Source') }}</p>
                            <pre class="mt-1 overflow-x-auto rounded-md bg-gray-50 p-2 text-xs text-gray-600">{{ $dataSourceJson }}</pre>
                        </div>
                    </div>
                </details>
            @endif

            <x-input-error class="mt-2" :messages="$errors->get('config_schema')" />
            <x-input-error class="mt-2" :messages="$errors->get('default_config')" />
            <x-input-error class="mt-2" :messages="$errors->get('data_source')" />
            <x-input-error class="mt-2" :messages="$errors->get('data_source.provider')" />
        </div>
    </div>
</aside>
