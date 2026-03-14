{{--
    Overview:
    This component renders a TV-styled screen summary card used in the screens service grid.
--}}

@props([
    'screen',
])

@php
    // Normalize labels in one place so markup stays focused on layout only.
    $statusRaw = trim((string) ($screen->status?->name ?? ''));
    $orientationRaw = strtolower((string) ($screen->orientation?->name ?? ''));
    $statusLabel = $statusRaw !== '' ? ucfirst(__($statusRaw)) : __('Unknown');
    $orientationLabel = $screen->orientation?->name ? ucfirst(__((string) $screen->orientation->name)) : __('Unassigned');
    $profileLabel = $screen->profile?->name ?? __('Unassigned');
    $resolutionWidth = (int) ($screen->width ?? 0);
    $resolutionHeight = (int) ($screen->height ?? 0);

    // Mirror the monitor view so the summary card shows the effective playback canvas.
    if ($orientationRaw === 'portrait' && $resolutionWidth > $resolutionHeight) {
        [$resolutionWidth, $resolutionHeight] = [$resolutionHeight, $resolutionWidth];
    } elseif ($orientationRaw === 'landscape' && $resolutionHeight > $resolutionWidth) {
        [$resolutionWidth, $resolutionHeight] = [$resolutionHeight, $resolutionWidth];
    }

    $resolutionLabel = ($resolutionWidth > 0 && $resolutionHeight > 0)
        ? ($resolutionWidth . ' x ' . $resolutionHeight)
        : __('Not set');

    // Room label mirrors the building > floor > room breadcrumb used elsewhere.
    $roomLabel = $screen->room?->breadcrumbLabel(' > ') ?: __('Unassigned');

    // "Online-like" statuses get a green badge, everything else amber.
    $isOnlineLike = in_array(strtolower($statusRaw), ['online', 'active', 'enabled', 'ready'], true);
    $statusPillClasses = $isOnlineLike
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-amber-200 bg-amber-50 text-amber-700';
@endphp

<a
    href="{{ route('screen.edit', ['screen' => $screen]) }}"
    {{ $attributes->class(['group block h-full']) }}
>
    <div class="h-full rounded-2xl bg-[#4a4a4a] p-3 shadow ring-1 ring-[#666666] transition duration-300 group-hover:-translate-y-0.5 group-hover:shadow-lg group-hover:ring-[#777777]">
        <div class="relative overflow-hidden rounded-xl border-[6px] border-[#707070] bg-[#ececec]">

            <div class="relative flex min-h-44 flex-col justify-between p-4">
                <div class="flex items-start justify-end gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $statusPillClasses }}">
                        <span class="inline-block size-1.5 rounded-full {{ $isOnlineLike ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        {{ $statusLabel }}
                    </span>
                </div>

                <div>
                    <h2 class="truncate text-lg font-semibold leading-tight text-slate-900" title="{{ $screen->name }}">
                        {{ $screen->name }}
                    </h2>
                    <p class="mt-1 truncate text-xs text-slate-700">
                        {{ __('Profile') }}: {{ $profileLabel }}
                    </p>
                </div>

                <p class="truncate text-xs text-slate-600" title="{{ $roomLabel }}">
                    {{ $roomLabel }}
                </p>
            </div>
        </div>

        <div class="mx-auto mt-2 h-1.5 w-16 rounded-full bg-[#686868]"></div>
        <div class="mx-auto mt-1 h-2 w-24 rounded-full bg-[#5a5a5a]"></div>

        <div class="mt-3 grid grid-cols-2 gap-2">
            <div class="rounded-lg border border-[#c9c9c9] bg-[#efefef] px-2 py-1.5">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">{{ __('Orientation') }}</p>
                <p class="truncate text-xs font-medium text-slate-800">{{ $orientationLabel }}</p>
            </div>

            <div class="rounded-lg border border-[#c9c9c9] bg-[#efefef] px-2 py-1.5">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">{{ __('Resolution') }}</p>
                <p class="truncate text-xs font-medium text-slate-800">{{ $resolutionLabel }}</p>
            </div>

            <div class="col-span-2 rounded-lg border border-[#c9c9c9] bg-[#efefef] px-2 py-1.5">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">{{ __('Current Profile') }}</p>
                <p class="truncate text-xs font-medium text-slate-800">{{ $profileLabel }}</p>
            </div>
        </div>
    </div>
</a>
