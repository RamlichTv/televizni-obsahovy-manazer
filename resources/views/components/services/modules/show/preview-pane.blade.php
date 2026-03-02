{{--
    Rendered output panel for module show view.
--}}

@props([
    'module',
    'previewHtml' => '',
    'availableRevisions' => [],
    'selectedRevision' => null,
])

<div class="flex-1 overflow-y-auto bg-gray-50 p-4 md:p-6 space-y-5">
    <div class="rounded-xl border border-gray-200 bg-white p-4 md:p-5">
        
        {{-- Preview heading, revision switch, and sidebar toggle --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('Rendered Output') }}</h2>

            <div class="flex flex-wrap items-center gap-2">
                @if (count($availableRevisions) > 1)
                    <select
                        class="rounded-md border-gray-300 text-xs shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                        onchange="window.location.href = this.value"
                    >
                        @foreach ($availableRevisions as $revisionOption)
                            <option
                                value="{{ route('module.show', ['module' => $module, 'revision' => $revisionOption]) }}"
                                @selected((int) $selectedRevision === (int) $revisionOption)
                            >
                                {{ __('Revision') }} {{ $revisionOption }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <button
                    type="button"
                    @click="sidebarOpen = !sidebarOpen"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                >
                    <span x-text="sidebarOpen ? '{{ __('Hide Sidebar') }}' : '{{ __('Show Sidebar') }}'"></span>
                </button>
            </div>
        </div>

        {{-- Module badges --}}
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
            @if ($module->moduleCategory)
                <span class="rounded bg-gray-100 px-2 py-1 text-gray-700">
                    {{ ucfirst($module->moduleCategory->name) }}
                </span>
                @if ($module->moduleCategory->is_widget)
                    <span class="rounded bg-sky-100 px-2 py-1 text-sky-800">{{ __('Widget') }}</span>
                @endif
            @endif
            <span class="rounded px-2 py-1 {{ $module->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                {{ $module->is_active ? __('Active') : __('Inactive') }}
            </span>
        </div>

        {{-- Rendered module output (or fallback empty state) --}}
        <div class="m-4 min-h-[58vh] grid rounded-lg border border-gray-200 bg-white p-5 overflow-auto">
            @if (is_string($previewHtml) && trim($previewHtml) !== '')
                {!! $previewHtml !!}
            @else
                <div class="grid min-h-[52vh] place-items-center rounded-lg border border-dashed border-gray-300 bg-gray-50">
                    <p class="px-4 text-center text-sm text-gray-500">
                        {{ __('No rendered output available.') }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
