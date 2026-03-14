{{--
    Shared module preview frame.
    Renders module output inside an iframe so preview CSS/JS stay isolated from the editor shell.
--}}

@props([
    'html' => '',
    'emptyTitle' => '',
    'emptyDescription' => '',
])

@php
    // Load the compiled app stylesheet into the iframe so Tailwind classes work in previews too.
    try {
        $runtimeCssHref = (string) app(\Illuminate\Foundation\Vite::class)->asset('resources/css/app.css');
    } catch (\Throwable) {
        $runtimeCssHref = '';
    }

    // Load the lightweight Alpine runtime so previewed modules can use x-data,
    // x-show, x-on, and other Alpine directives inside the iframe document.
    try {
        $runtimeScriptHref = (string) app(\Illuminate\Foundation\Vite::class)->asset('resources/js/module-runtime.js');
    } catch (\Throwable) {
        $runtimeScriptHref = '';
    }

    // Build a minimal HTML document for iframe srcdoc so preview content starts from a predictable surface.
    $documentHtml = '<!doctype html><html lang="' . e(str_replace('_', '-', app()->getLocale())) . '"><head>'
        . '<meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<link rel="preconnect" href="https://fonts.bunny.net">'
        . '<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />'
        . ($runtimeCssHref !== '' ? '<link rel="stylesheet" href="' . e($runtimeCssHref) . '">' : '')
        . ($runtimeScriptHref !== '' ? '<script type="module" src="' . e($runtimeScriptHref) . '"></script>' : '')
        . '<style>html,body{margin:0;padding:0;width:100%;height:100%;min-width:0;min-height:100%;background:#fff;overflow:hidden}body{display:block}*,*::before,*::after{box-sizing:border-box}[x-cloak]{display:none!important}</style>'
        . '</head><body class="font-sans antialiased">'
        . (is_string($html) ? $html : '')
        . '</body></html>';
@endphp

<div {{ $attributes->class('h-full min-h-0 max-w-full rounded-lg border border-gray-200 bg-white p-3 md:p-4') }}>
    <div class="h-full min-h-0 max-w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
        @if (is_string($html) && trim($html) !== '')
            <iframe
                title="{{ __('Module Preview') }}"
                class="block h-full min-h-full w-full min-w-0 max-w-full border-0 bg-white"
                srcdoc="{{ $documentHtml }}"
            ></iframe>
        @else
            <div class="grid h-full min-h-full place-items-center px-6 py-10">
                <div class="mx-auto max-w-sm text-center">
                    <p class="text-base font-semibold text-gray-800">
                        {{ $emptyTitle }}
                    </p>
                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        {{ $emptyDescription }}
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
