{{--
    Guided module settings editor.
    Schema rows define rules, and the Data tab reuses those rows to generate matching inputs.
--}}
<x-services.modules.settings-accordion
    :title="__('Module Settings')"
    :description="__('Define the rules (Schema) and the default values (Data) for your module.')"
    :expanded="false"
>
    <div
        class="space-y-4"
        x-effect="moduleSettingsTab; refreshCodeEditors()"
        @input.debounce.50ms="notifyGuidedSettingsChanged()"
        @change="notifyGuidedSettingsChanged()"
    >
        {{-- Shared Schema/Data tab switcher for guided module settings. --}}
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

        {{-- Schema builder tab for defining field keys, types, and defaults. --}}
        <div x-show="moduleSettingsTab === 'schema'" x-cloak class="space-y-4">
            
            {{-- Section heading and entry point for creating new schema rows. --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ __('Schema Builder') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Add fields that describe the shape, types, and default values your module expects.') }}</p>
                </div>

                <button
                    type="button"
                    @click="addSchemaField()"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#077CB6] focus:ring-offset-2"
                >
                    {{ __('Add Field') }}
                </button>
            </div>

            {{-- Accordion list of schema fields with per-type configuration controls. --}}
            <div class="space-y-4">
                <template x-for="(field, index) in schemaFields" :key="field.id">
                    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <button
                            type="button"
                            @click="field.expanded = !field.expanded"
                            class="flex w-full items-start justify-between gap-4 px-4 py-4 text-left transition hover:bg-gray-50"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-900" x-text="field.key || '{{ __('New field') }}'"></p>
                                    <span class="rounded-md bg-gray-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-widest text-gray-500" x-text="field.type"></span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">{{ __('Configure how this field is normalized before the module renders.') }}</p>
                            </div>

                            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 transition" :class="field.expanded ? 'rotate-180 text-[#077CB6]' : ''">
                                <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" aria-hidden="true">
                                    <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>

                        <div x-show="field.expanded" x-transition.opacity.duration.150ms x-cloak class="border-t border-gray-200 px-4 py-4">
                            <div class="flex flex-wrap items-center justify-end gap-3">
                                <button
                                    type="button"
                                    @click="removeSchemaField(index)"
                                    class="inline-flex items-center rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-red-600 transition hover:bg-red-50"
                                >
                                    {{ __('Remove') }}
                                </button>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,0.9fr)]">
                                <div>
                                    <x-input-label :value="__('Key Name')" />
                                    <input
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                        x-model.trim="field.key"
                                        placeholder="{{ __('title') }}"
                                    >
                                    <p class="mt-2 text-sm text-gray-500">{{ __('Referenced in templates as') }} <code>@{{ config.field_key }}</code>.</p>
                                </div>

                                <div>
                                    <x-input-label :value="__('Type')" />
                                    <select
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                        x-model="field.type"
                                        @change="setSchemaFieldType(index, field.type)"
                                    >
                                        <template x-for="typeOption in schemaFieldTypes" :key="typeOption.value">
                                            <option :value="typeOption.value" x-text="typeOption.label"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
                                <div class="xl:col-span-2" x-show="field.type === 'string'" x-cloak>
                                    <x-input-label :value="__('Default Value')" />
                                    <input
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                        x-model="field.defaultValue"
                                        placeholder="{{ __('My Module') }}"
                                    >
                                </div>

                                <div class="xl:col-span-2" x-show="field.type === 'integer'" x-cloak>
                                    <x-input-label :value="__('Default Value')" />
                                    <input
                                        type="number"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                        x-model="field.defaultValue"
                                    >
                                </div>

                                <div x-show="field.type === 'boolean'" x-cloak>
                                    <x-input-label :value="__('Default Value')" />
                                    <label class="mt-1 inline-flex items-center gap-3 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm">
                                        <span class="font-medium">{{ __('Enabled') }}</span>
                                        <span class="relative inline-flex h-6 w-11 items-center">
                                            <input type="checkbox" class="peer sr-only" x-model="field.defaultValue">
                                            <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#077CB6] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                                        </span>
                                    </label>
                                </div>

                                <div class="xl:col-span-2" x-show="field.type === 'object' || field.type === 'array'" x-cloak>
                                    <x-input-label :value="__('Default JSON')" />
                                    <textarea
                                        rows="8"
                                        data-module-code-editor
                                        data-editor-language="json"
                                        class="mt-2 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                        x-model="field.defaultValue"
                                    ></textarea>
                                    <p class="mt-2 text-sm text-gray-500">{{ __('Use valid JSON matching the selected type.') }}</p>
                                </div>

                                <div x-show="field.type === 'integer'" x-cloak>
                                    <x-input-label :value="__('Minimum')" />
                                    <input
                                        type="number"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                        x-model="field.min"
                                        placeholder="{{ __('Optional') }}"
                                    >
                                </div>

                                <div x-show="field.type === 'integer'" x-cloak>
                                    <x-input-label :value="__('Maximum')" />
                                    <input
                                        type="number"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                        x-model="field.max"
                                        placeholder="{{ __('Optional') }}"
                                    >
                                </div>
                            </div>
                        </div>
                    </section>
                </template>
            </div>

            {{-- Empty state shown before any schema fields exist. --}}
            <template x-if="schemaFields.length === 0">
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-6 text-sm text-gray-500">
                    {{ __('No schema fields yet. Add one to start generating module data inputs.') }}
                </div>
            </template>
        </div>

        {{-- Data tab generated from the current schema so values stay type-aligned. --}}
        <div x-show="moduleSettingsTab === 'data'" x-cloak class="space-y-4">
            <div>
                <p class="text-sm font-semibold text-gray-900">{{ __('Module Data') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('These defaults are generated from your schema so the saved values stay aligned with the declared types.') }}</p>
            </div>

            {{-- Empty state for the generated data form when no schema exists yet. --}}
            <template x-if="schemaFields.length === 0">
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-6 text-sm text-gray-500">
                    {{ __('Add schema fields first. The matching module data form will appear here automatically.') }}
                </div>
            </template>

            {{-- Auto-generated value editors for each schema field. --}}
            <div class="space-y-4">
                <template x-for="field in schemaFields" :key="field.id + '-data'">
                    <section class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900" x-text="field.key || '{{ __('Untitled field') }}'"></p>
                            <p class="mt-1 text-sm text-gray-500">
                                <span>{{ __('Type:') }}</span>
                                <span class="font-medium" x-text="field.type"></span>
                            </p>
                        </div>

                        <div class="mt-4" x-show="field.type === 'string'" x-cloak>
                            <x-input-label :value="__('Value')" />
                            <input
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                x-model="field.value"
                            >
                        </div>

                        <div class="mt-4" x-show="field.type === 'integer'" x-cloak>
                            <x-input-label :value="__('Value')" />
                            <input
                                type="number"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                x-model="field.value"
                                :min="field.min !== '' ? field.min : null"
                                :max="field.max !== '' ? field.max : null"
                            >
                        </div>

                        <div class="mt-4" x-show="field.type === 'boolean'" x-cloak>
                            <x-input-label :value="__('Value')" />
                            <label class="mt-1 inline-flex items-center gap-3 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm">
                                <span class="font-medium">{{ __('Enabled') }}</span>
                                <span class="relative inline-flex h-6 w-11 items-center">
                                    <input type="checkbox" class="peer sr-only" x-model="field.value">
                                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#077CB6] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                                </span>
                            </label>
                        </div>

                        <div class="mt-4" x-show="field.type === 'object' || field.type === 'array'" x-cloak>
                            <x-input-label :value="__('Value JSON')" />
                            <textarea
                                rows="8"
                                data-module-code-editor
                                data-editor-language="json"
                                class="mt-2 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                                x-model="field.value"
                            ></textarea>
                            <p class="mt-2 text-sm text-gray-500">{{ __('Use valid JSON that matches the selected schema type.') }}</p>
                        </div>
                    </section>
                </template>
            </div>
        </div>

        {{-- Collapsed snapshots of the generated JSON payloads for quick inspection. --}}
        <div class="space-y-4">
            <x-services.modules.settings-json-preview
                :title="__('View Generated Data Schema JSON')"
                :description="__('Preview the normalized data_schema payload that will be submitted.')"
                preview="submittedDataSchema()"
            />

            <x-services.modules.settings-json-preview
                :title="__('View Generated Module Data JSON')"
                :description="__('Preview the normalized module_data payload generated from the current schema and values.')"
                preview="submittedModuleData()"
            />
        </div>
    </div>
</x-services.modules.settings-accordion>
