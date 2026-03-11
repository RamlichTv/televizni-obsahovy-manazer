{{--
    View: Profile Editor
    Goal: Compose high-level layout from focused Blade components while Alpine state lives in a dedicated JS module.
    Result: This file stays short and descriptive; heavy HTML and JavaScript are delegated to reusable building blocks.
--}}

<x-app-layout title="{{ $profile->name }}">
    <x-slot name="profilesNavTitle">
        {{ __('Editor') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between w-full">
            <div class="flex items-center gap-3">
                <x-secondary-link href="{{ route('profiles.index') }}">
                    <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                    {{ __('Back') }}
                </x-secondary-link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $profile->name }}</h1>
                    <p class="text-xs text-gray-500">
                        {{ __('Visual Layout Editor') }} • {{ __('Slug') }}: {{ $profile->slug }}
                    </p>
                </div>
            </div>

            <x-secondary-link href="{{ route('profile.show', ['profile' => $profile]) }}">
                {{ __('Preview Mode') }}
            </x-secondary-link>
        </div>
    </x-slot>

    <section
        class="px-4 py-6"
        x-data="profileLayoutEditor({
            pages: @js($editorPages),
            modules: @js($moduleCatalog),
            routes: @js($editorRoutes),
            csrfToken: '{{ csrf_token() }}',
            maxTracks: 24,
            i18n: @js([
                'pageNamePrompt' => __('Page name'),
                'pageAdded' => __('Page added to profile.'),
                'atLeastOnePage' => __('At least one page must remain.'),
                'confirmDeletePage' => __('Delete current page?'),
                'pageRemoved' => __('Page removed.'),
                'maxRowsReached' => __('Maximum row count reached.'),
                'rowOccupied' => __('Cannot remove row because a module occupies it.'),
                'maxColumnsReached' => __('Maximum column count reached.'),
                'columnOccupied' => __('Cannot remove column because a module occupies it.'),
                'moduleAdded' => __('Module added to page.'),
                'modulePlacementUpdated' => __('Module placement updated.'),
                'confirmRemoveModule' => __('Remove this module from the page?'),
                'moduleRemoved' => __('Module removed from page.'),
                'rowSpanExceeded' => __('Row span exceeds page bounds.'),
                'columnSpanExceeded' => __('Column span exceeds page bounds.'),
                'pageUpdated' => __('Page updated.'),
                'requestFailed' => __('Request failed.'),
                'uncategorized' => __('Uncategorized'),
            ]),
        })"
        x-init="init()"
    >
        <div class="mx-auto max-w-384 space-y-4">
            {{-- Backend success flash after full-page form submissions. --}}
            @if (session('success'))
                <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Backend validation summary for the settings form below. --}}
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Live Alpine feedback for in-canvas actions (drag/drop, resize, async saves). --}}
            <div
                class="rounded-md border p-3 text-sm"
                :class="flash.type === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700'"
                x-show="flash.message"
                x-cloak
            >
                <span x-text="flash.message"></span>
            </div>

            {{-- Visual page builder with drag/drop, sizing, and module placement controls. --}}
            <x-services.profiles.layout-editor />

            {{-- Persistent profile metadata and advanced behavior settings. --}}
            <x-services.profiles.settings-form :profile="$profile" />
        </div>
    </section>
</x-app-layout>
