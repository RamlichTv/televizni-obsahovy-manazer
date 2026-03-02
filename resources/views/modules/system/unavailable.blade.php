{{--
    Overview:
    This view composes the modules > system > unavailable interface, arranging reusable components and route-driven actions so the page stays readable while presentation concerns remain separated from backend logic.
--}}

<section class="rounded-xl border border-red-200 bg-red-50 p-4">
    <h3 class="text-sm font-semibold text-red-800">
        {{ $module->name }}
    </h3>

    <p class="mt-1 text-sm text-red-700">
        {{ __('Module could not be rendered.') }}
    </p>

    @if (!empty($reason) && auth()->check() && auth()->user()->hasPermission('edit_modules'))
        <p class="mt-2 text-xs text-red-700">{{ $reason }}</p>
    @endif
</section>
