{{--
    Tag manager partial.
    It is rendered inside the shared light modal shell and keeps its own search, validation, and destructive actions self-contained.
--}}

@php
    /**
     * Scope tag validation feedback to this manager so unrelated forms stay quiet.
     */
    $tagManagerErrors = $errors->getBag('tagManager');
@endphp

<div
    class="flex flex-col gap-6 md:flex-row"
    x-data="{
        searchQuery: '',
        tags: @js($tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->values()),
        matchesTag(tagName) {
            const query = this.searchQuery.toLowerCase().trim();
            return query === '' || tagName.toLowerCase().includes(query);
        },
        filteredTagsCount() {
            return this.tags.filter(tag => this.matchesTag(tag.name)).length;
        },
    }"
>
    {{-- Mobile-friendly validation summary stays near the top of the manager when a save fails. --}}
    @if ($tagManagerErrors->isNotEmpty())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 md:hidden">
            {{ $tagManagerErrors->first() }}
        </div>
    @endif

    {{-- Existing tag list supports inline search and light-mode chip styling. --}}
    <div class="min-w-0 flex-1 rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-lg font-bold text-gray-700">{{ __('Existing Tags') }}</h3>
            <div class="w-full sm:max-w-xs">
                <label for="tag_search" class="sr-only">{{ __('Search tags') }}</label>
                <input
                    id="tag_search"
                    type="text"
                    x-model.debounce.150ms="searchQuery"
                    placeholder="{{ __('Search tags...') }}"
                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                >
            </div>
        </div>

        <div class="max-h-72 overflow-y-auto pr-1 md:max-h-96">
            <div class="flex flex-wrap gap-2">
                @forelse($tags as $tag)
                    {{-- Each tag chip routes destructive confirmation through the shared modal system. --}}
                    <div
                        x-show="matchesTag(@js($tag->name))"
                        x-cloak
                        class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-1 text-sm font-medium text-gray-700 shadow-sm"
                    >
                        <span class="mr-2">#{{ $tag->name }}</span>

                        <form
                            action="{{ route('content.tags.destroy', $tag) }}"
                            method="POST"
                            class="flex"
                            data-confirm-title="{{ __('Delete tag') }}"
                            data-confirm-message="{{ __('Delete tag #:name?', ['name' => $tag->name]) }}"
                            data-confirm-label="{{ __('Delete') }}"
                            data-cancel-label="{{ __('Cancel') }}"
                            data-confirm-variant="danger"
                        >
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return_modal" value="tags">
                            <button type="submit" class="text-gray-400 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2 rounded-md">
                                <x-icon name="trash" class="w-4 h-4" />
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic">{{ __('No tags found.') }}</p>
                @endforelse
            </div>

            @if($tags->isNotEmpty())
                <p x-show="filteredTagsCount() === 0" x-cloak class="mt-3 text-sm text-gray-500 italic">
                    {{ __('No tags match your search.') }}
                </p>
            @endif
        </div>
    </div>

    {{-- Creation panel keeps the form visible beside the searchable list on larger screens. --}}
    <div class="shrink-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:w-72">
        <h3 class="mb-3 text-lg font-bold text-gray-700">{{ __('Add New Tag') }}</h3>

        {{-- Desktop validation summary keeps form feedback near the field on wider screens. --}}
        @if ($tagManagerErrors->isNotEmpty())
            <div class="mb-3 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 md:block">
                {{ $tagManagerErrors->first() }}
            </div>
        @endif

        <form action="{{ route('content.tags.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="return_modal" value="tags">

            <div>
                <label for="tag_name" class="sr-only">{{ __('Tag Name') }}</label>
                <input
                    type="text"
                    name="name"
                    id="tag_name"
                    value="{{ old('name') }}"
                    placeholder="{{ __('Tag Name (e.g. Sports)') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                    required
                >
            </div>

            <x-input-error :messages="$tagManagerErrors->get('name')" class="mt-2" />

            <x-primary-button class="w-full justify-center">
                {{ __('Create Tag') }}
            </x-primary-button>
        </form>
    </div>
</div>
