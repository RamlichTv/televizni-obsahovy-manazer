{{--
    Preview-first pane for the read-only module shell.
    The canvas shares the same iframe isolation contract as the editor preview.
--}}

@props([
    'module',
])

<section class="min-h-0 min-w-0 flex-1 bg-gray-100" :style="previewPaneStyle()">
    <div class="flex h-full min-h-0 flex-col overflow-hidden">
        <div class="border-b border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Preview Canvas') }}</p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ __('Rendered module output') }}</h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        {{ __('This isolated surface mirrors how the saved module renders inside runtime cells without leaking styles or scripts into the shell.') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-widest text-gray-500">
                    <span class="rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm">
                        {{ __('UUID') }}: {{ $module->id }}
                    </span>
                    <span class="rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm">
                        {{ __('Owner') }}: {{ $module->createdBy?->name ?? __('System') }}
                    </span>
                </div>
            </div>

            <div class="mt-4 space-y-3">
                @if ($errors->has('module'))
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first('module') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="module-editor-checkerboard min-h-0 flex-1 overflow-hidden p-4 sm:p-5 lg:p-6">
            <div class="flex h-full min-h-0 items-stretch">
                <div class="module-editor-surface relative flex h-full min-h-0 w-full min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <iframe
                        x-show="hasPreviewHtml(previewHtml)"
                        x-cloak
                        title="{{ __('Module Preview') }}"
                        class="block h-full min-h-full w-full min-w-0 border-0 bg-white"
                        :srcdoc="previewDocumentHtml(previewHtml)"
                    ></iframe>

                    <div
                        x-show="!hasPreviewHtml(previewHtml)"
                        x-cloak
                        class="grid h-full min-h-full w-full place-items-center px-6 py-10"
                    >
                        <div class="max-w-md text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-gray-100 text-gray-500 shadow-inner">
                                <svg viewBox="0 0 24 24" fill="none" class="h-7 w-7" aria-hidden="true">
                                    <path d="M7 5h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5"></path>
                                    <path d="M9 9h6M9 12h6M9 15h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                </svg>
                            </div>
                            <p class="mt-5 text-xl font-semibold text-gray-900">{{ __('No rendered output available yet.') }}</p>
                            <p class="mt-2 text-sm leading-6 text-gray-500">
                                {{ __('Once this module has renderable content, it will appear here inside the isolated preview canvas.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
