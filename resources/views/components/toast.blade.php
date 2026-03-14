{{--
    Global toast host.
    It keeps Laravel session flashes and JavaScript-driven notices in one shared light-themed notification surface.
--}}

@php
    /**
     * Seed the toast host with the first available Laravel flash message.
     *
     * The host still accepts runtime `app-toast` events after Alpine boots.
     */
    $initialToast = collect(['success', 'error', 'warning', 'info'])
        ->map(fn (string $type) => session()->has($type)
            ? ['type' => $type, 'message' => (string) session($type)]
            : null)
        ->first(fn (?array $toast) => $toast !== null);
@endphp

<div
    x-data="appToastHost(@js($initialToast))"
    x-init="init()"
    x-on:keydown.escape.window="isVisible ? closeToast() : null"
    x-cloak
    class="pointer-events-none fixed inset-x-4 bottom-4 z-[80] flex justify-end sm:inset-x-auto sm:right-4 sm:bottom-4"
>
    {{-- Toast card: compact, light, and visually consistent with the rest of the app. --}}
    <div
        x-show="isVisible && toast"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-2 opacity-0"
        class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-xl border bg-white shadow-lg"
        :class="{
            'border-green-200': toast?.type === 'success',
            'border-red-200': toast?.type === 'error',
            'border-amber-200': toast?.type === 'warning',
            'border-sky-200': toast?.type === 'info',
        }"
        role="status"
        aria-live="polite"
    >
        <div class="flex items-start gap-3 p-4">
            {{-- Status icon keeps each toast type scannable without leaving the light theme. --}}
            <div
                class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-green-100 text-green-700': toast?.type === 'success',
                    'bg-red-100 text-red-700': toast?.type === 'error',
                    'bg-amber-100 text-amber-700': toast?.type === 'warning',
                    'bg-sky-100 text-sky-700': toast?.type === 'info',
                }"
                aria-hidden="true"
            >
                <svg x-show="toast?.type === 'success'" x-cloak viewBox="0 0 20 20" fill="none" class="h-5 w-5">
                    <path d="M5 10.5 8.2 13.7 15 7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <svg x-show="toast?.type === 'error'" x-cloak viewBox="0 0 20 20" fill="none" class="h-5 w-5">
                    <path d="M6 6 14 14M14 6 6 14" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
                </svg>
                <svg x-show="toast?.type === 'warning'" x-cloak viewBox="0 0 20 20" fill="none" class="h-5 w-5">
                    <path d="m10 3 7 12.5a1 1 0 0 1-.87 1.5H3.87A1 1 0 0 1 3 15.5L10 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                    <path d="M10 7.5v3.75M10 14h.01" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
                </svg>
                <svg x-show="toast?.type === 'info'" x-cloak viewBox="0 0 20 20" fill="none" class="h-5 w-5">
                    <path d="M10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z" stroke="currentColor" stroke-width="1.5" />
                    <path d="M10 8.25h.01M9.5 11h1v3h1" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>

            {{-- Toast copy stays intentionally concise so the card matches existing app density. --}}
            <div class="min-w-0 flex-1">
                <p
                    class="text-sm font-semibold"
                    :class="{
                        'text-green-800': toast?.type === 'success',
                        'text-red-800': toast?.type === 'error',
                        'text-amber-800': toast?.type === 'warning',
                        'text-sky-800': toast?.type === 'info',
                    }"
                    x-text="{
                        success: '{{ __('Success') }}',
                        error: '{{ __('Error') }}',
                        warning: '{{ __('Warning') }}',
                        info: '{{ __('Info') }}',
                    }[toast?.type] ?? '{{ __('Notice') }}'"
                ></p>
                <p class="mt-1 text-sm leading-6 text-gray-600" x-text="toast?.message ?? ''"></p>
            </div>

            {{-- Manual dismissal keeps the toast unobtrusive when the user wants it gone sooner. --}}
            <button
                type="button"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                @click="closeToast()"
            >
                <span class="sr-only">{{ __('Dismiss notification') }}</span>
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    </div>
</div>
