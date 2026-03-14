{{--
    Reusable revision metadata panel for module edit pages.
    It keeps revision browsing and generated asset paths together in one compact block.
--}}

@props([
    'module',
    'revisions',
])

@if ($revisions['selected'] !== (int) $module->revision)
    <div class="rounded-md border border-[#5EB4EE] bg-[#e9faff] px-4 py-3 text-sm text-gray-700">
        {{ __('You are viewing an older revision snapshot. Saving will create a new latest revision from these contents.') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 md:grid-cols-2 xl:grid-cols-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Revision') }}</p>
        <p class="mt-1 font-medium text-gray-900">{{ $module->revision }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Template Path') }}</p>
        <p class="mt-1 break-all text-xs text-gray-700">{{ $module->template_path ?: __('Not generated yet') }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Style Path') }}</p>
        <p class="mt-1 break-all text-xs text-gray-700">{{ $module->style_path ?: __('Not generated yet') }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Script Path') }}</p>
        <p class="mt-1 break-all text-xs text-gray-700">{{ $module->script_path ?: __('Not generated yet') }}</p>
    </div>

    @if (count($revisions['available']) > 1)
        <div class="md:col-span-2 xl:col-span-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">{{ __('Browse Revision') }}</p>
            <select
                class="mt-2 block w-full max-w-xs rounded-md border-gray-300 text-sm shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                onchange="window.location.href = this.value"
            >
                @foreach ($revisions['available'] as $revisionOption)
                    <option
                        value="{{ route('module.edit', ['module' => $module, 'revision' => $revisionOption]) }}"
                        @selected($revisions['selected'] === $revisionOption)
                    >
                        {{ __('Revision') }} {{ $revisionOption }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif
</div>
