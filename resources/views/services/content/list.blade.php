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

    <section class="p-4 max-w-screen-2xl w-full mx-auto">
        
        {{-- Filters (form) --}}
        <div x-data="{ showFilters: false }" class="mb-6 bg-white rounded-lg shadow p-4">
            <div class="flex justify-between items-center cursor-pointer" @click="showFilters = !showFilters">
                <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                    {{-- Filter icon --}}
                    <x-icon name="filter" class="w-4 h-4"/>
                    {{ __('Filters') }}
                </h3>
                <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{'rotate-180': showFilters}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>

            <form method="GET" x-show="showFilters" x-transition class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Content type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Type') }}</label>
                    <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE] sm:text-sm">
                        <option value="">{{ __('All Types') }}</option>
                        @foreach(\App\Models\ContentType::all() as $type)
                            <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tags --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Tag') }}</label>
                    <select name="tag" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE] sm:text-sm">
                        <option value="">{{ __('All Tags') }}</option>
                        @foreach(\App\Models\Tag::all() as $tag)
                            <option value="{{ $tag->id }}" {{ request('tag') == $tag->id ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Author --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Creator') }}</label>
                    <select name="author" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE] sm:text-sm">
                        <option value="">{{ __('All Creators') }}</option>
                        @foreach(\App\Models\User::has('content_objects')->get() as $user)
                            <option value="{{ $user->id }}" {{ request('author') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full bg-[#077CB6] hover:bg-[#066a9c] text-white font-bold py-2 px-4 rounded transition">
                        {{ __('Apply Filters') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- GRID LAYOUT --}}
        {{-- Mobile: 2 col (grid-cols-2), Desktop LG: 5 col (lg:grid-cols-5) --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 auto-rows-fr">
            @forelse($content_objects as $content)
                <div class="flex flex-col gap-1">
                    <div class="flex-1">
                        <x-services.content.content-card :content="$content"/>
                    </div>
                    @if (request()->routeIs('content.unpublished'))
                    <form id="{{ $content->id }}_publish" hidden action="{{ route('content.publish', ['content_object' => $content]) }}" method="post">
                        @csrf
                        @method('patch')
                    </form>
                    <div class="flex items-center justify-center">
                        <x-primary-button form="{{ $content->id }}_publish" 
                            class="flex bg-lime-600 hover:bg-lime-500 text-white !rounded-full px-2 p-1">
                            {{ __('Publish') }}
                            <x-icon name="visible" class="w-4 h-4 ml-1"/>
                        </x-primary-button>
                    </div>
                    @endif
                </div>
            @empty
                <div class="col-span-2 lg:col-span-5 py-12 text-center text-gray-500 bg-white rounded-lg shadow border border-dashed border-gray-300">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No content found') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Get started by creating a new content object.') }}</p>
                    <div class="mt-6">
                        <x-primary-link href="{{ route('content.create') }}">
                            {{ __('Create Content') }}
                        </x-primary-link>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $content_objects->withQueryString()->links() }}
        </div>
        
    </section>
</x-app-layout>
