{{--
    Preview-side workspace for the module editor.
    It keeps notices and revision context above the isolated render canvas.
--}}

<section class="min-h-0 min-w-0 flex-1 bg-gray-100" :style="previewPaneStyle()">
    <div class="flex h-full min-h-0 flex-col overflow-hidden">
        
        {{-- Preview header summarises the current module state and render context. --}}
        <div class="border-b border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Live Preview') }}</p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ __('Rendered module surface') }}</h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        {{ __('Use this canvas to verify layout, Tailwind utilities, scoped CSS, and the current data-source output before saving.') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-widest text-gray-500">
                    @if ($module->moduleCategory)
                        <span class="rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm">
                            {{ ucfirst((string) $module->moduleCategory->name) }}
                        </span>
                    @endif

                    <span class="rounded-md border px-3 py-2 shadow-sm {{ (bool) old('is_active', $module->is_active ?? true) ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-300 bg-white text-gray-500' }}">
                        {{ (bool) old('is_active', $module->is_active ?? true) ? __('Active') : __('Inactive') }}
                    </span>
                </div>
            </div>

            {{-- Flash messages, revision info, and preview errors live above the canvas. --}}
            <div class="mt-4 space-y-3">
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
