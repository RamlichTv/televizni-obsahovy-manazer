<x-app-layout :title="$content->name">
    <x-slot name="contentNavTitle">
        {{ __('Showing content') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="javascript:history.back()" class="text-gray-500 hover:text-gray-700 transition">
                    <x-icon class="size-6 rotate-180" name="arrow"/>
                </a>
                <h1 class="text-2xl font-bold text-gray-800 truncate max-w-xl">
                    {{ __('Showing: ') . $content->name }}
                </h1>
                
                {{-- Status Badge --}}
                @php
                    $status = App\Models\ContentStatus::find($content->status_id)->name;
                @endphp
                @if($content->status_id == 1)
                    <span class="bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1 rounded-full">{{ $status }}</span>
                @elseif($content->status_id == 2)
                    <span class="bg-green-100 text-green-800 text-sm font-semibold px-3 py-1 rounded-full">{{ $status }}</span>
                @elseif($content->status_id == 3)
                    <span class="bg-gray-100 text-gray-800 text-sm font-semibold px-3 py-1 rounded-full">{{ $status }}</span>
                @elseif($content->status_id == 4)
                    <span class="bg-red-100 text-red-800 text-sm font-semibold px-3 py-1 rounded-full">{{ $status }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    {{-- Layout Container --}}
    <div class="flex flex-col lg:flex-row h-[calc(100vh-180px)] overflow-hidden" x-data="{ sidebarOpen: true }">
        
        {{-- MAIN SECTION (Center) --}}
        <div class="flex-1 overflow-y-auto bg-gray-50 p-6 relative">
            
            {{-- Toggle Sidebar Button (Floating) --}}
            <button @click="sidebarOpen = !sidebarOpen" class="fixed top-20 right-4 z-20 p-2 bg-white rounded shadow-md text-gray-500 hover:text-[#077CB6] transition" title="Toggle Sidebar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>

            <div class="max-w-4xl mx-auto space-y-8">
                {{-- Render file preview only for file-based content types. --}}
                @if ($content->type->is_file)
                    
                {{-- Content preview --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-h-75 flex items-center justify-center relative group">
                    {{-- Resolve storage metadata used by preview and file details. --}}
                    @php
                        $meta = is_array($content->meta) ? $content->meta : json_decode($content->meta, true);
                        $path = $meta['path'] ?? null;
                        $url = $path ? asset('storage/' . $path) : null;
                        $mime = $meta['mime_type'] ?? '';
                    @endphp

                    @if($url && str_contains($mime, 'image'))
                        <img src="{{ $url }}" class="max-w-full max-h-150 object-contain shadow-lg rounded">
                    @elseif($url && str_contains($mime, 'video'))
                        <video src="{{ $url }}" controls class="max-w-full max-h-150 rounded shadow-lg"></video>
                    @else
                        <div class="text-center text-gray-400 p-12">
                            <span class="text-6xl block mb-4">📄</span>
                            <span class="text-lg">No preview available for this file type.</span>
                            <div class="mt-4">
                                <a href="{{ $url }}" target="_blank" class="text-[#077CB6] underline">Download File</a>
                            </div>
                        </div>
                    @endif
                </div>

                @endif

                {{-- Description --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">{{ __('Description') }}</h3>
                    @if($content->description)
                        <div class="prose max-w-none text-gray-600">
                            {{ $content->description }}
                        </div>
                    @else
                        <p class="text-gray-400 italic">{{ __('No description provided.') }}</p>
                    @endif
                </div>

                {{-- Context-aware content actions (publish, unpublish, archive, edit). --}}
                @if (!$content->deleted_at)
                <div class="flex justify-between items-center gap-2">
                    @can('publish_content')
                        @if ($content->status_id === 1)
                        <form id="{{ $content->id }}_publish" hidden action="{{ route('content.publish', ['content_object' => $content]) }}" method="post">
                            @csrf
                            @method('patch')
                        </form>
                        <div class="flex justify-center">
                            <x-primary-button form="{{ $content->id }}_publish"
                                class="flex bg-lime-600 hover:bg-lime-500 rounded-full! p-2">
                                {{ __('Publish') }}
                                <x-icon name="visible" class="size-5 ml-1 font-semibold"/>
                            </x-primary-button>
                        </div>
                        @elseif($content->status_id === 2)
                        <form id="{{ $content->id }}_unpublish" hidden action="{{ route('content.unpublish', ['content_object' => $content]) }}" method="post">
                            @csrf
                            @method('patch')
                        </form>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <x-primary-button form="{{ $content->id }}_unpublish"
                                    class="flex bg-amber-600 hover:bg-amber-500 rounded-full! p-2">
                                    {{ __('Unpublish') }}
                                    <x-icon name="invisible" class="size-5 ml-1 font-semibold"/>
                                </x-primary-button>
                            </div>
                            @can('manage_archived')
                            <form id="{{ $content->id }}_archive" hidden action="{{ route('content.archive', ['content_object' => $content]) }}" method="post">
                                @csrf
                                @method('patch')
                            </form>
                            <div class="flex-1">
                                <button form="{{ $content->id }}_archive" type="submit"
                                    class="inline-flex items-center px-4 py-2 font-semibold text-xs uppercase tracking-widest focus:bg-[#D78500] active:bg-[#D78500] focus:outline-none focus:ring-2 focus:ring-[#D78500] 
                                    focus:ring-offset-2 transition ease-in-out duration-150 bg-yellow-300 hover:bg-yellow-400 focus:text-white active:text-white border-yellow-500 border-2 rounded-full p-2">
                                    {{ __('Archive') }}
                                    <x-icon name="archive-down" class="size-5 ml-1 font-semibold"/>
                                </button>
                            </div>
                            @endcan
                        </div>
                        @elseif ($content->status_id === 3)
                            @can('manage_archived')
                                <form id="{{ $content->id }}_unarchive" hidden action="{{ route('content.unarchive', ['content_object' => $content]) }}" method="post">
                                    @csrf
                                    @method('patch')
                                </form>
                                <div class="flex justify-center">
                                    <button form="{{ $content->id }}_unarchive" type="submit"
                                        class="inline-flex items-center px-4 py-2 font-semibold text-xs uppercase tracking-widest focus:bg-[#D78500] active:bg-[#D78500] focus:outline-none focus:ring-2 focus:ring-[#D78500] 
                                            focus:ring-offset-2 transition ease-in-out duration-150 bg-yellow-300 hover:bg-yellow-400 focus:text-white active:text-white border-yellow-500 border-2 rounded-full p-2">
                                        {{ __('Unarchive') }}
                                        <x-icon name="archive-up" class="size-5 ml-1 font-semibold"/>
                                    </button>
                                </div>
                            @endcan
                        @endif
                    @endcan

                    @can('update', $content)
                        @if ($content->status_id !== 3)
                        <x-primary-link href="{{ route('content.edit', $content) }}" class="p-2! rounded-full!">
                            <x-icon name="pen" class="size-5 mr-2"/>
                            {{ __('Edit') }}
                        </x-primary-link>
                        @endif
                    @endcan
                </div>
                @endif
            </div>
        </div>

        {{-- SIDEBAR (Right) --}}
        <div class="w-full lg:w-80 h-[40%] lg:h-full bg-white border-l border-gray-200 overflow-y-auto transition-all duration-300 ease-in-out"
             :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:hidden'"
             x-show="sidebarOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-10"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-10">
            
            <div class="p-6 space-y-6">
                
                {{-- Author --}}
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ __('Author') }}</h4>
                    <div class="flex items-center gap-3">
                         @if ($content->author)
                            <img src="{{ $content->author->avatarUrl() }}" class="w-10 h-10 rounded-full object-cover shadow-sm">
                        @else
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-lg">
                                ?
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $content->author->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500">{{ $content->author->email ?? '' }}</p>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Metadata --}}
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ __('Information') }}</h4>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('Type') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $content->type->name ?? 'Unknown' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('Priority') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $content->priority }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('Created') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $content->created_at->format('d.m.Y H:i') }}</dd>
                        </div>
                        @if ($content->published_at)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ $content->published_at->isPast() ? __('Published at') : __('Publishing at') }}</dt>
                                <dd class="font-medium text-gray-900">{{ $content->published_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        @endif
                        @if (!$content->expires_at?->isPast())
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('Expires') }}</dt>
                            <dd class="font-medium text-gray-900">
                                {{ $content->expires_at ? $content->expires_at->format('d.m.Y H:i') : 'Never' }}
                            </dd>
                        </div>
                        @endif
                        @if ($content->archived_at)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('Archived') }}</dt>
                                <dd class="font-medium text-orange-500">{{ $content->archived_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        @endif
                        @if ($content->deleted_at)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 font-semibold">{{ __('Deleted') }}</dt>
                                <dd class="font-medium text-red-600">{{ $content->deleted_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        @endif
                         <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('File Size') }}</dt>
                            <dd class="text-gray-900">{{ round(($meta['size'] ?? 0) / 1024 / 1024, 2) }} MB</dd>
                        </div>
                    </dl>
                </div>

                <hr class="border-gray-100">

                {{-- Tags --}}
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ __('Tags') }}</h4>
                    <div class="flex flex-wrap gap-2">
                        @forelse($content->tags as $tag)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-indigo-50 text-[#077CB6]">
                                #{{ $tag->name }}
                            </span>
                        @empty
                            <p class="text-sm text-gray-400 italic">No tags assigned.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
