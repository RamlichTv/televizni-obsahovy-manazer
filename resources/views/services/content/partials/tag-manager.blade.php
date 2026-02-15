<div class="flex flex-col md:flex-row gap-6">
    {{-- List of existing tags --}}
    <div class="flex-1">
        <h3 class="text-lg font-bold text-gray-700 mb-3">{{ __('Existing Tags') }}</h3>
        <div class="flex flex-wrap gap-2">
            @forelse($tags as $tag)
                <div class="inline-flex items-center align-middle px-3 py-1 rounded-full text-sm font-medium bg-white border border-gray-300 shadow-sm text-gray-700">
                    <span class="mr-2">#{{ $tag->name }}</span>
                    <form action="{{ route('content.tags.destroy', $tag) }}" method="POST" class="flex" onsubmit="return confirm('Delete tag?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-500 focus:outline-none">
                            <x-icon name="trash" class="w-4 h-4" />
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500 italic">{{ __('No tags found.') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Form for adding a new tag --}}
    <div class="w-full md:w-1/3 border-l pl-6 border-gray-200">
        <h3 class="text-lg font-bold text-gray-700 mb-3">{{ __('Add New Tag') }}</h3>
        <form action="{{ route('content.tags.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label for="tag_name" class="sr-only">{{ __('Tag Name') }}</label>
                <input type="text" name="name" id="tag_name" placeholder="Tag Name (e.g. Sports)" 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]" required>
            </div>
            <x-primary-button class="w-full justify-center">
                {{ __('Create Tag') }}
            </x-primary-button>
        </form>
    </div>
</div>
