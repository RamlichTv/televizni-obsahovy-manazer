{{--
    Reusable draft-preview canvas for the module editor.
    The iframe keeps module CSS and JS isolated while still showing the current render output.
--}}

<div class="module-editor-checkerboard min-h-0 flex-1 overflow-hidden p-4 sm:p-5 lg:p-6">
    <div class="module-editor-surface relative flex h-full min-h-0 w-full min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div
            x-show="isPreviewLoading"
            x-cloak
            x-transition.opacity
            class="absolute inset-0 z-10 flex items-center justify-center bg-white/75 backdrop-blur-sm"
        >
            <div class="rounded-lg border border-gray-200 bg-white px-5 py-4 text-center shadow-sm">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-[#e9faff] text-[#077CB6]">
                    <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-20"></circle>
                        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                    </svg>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">{{ __('Rendering preview...') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('The current draft is being rendered into the isolated preview frame.') }}</p>
            </div>
        </div>

        <iframe
            x-show="hasPreviewHtml(previewHtml)"
            x-cloak
            title="{{ __('Module Preview') }}"
            class="block h-full min-h-full w-full min-w-0 border-0 bg-white"
            :srcdoc="previewDocumentHtml(previewHtml)"
        ></iframe>

        <div
            x-show="!isPreviewLoading && !hasPreviewHtml(previewHtml)"
            x-cloak
            class="grid h-full min-h-full w-full place-items-center px-6 py-10"
        >
            <div class="max-w-md text-center">
                <h3 class="text-2xl font-semibold text-gray-900">{{ __('Preview appears here after saving or rendering a draft.') }}</h3>
                <p class="mt-4 text-base leading-8 text-gray-500">
                    {{ __('Use this space to check how the module reads inside a page cell.') }}
                </p>

                <div
                    x-show="hasPreviewError(previewError)"
                    x-cloak
                    class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-left text-sm text-red-700 shadow-sm"
                >
                    <p class="font-semibold">{{ __('Preview problem') }}</p>
                    <p class="mt-1" x-text="previewError"></p>
                </div>
            </div>
        </div>
    </div>
</div>
