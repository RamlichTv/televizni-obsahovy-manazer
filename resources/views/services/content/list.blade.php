<x-app-layout title="{{ __('Content List') }}">
    <x-slot name="contentNavTitle">

        {{-- Navigation title logic --}}
        @switch(request()->route()->getName())
            @case('content.create') {{ __('Create') }} @break
            @case('content.unpublished') {{ __('Unpublished') }} @break
            @case('content.library') {{ __('Library') }} @break
            @case('content.manage') {{ __('My content') }} @break
            @case('content.archived') {{ __('Archived') }} @break
            @case('content.deleted') {{ __('Deleted') }} @break
            @default {{ __('Content') }}
        @endswitch
    </x-slot>

    <x-slot name="header">
        <div class="flex md:flex-row md:items-center justify-between w-full gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('content.index') }}" class="text-gray-500 hover:text-gray-700 transition">
                    <x-icon class="size-6 rotate-180" name="arrow"/>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ __('Content List') }}
                </h1>
            </div>

            <div class="flex items-center gap-2">
                @if(!request()->routeIs('content.deleted'))
                    <a href="{{ route('content.archived') }}" class="flex items-center space-x-2 px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-700 transition" title="{{ __('Archived') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        <span class="hidden sm:inline">{{ __('Archived') }}</span>
                    </a>

                    @if (Auth::user()->isAdmin())
                        <a href="{{ route('content.deleted') }}" class="flex items-center space-x-2 px-3 py-2 rounded-lg border border-red-300 hover:bg-red-50 text-red-600 transition" title="{{ __('Deleted') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span class="hidden sm:inline">{{ __('Deleted') }}</span>
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    {{-- Prepare picker options and normalize URL filter inputs for multi-select controls. --}}
    @php
        $typeOptions = \App\Models\ContentType::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $tagOptions = \App\Models\Tag::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $authorOptions = \App\Models\User::query()
            ->has('content_objects')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $selectedTypeIds = collect((array) request()->input('type_ids', []));
        if ($selectedTypeIds->isEmpty() && request()->filled('type')) {
            $selectedTypeIds = collect([(string) request()->input('type')]);
        }
        $selectedTypeIds = $selectedTypeIds
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        $selectedTagIds = collect((array) request()->input('tag_ids', []));
        if ($selectedTagIds->isEmpty() && request()->filled('tag')) {
            $selectedTagIds = collect([(string) request()->input('tag')]);
        }
        $selectedTagIds = $selectedTagIds
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        $selectedAuthorIds = collect((array) request()->input('author_ids', []));
        if ($selectedAuthorIds->isEmpty() && request()->filled('author')) {
            $selectedAuthorIds = collect([(string) request()->input('author')]);
        }
        $selectedAuthorIds = $selectedAuthorIds
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        $typesForPicker = $typeOptions->map(fn ($type) => [
            'id' => (string) $type->id,
            'name' => $type->name,
        ])->values();

        $tagsForPicker = $tagOptions->map(fn ($tag) => [
            'id' => (string) $tag->id,
            'name' => $tag->name,
        ])->values();

        $authorsForPicker = $authorOptions->map(fn ($author) => [
            'id' => (string) $author->id,
            'name' => $author->name,
        ])->values();

        $contentSearchValue = trim((string) request('search', ''));
        $searchSuggestions = \App\Models\ContentObject::query()
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();
    @endphp

    {{-- Filter toolbar + result grid wrapper. --}}
    <section class="flex flex-col grow p-4 max-w-screen-2xl w-full mx-auto">
        
        {{-- Shared filter panel: search, type, tags, and creator filters. --}}
        <x-filter-panel
            :search-value="$contentSearchValue"
            :search-placeholder="__('Content name or description...')"
            :search-suggestions="$searchSuggestions"
            :pickers="[
                [
                    'label' => __('Type'),
                    'input_name' => 'type_ids[]',
                    'items' => $typesForPicker,
                    'selected' => $selectedTypeIds,
                    'placeholder' => __('Type'),
                    'no_selection_text' => __('All types'),
                    'no_results_text' => __('No types available to add.'),
                    'add_aria_label' => __('Add type filter'),
                    'remove_aria_label' => __('Remove type filter'),
                ],
                [
                    'label' => __('Tags'),
                    'input_name' => 'tag_ids[]',
                    'items' => $tagsForPicker,
                    'selected' => $selectedTagIds,
                    'placeholder' => __('Tag'),
                    'no_selection_text' => __('All tags'),
                    'no_results_text' => __('No tags available to add.'),
                    'add_aria_label' => __('Add tag filter'),
                    'remove_aria_label' => __('Remove tag filter'),
                    'chip_prefix' => '#',
                    'option_prefix' => '#',
                ],
                [
                    'label' => __('Creator'),
                    'input_name' => 'author_ids[]',
                    'items' => $authorsForPicker,
                    'selected' => $selectedAuthorIds,
                    'placeholder' => __('Creator'),
                    'no_selection_text' => __('All creators'),
                    'no_results_text' => __('No creators available to add.'),
                    'add_aria_label' => __('Add creator filter'),
                    'remove_aria_label' => __('Remove creator filter'),
                ],
            ]"
            :reset-url="url()->current()"
        />

        {{-- Content result cards. --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 auto-rows-fr">
            @forelse($content_objects as $content)
                <div class="flex flex-col">
                    <div class="flex-1">
                        <x-services.content.content-card :content="$content" class="{{ request()->routeIs('content.unpublished') ? 'rounded-t-lg rounded-b-none' : '' }}"/>
                    </div>

                    {{-- Publish action is only rendered in unpublished listing mode. --}}
                    @if (request()->routeIs('content.unpublished'))
                        <form id="{{ $content->id }}_publish" hidden action="{{ route('content.publish', ['content_object' => $content]) }}" method="post">
                            @csrf
                            @method('patch')
                        </form>

                        <div class="flex flex-1 items-center justify-center">
                            <x-primary-button form="{{ $content->id }}_publish" class="flex flex-1 justify-center text-white rounded-b-lg! rounded-t-none! shadow border-gray-200! px-2 p-1 z-0">
                                {{ __('Publish') }}
                                <x-icon name="visible" class="w-4 h-4 ml-1"/>
                            </x-primary-button>
                        </div>
                    @endif
                </div>
            @empty
                {{-- Empty state shown when filters or route context return no items. --}}
                <div class="col-span-2 lg:col-span-5 py-12 text-center text-gray-500 bg-white rounded-lg shadow border border-dashed border-gray-300">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No content found') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Get started by creating a new content object.') }}</p>
                    <div class="mt-6">
                        <x-services.create-action
                            href="{{ route('content.create') }}"
                            label="{{ __('Create Content') }}"
                        />
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Preserve active filters during pagination. --}}
        <div class="flex flex-col justify-end grow mt-6">
            {{ $content_objects->withQueryString()->links() }}
        </div>
    </section>
</x-app-layout>
