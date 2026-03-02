{{--
    Tailwind build status panel used on the modules index page.
    Keeps status formatting in one place so the page view stays focused on layout.
--}}

@props([
    'tailwindBuild' => [],
])

@php
    $tailwindBuildState = (string) ($tailwindBuild['state'] ?? 'idle');
    $tailwindBuildUpdatedAt = $tailwindBuild['updated_at'] ?? null;
    $tailwindBuildError = trim((string) ($tailwindBuild['error'] ?? ''));

    $tailwindBuildStateClasses = match ($tailwindBuildState) {
        'queued' => 'bg-sky-100 text-sky-800',
        'running' => 'bg-amber-100 text-amber-800',
        'success' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-700',
    };

    $tailwindBuildStateLabel = match ($tailwindBuildState) {
        'queued' => __('Queued'),
        'running' => __('Running'),
        'success' => __('Successful'),
        'failed' => __('Failed'),
        default => __('Idle'),
    };
@endphp

<div class="mb-4 rounded-md border border-gray-200 bg-white p-4">
    {{-- Current scheduler/build state --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">{{ __('Tailwind Build Status') }}</h2>
            <p class="text-xs text-gray-500">
                {{ __('Queued build runs `npm run build` so module templates can use Tailwind classes. Queue worker must have Node/npm.') }}
            </p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tailwindBuildStateClasses }}">
            {{ $tailwindBuildStateLabel }}
        </span>
    </div>

    @if (!empty($tailwindBuildUpdatedAt))
        <p class="mt-2 text-xs text-gray-600">
            {{ __('Last update: :time', ['time' => \Illuminate\Support\Carbon::parse($tailwindBuildUpdatedAt)->diffForHumans()]) }}
        </p>
    @endif

    @if ($tailwindBuildState === 'failed' && $tailwindBuildError !== '')
        {{-- Last failure output to help quick triage without opening logs --}}
        <div class="mt-3 rounded-md border border-red-200 bg-red-50 p-3">
            <p class="text-xs font-semibold text-red-700">{{ __('Latest build error') }}</p>
            <pre class="mt-2 whitespace-pre-wrap wrap-break-word text-xs text-red-700">{{ $tailwindBuildError }}</pre>
            <p class="mt-2 text-xs text-red-700">
                {{ __('Module changes were saved. Fix the build issue and re-run build, or save a module again to queue another attempt.') }}
            </p>
        </div>
    @endif
</div>
