{{--
    Preview-side workspace for the module editor.
    It keeps notices and revision context above the isolated render canvas.
--}}

<section
    x-data="{ previewDetailsOpen: false }"
    class="min-h-0 min-w-0 flex-1 bg-gray-100"
    :style="previewPaneStyle()"
>
    <div class="flex h-full min-h-0 flex-col overflow-hidden">
        
        {{-- Preview header summarises the current module state and render context. --}}
        <div class="border-b border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
            <button
                type="button"
                @click="previewDetailsOpen = !previewDetailsOpen"
                class="group -mx-2 flex w-[calc(100%+1rem)] flex-wrap items-start justify-between gap-4 rounded-lg px-2 py-2 text-left transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                :aria-expanded="previewDetailsOpen.toString()"
                aria-controls="module-preview-details"
                :aria-label="previewDetailsOpen ? '{{ __('Hide preview details') }}' : '{{ __('Show preview details') }}'"
                :title="previewDetailsOpen ? '{{ __('Hide details') }}' : '{{ __('Show details') }}'"
            >
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Live Preview') }}</p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ __('Rendered module surface') }}</h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        {{ __('Use this canvas to verify layout, Tailwind utilities, scoped CSS, and the current data-source output before saving.') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 text-xs font-semibold uppercase tracking-widest text-gray-500">
                    @if ($module->moduleCategory)
                        <span class="rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm transition group-hover:border-[#5EB4EE]/50 group-hover:bg-[#f8fcff]">
                            {{ ucfirst((string) $module->moduleCategory->name) }}
                        </span>
                    @endif

                    <span class="rounded-md border px-3 py-2 shadow-sm transition {{ (bool) old('is_active', $module->is_active ?? true) ? 'border-green-200 bg-green-50 text-green-700 group-hover:border-green-300 group-hover:bg-green-100' : 'border-gray-300 bg-white text-gray-500 group-hover:border-[#5EB4EE]/50 group-hover:bg-[#f8fcff]' }}">
                        {{ (bool) old('is_active', $module->is_active ?? true) ? __('Active') : __('Inactive') }}
                    </span>

                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center">
                        <x-icon
                            name="arr"
                            class="size-6 transition-transform duration-200"
                            ::class="previewDetailsOpen ? 'rotate-180' : ''"
                        />
                    </span>
                </div>
            </button>

            {{-- Flash messages, revision info, and preview errors can collapse to free more preview space. --}}
            <div
                id="module-preview-details"
                x-show="previewDetailsOpen"
                x-transition.opacity.duration.150ms
                x-cloak
                class="mt-4 space-y-3"
            >
                @if ($flags['embeddedAccessLocked'])
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ __('This module uses an embedded-content category. Your account can inspect the current revision, but editing and previewing are locked until the embedded module permission is granted.') }}
                    </div>
                @endif

                @if ($errors->has('module'))
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first('module') }}
                    </div>
                @endif

                @if ($flags['isEdit'])
                    <x-services.modules.editor.revision-meta
                        :module="$module"
                        :revisions="$revisions"
                    />
                @endif

                <div
                    x-show="hasPreviewError(previewError)"
                    x-cloak
                    class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    x-text="previewError"
                ></div>
            </div>
        </div>

        {{-- Isolated iframe preview canvas for the current draft render. --}}
        <x-services.modules.editor.preview-pane />
    </div>
</section>
