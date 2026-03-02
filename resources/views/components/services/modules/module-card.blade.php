{{--
    Module list card shown on modules index.
--}}

@props([
    'module',
])

<article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="flex items-start justify-between gap-4">
        <div>
            {{-- Module title and active/inactive state --}}
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-gray-900">{{ $module->name }}</h2>
                @if (!$module->is_active)
                    <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700">{{ __('Inactive') }}</span>
                @endif
            </div>

            {{-- Category and revision chips --}}
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                @if ($module->moduleCategory)
                    <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700">
                        {{ ucfirst($module->moduleCategory->name) }}
                    </span>
                    @if ($module->moduleCategory->is_widget)
                        <span class="rounded bg-sky-100 px-2 py-0.5 text-sky-800">{{ __('Widget') }}</span>
                    @endif
                @endif
                <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700">
                    {{ __('Revision') }} {{ $module->revision }}
                </span>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="flex items-center gap-2">
            <x-secondary-link href="{{ route('module.show', ['module' => $module]) }}">
                {{ __('Preview') }}
            </x-secondary-link>
            @can('update', $module)
                <x-primary-link href="{{ route('module.edit', ['module' => $module]) }}">
                    {{ __('Edit') }}
                </x-primary-link>
            @endcan
        </div>
    </div>

    {{-- Audit footer --}}
    <p class="mt-3 text-xs text-gray-500">
        {{ __('Updated') }} {{ $module->updated_at?->diffForHumans() }} |
        {{ __('Author') }} {{ $module->createdBy?->name ?? __('System') }}
    </p>
</article>
