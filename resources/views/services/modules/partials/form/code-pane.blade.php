{{--
    Code authoring pane for module templates.
    Presets stay compact, while CodeMirror editors fill the available height per tab.
--}}

@if (count($templatePresetOptions) > 0)
    <div class="border-b border-gray-200 bg-white px-4 py-3 sm:px-5">
        <div class="flex flex-wrap items-center gap-2">
            <label for="template_preset" class="text-xs font-semibold uppercase tracking-widest text-gray-500">
                {{ __('Template Preset') }}
            </label>
            <select
                id="template_preset"
                x-model="selectedTemplatePreset"
                class="min-w-56 flex-1 rounded-md border-gray-300 text-sm text-gray-700 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
            >
                <option value="">{{ __('Choose preset') }}</option>
                @foreach ($templatePresetOptions as $presetKey => $preset)
                    <option value="{{ $presetKey }}">{{ $preset['label'] }}</option>
                @endforeach
            </select>
            <button
                type="button"
                @click="applyTemplatePreset()"
                :disabled="selectedTemplatePreset === ''"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ __('Apply') }}
            </button>
        </div>

        <p
            x-show="selectedTemplatePreset && templatePresets[selectedTemplatePreset]"
            x-cloak
            class="mt-2 text-sm text-gray-500"
            x-text="templatePresets[selectedTemplatePreset] && templatePresets[selectedTemplatePreset].description ? templatePresets[selectedTemplatePreset].description : ''"
        ></p>

        @unless ($flags['canEditScript'])
            <p class="mt-2 text-sm text-gray-500">
                {{ __('Preset JavaScript stays hidden unless your account has the module JavaScript permission. HTML and CSS still apply normally.') }}
            </p>
        @endunless
    </div>
@endif

<div class="border-b border-gray-200 bg-white px-4 py-3 sm:px-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        {{-- Source-code tab switcher for the three template languages. --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" @click="setActiveTab('html')" :class="editorTabClass('html')" class="border-b-2 px-1 pb-2 text-sm font-mono transition">HTML</button>
            <button type="button" @click="setActiveTab('css')" :class="editorTabClass('css')" class="border-b-2 px-1 pb-2 text-sm font-mono transition">CSS</button>
            @if ($flags['canEditScript'])
                <button type="button" @click="setActiveTab('js')" :class="editorTabClass('js')" class="border-b-2 px-1 pb-2 text-sm font-mono transition">JS</button>
            @endif
        </div>

        {{-- Active-code history actions target the visible CodeMirror tab only. --}}
        <div class="flex items-start gap-2">
            <button
                type="button"
                @click.prevent="undoActiveCodeEditor()"
                :disabled="!canControlActiveCodeEditor()"
                class="inline-flex min-w-[3.5rem] flex-col items-center justify-center gap-1 rounded-md px-2 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                title="{{ __('Undo the last code change') }}"
            >
                <x-icon name="undo-left-round" class="h-5 w-5" />
                <span>{{ __('Undo') }}</span>
            </button>

            <button
                type="button"
                @click.prevent="redoActiveCodeEditor()"
                :disabled="!canControlActiveCodeEditor()"
                class="inline-flex min-w-[3.5rem] flex-col items-center justify-center gap-1 rounded-md px-2 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                title="{{ __('Redo the last undone code change') }}"
            >
                <x-icon name="undo-right-round" class="h-5 w-5" />
                <span>{{ __('Redo') }}</span>
            </button>
        </div>
    </div>
</div>

<div class="min-h-0 flex-1 overflow-hidden">
    <div x-show="activeTab === 'html'" x-cloak class="flex h-full min-h-0 flex-col overflow-hidden px-4 py-4 sm:px-5">
        <div class="shrink-0">
            <x-input-label for="template_html" :value="__('Template HTML')" :required="true" />
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Use Mustache placeholders such as') }} <code>@{{ module.name }}</code>, <code>@{{ config.title }}</code>, <code>@{{#each items}}</code>.
            </p>
        </div>

        <div class="min-h-0 flex-1 pt-4">
            <textarea
                id="template_html"
                x-ref="templateHtmlField"
                name="template_html"
                rows="24"
                data-module-code-editor
                data-editor-language="html"
                data-editor-fill="1"
                class="block h-full w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
            >{{ $templates['html'] }}</textarea>
        </div>

        @if ($flags['canBypassSanitizer'])
            <p class="mt-3 shrink-0 text-sm text-red-600">
                {{ __('Admin mode bypasses HTML sanitization. Inline scripts, forms, iframes, and global markup are preserved exactly as written.') }}
            </p>
        @else
            <p class="mt-3 shrink-0 text-sm text-gray-500">
                {{ __('HTML is sanitized before preview and save. Unsafe scripts, handlers, and blocked embeds are removed automatically.') }}
            </p>
        @endif

        <x-input-error class="mt-2 shrink-0" :messages="$errors->get('template_html')" />
    </div>

    <div x-show="activeTab === 'css'" x-cloak class="flex h-full min-h-0 flex-col overflow-hidden px-4 py-4 sm:px-5">
        <div class="shrink-0">
            <x-input-label for="template_css" :value="__('Template CSS')" />
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Use CSS for pseudo-elements, layout glue, and advanced presentation details.') }}
            </p>
        </div>

        <div class="min-h-0 flex-1 pt-4">
            <textarea
                id="template_css"
                x-ref="templateCssField"
                name="template_css"
                rows="24"
                data-module-code-editor
                data-editor-language="css"
                data-editor-fill="1"
                class="block h-full w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
            >{{ $templates['css'] }}</textarea>
        </div>

        <x-input-error class="mt-2 shrink-0" :messages="$errors->get('template_css')" />
    </div>

    @if ($flags['canEditScript'])
        <div x-show="activeTab === 'js'" x-cloak class="flex h-full min-h-0 flex-col overflow-hidden px-4 py-4 sm:px-5">
            <div class="shrink-0">
                <x-input-label for="template_js" :value="__('Template JS')" />
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('Keep scripts local to the rendered module output. Access is controlled by the module JavaScript permission.') }}
                </p>
            </div>

            <div class="min-h-0 flex-1 pt-4">
                <textarea
                    id="template_js"
                    x-ref="templateJsField"
                    name="template_js"
                    rows="24"
                    data-module-code-editor
                    data-editor-language="javascript"
                    data-editor-fill="1"
                    class="block h-full w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                >{{ $templates['js'] }}</textarea>
            </div>

            <x-input-error class="mt-2 shrink-0" :messages="$errors->get('template_js')" />
        </div>
    @else
        <div x-show="activeTab === 'js'" x-cloak class="grid h-full place-items-center px-6 py-10 text-center">
            <div class="max-w-sm rounded-lg border border-gray-200 bg-gray-50 px-6 py-8 shadow-sm">
                <p class="text-base font-semibold text-gray-900">{{ __('JavaScript editing requires the module JavaScript permission.') }}</p>
                <p class="mt-2 text-sm leading-6 text-gray-500">{{ __('You can still work with HTML, CSS, provider filters, and raw JSON settings without script access.') }}</p>
            </div>
        </div>
    @endif
</div>
