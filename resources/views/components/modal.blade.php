{{--
    Shared modal component.
    It renders either one reusable named modal shell or the singleton alert / confirm host used by the feedback system.
--}}

@props([
    'name' => null,
    'show' => false,
    'maxWidth' => '2xl',
    'kind' => 'named',
])

@php
    /**
     * Normalize supported modal widths so larger manager dialogs can reuse the same shell.
     */
    $maxWidthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        '6xl' => 'sm:max-w-6xl',
        '7xl' => 'sm:max-w-7xl',
    ][$maxWidth] ?? 'sm:max-w-2xl';
@endphp

@if ($kind === 'system')
    <div
        x-data="appSystemModal()"
        x-init="init()"
        x-on:keydown.escape.window="isOpen && isTopModal() ? cancel() : null"
        x-on:keydown.tab.window="handleTab($event)"
        x-cloak
        class="pointer-events-none fixed inset-0 z-[70]"
    >
        {{-- System modal backdrop for alert / confirm flows. --}}
        <div
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto fixed inset-0 bg-gray-900/40 backdrop-blur-[1px]"
            @click="isTopModal() ? cancel() : null"
        ></div>

        {{-- System dialog panel for promise-based alert / confirm interactions. --}}
        <div x-show="isOpen" class="pointer-events-auto fixed inset-0 overflow-y-auto px-4 py-6 sm:px-6">
            <div class="flex min-h-full items-center justify-center">
                <div
                    x-ref="panel"
                    x-show="isOpen"
                    x-transition:enter="transform transition ease-out duration-300"
                    x-transition:enter-start="translate-y-4 opacity-0 sm:scale-95"
                    x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                    x-transition:leave="transform transition ease-in duration-200"
                    x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                    x-transition:leave-end="translate-y-4 opacity-0 sm:scale-95"
                    class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-2xl"
                    tabindex="-1"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="title ? 'system-feedback-modal-title' : null"
                >
                    <div class="border-b border-gray-100 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 id="system-feedback-modal-title" class="text-lg font-semibold text-gray-900" x-text="title || '{{ __('Notice') }}'"></h2>
                                <p class="mt-2 text-sm leading-6 text-gray-500" x-text="message"></p>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                                @click="cancel()"
                            >
                                <span class="sr-only">{{ __('Close dialog') }}</span>
                                <x-icon name="x" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4">
                        {{-- Confirm modals keep an explicit secondary action for cancellation. --}}
                        <button
                            x-show="type === 'confirm'"
                            x-cloak
                            type="button"
                            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                            @click="cancel()"
                        >
                            <span x-text="cancelLabel"></span>
                        </button>

                        {{-- Primary action label and tone are controlled by the modal request payload. --}}
                        <button
                            x-ref="confirmButton"
                            type="button"
                            :class="confirmButtonClasses()"
                            @click="confirm()"
                        >
                            <span x-text="confirmLabel"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div
        x-data="appNamedModal({ name: @js($name), show: @js($show) })"
        x-init="init()"
        x-on:keydown.escape.window="show && isTopModal() ? close() : null"
        x-on:keydown.tab.window="handleTab($event)"
        x-cloak
        class="pointer-events-none fixed inset-0 z-50"
    >
        {{-- Named modal backdrop for reusable page-level manager dialogs. --}}
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto fixed inset-0 bg-gray-900/35 backdrop-blur-[1px]"
            @click="isTopModal() ? close() : null"
        ></div>

        {{-- Named modal dialog shell shared by the content and screen manager overlays. --}}
        <div x-show="show" class="pointer-events-auto fixed inset-0 overflow-y-auto px-4 py-6 sm:px-6">
            <div class="flex min-h-full items-center justify-center">
                <div
                    x-ref="panel"
                    x-show="show"
                    x-transition:enter="transform transition ease-out duration-300"
                    x-transition:enter-start="translate-y-4 opacity-0 sm:scale-95"
                    x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                    x-transition:leave="transform transition ease-in duration-200"
                    x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                    x-transition:leave-end="translate-y-4 opacity-0 sm:scale-95"
                    class="w-full {{ $maxWidthClass }} overflow-hidden rounded-2xl bg-white shadow-2xl"
                    tabindex="-1"
                    role="dialog"
                    aria-modal="true"
                >
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
@endif
