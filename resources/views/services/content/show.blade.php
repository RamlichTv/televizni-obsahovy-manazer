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

    @php
        $meta = is_array($content->meta) ? $content->meta : json_decode($content->meta, true);
        $fileItems = collect($content->fileItems());
        $totalFileSizeBytes = (int) $fileItems->sum(fn ($file) => (int) ($file['size'] ?? 0));
    @endphp

    {{-- Layout Container --}}
    <div
        class="flex flex-col lg:flex-row h-[calc(100vh-180px)] overflow-hidden"
        x-data="{
            sidebarOpen: true,
            zoomOpen: false,
            zoomImage: '',
            zoomTitle: '',
            zoomScale: 1,
            panX: 0,
            panY: 0,
            isPanning: false,
            pointerStartX: 0,
            pointerStartY: 0,
            panStartX: 0,
            panStartY: 0,
            panHasMoved: false,
            openZoom(imageUrl, imageTitle = '') {
                this.zoomImage = imageUrl;
                this.zoomTitle = imageTitle;
                this.zoomScale = 1;
                this.panX = 0;
                this.panY = 0;
                this.isPanning = false;
                this.panHasMoved = false;
                this.zoomOpen = true;
            },
            closeZoom() {
                this.zoomOpen = false;
                this.zoomImage = '';
                this.zoomTitle = '';
                this.zoomScale = 1;
                this.panX = 0;
                this.panY = 0;
                this.isPanning = false;
                this.panHasMoved = false;
            },
            zoomIn() {
                this.zoomScale = Math.min(4, this.zoomScale + 0.25);
            },
            zoomOut() {
                this.zoomScale = Math.max(1, this.zoomScale - 0.25);
                if (this.zoomScale === 1) {
                    this.panX = 0;
                    this.panY = 0;
                }
            },
            resetZoom() {
                this.zoomScale = 1;
                this.panX = 0;
                this.panY = 0;
            },
            handleZoomWheel(event) {
                if (!this.zoomOpen) {
                    return;
                }

                event.preventDefault();
                if (event.deltaY < 0) {
                    this.zoomIn();
                    return;
                }

                this.zoomOut();
            },
            startPan(event) {
                if (!this.zoomOpen || this.zoomScale <= 1) {
                    return;
                }

                this.isPanning = true;
                this.panHasMoved = false;
                this.pointerStartX = event.clientX;
                this.pointerStartY = event.clientY;
                this.panStartX = this.panX;
                this.panStartY = this.panY;
            },
            continuePan(event) {
                if (!this.isPanning) {
                    return;
                }

                const deltaX = event.clientX - this.pointerStartX;
                const deltaY = event.clientY - this.pointerStartY;
                if (Math.abs(deltaX) > 3 || Math.abs(deltaY) > 3) {
                    this.panHasMoved = true;
                }

                this.panX = this.panStartX + deltaX;
                this.panY = this.panStartY + deltaY;
            },
            stopPan() {
                this.isPanning = false;
            },
            handleZoomCanvasClick() {
                if (this.panHasMoved) {
                    this.panHasMoved = false;
                    return;
                }

                if (this.zoomScale < 4) {
                    this.zoomIn();
                    return;
                }

                this.resetZoom();
            },
            zoomTransformStyle() {
                return 'transform: translate(' + this.panX + 'px, ' + this.panY + 'px) scale(' + this.zoomScale + '); transform-origin: center center;';
            }
        }"
        @keydown.escape.window="closeZoom"
    >
        
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
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-h-75 relative group p-6">
                    @if ($content->type->multiple_file)
                        @if ($fileItems->isEmpty())
                            <div class="flex min-h-48 items-center justify-center text-center text-gray-400">
                                {{ __('No files are available for this content.') }}
                            </div>
                        @else
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ($fileItems as $file)
                                    @php
                                        $itemUrl = (string) ($file['url'] ?? '');
                                        $itemMime = strtolower((string) ($file['mime_type'] ?? ''));
                                        $itemName = (string) ($file['original_filename'] ?? '');
                                        $itemPath = strtolower((string) (parse_url($itemUrl, PHP_URL_PATH) ?: $itemUrl));
                                        $itemIsPdf = $itemUrl !== '' && ($itemMime === 'application/pdf' || str_ends_with($itemPath, '.pdf') || str_ends_with(strtolower($itemName), '.pdf'));
                                    @endphp

                                    <article class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                        @if ($itemUrl !== '' && str_starts_with($itemMime, 'image/'))
                                            <button
                                                type="button"
                                                class="block w-full rounded focus:outline-none focus:ring-2 focus:ring-[#077CB6]"
                                                @click='openZoom(@json($itemUrl), @json($itemName))'
                                                title="{{ __('Click to zoom') }}"
                                            >
                                                <img src="{{ $itemUrl }}" alt="{{ $itemName }}" class="h-48 w-full rounded object-cover hover:opacity-90 transition">
                                            </button>
                                            <p class="mt-1 text-[11px] text-gray-500">{{ __('Click image to zoom') }}</p>
                                        @elseif ($itemUrl !== '' && str_starts_with($itemMime, 'video/'))
                                            <video src="{{ $itemUrl }}" controls class="h-48 w-full rounded object-cover"></video>
                                        @elseif ($itemIsPdf)
                                            <div class="overflow-hidden rounded border border-gray-200 bg-white">
                                                <iframe src="{{ $itemUrl }}" title="{{ $itemName !== '' ? $itemName : __('PDF preview') }}" class="h-64 w-full bg-white"></iframe>
                                            </div>
                                            <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-gray-500">
                                                <span class="rounded-full bg-slate-100 px-2 py-1 font-semibold text-slate-600">PDF</span>
                                                <a href="{{ $itemUrl }}" target="_blank" class="text-[#077CB6] underline">{{ __('Open PDF') }}</a>
                                            </div>
                                        @else
                                            <div class="flex h-48 items-center justify-center rounded border border-dashed border-gray-300 bg-white text-center text-sm text-gray-500">
                                                <a href="{{ $itemUrl }}" target="_blank" class="text-[#077CB6] underline">{{ __('Download file') }}</a>
                                            </div>
                                        @endif

                                        <p class="mt-2 truncate text-xs text-gray-600" title="{{ $itemName }}">
                                            {{ $itemName !== '' ? $itemName : __('Unnamed file') }}
                                        </p>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    @else
                        @php
                            $primaryFile = $fileItems->first();
                            $url = (string) ($primaryFile['url'] ?? '');
                            $mime = strtolower((string) ($primaryFile['mime_type'] ?? ''));
                            $name = (string) ($primaryFile['original_filename'] ?? $content->name);
                            $path = strtolower((string) (parse_url($url, PHP_URL_PATH) ?: $url));
                            $isPdf = $url !== '' && ($mime === 'application/pdf' || str_ends_with($path, '.pdf') || str_ends_with(strtolower($name), '.pdf'));
                        @endphp

                        <div class="flex min-h-75 items-center justify-center">
                            @if ($url !== '' && str_starts_with($mime, 'image/'))
                                <div class="flex flex-col items-center">
                                    <button
                                        type="button"
                                        class="focus:outline-none focus:ring-2 focus:ring-[#077CB6] rounded"
                                        @click='openZoom(@json($url), @json($content->name))'
                                        title="{{ __('Click to zoom') }}"
                                    >
                                        <img src="{{ $url }}" class="max-w-full max-h-150 object-contain shadow-lg rounded hover:opacity-90 transition">
                                    </button>
                                    <p class="mt-2 text-xs text-gray-500">{{ __('Click image to zoom') }}</p>
                                </div>
                            @elseif ($url !== '' && str_starts_with($mime, 'video/'))
                                <video src="{{ $url }}" controls class="max-w-full max-h-150 rounded shadow-lg"></video>
                            @elseif ($isPdf)
                                <div class="w-full">
                                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                                        <iframe src="{{ $url }}" title="{{ $name }}" class="h-[75vh] w-full bg-white"></iframe>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <a href="{{ $url }}" target="_blank" class="text-sm text-[#077CB6] underline">{{ __('Open PDF in new tab') }}</a>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-gray-400 p-12">
                                    <span class="text-6xl block mb-4">📄</span>
                                    <span class="text-lg">{{ __('No preview available for this file type.') }}</span>
                                    @if ($url !== '')
                                        <div class="mt-4">
                                            <a href="{{ $url }}" target="_blank" class="text-[#077CB6] underline">{{ __('Download File') }}</a>
                                        </div>
                                    @endif
                                </div>
                            @endif
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
                                class="flex bg-[#077CB6]/85! hover:bg-[#077CB6]! rounded-full! p-2">
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
                            <dd class="font-medium text-gray-900">{{ $content->type?->name ?? __('Unknown') }}</dd>
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
                                {{ $content->expires_at ? $content->expires_at->format('d.m.Y H:i') : __('Never') }}
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
                        @if ($content->type->is_file)
                            @if ($content->type->multiple_file)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">{{ __('Files') }}</dt>
                                    <dd class="text-gray-900">{{ $fileItems->count() }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('File Size') }}</dt>
                                <dd class="text-gray-900">{{ round($totalFileSizeBytes / 1024 / 1024, 2) }} MB</dd>
                            </div>
                        @endif
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
                            <p class="text-sm text-gray-400 italic">{{ __('No tags assigned.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="zoomOpen"
            x-transition.opacity
            style="display: none;"
            class="fixed inset-0 z-90 bg-black/90"
        >
            <div class="absolute top-4 right-4 z-30 flex items-center gap-2">
                <button type="button" class="rounded bg-white/90 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-white" @click.stop="zoomOut">
                    -
                </button>
                <span class="rounded bg-white/90 px-3 py-2 text-xs font-semibold text-gray-700" x-text="Math.round(zoomScale * 100) + '%'"></span>
                <button type="button" class="rounded bg-white/90 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-white" @click.stop="zoomIn">
                    +
                </button>
                <button type="button" class="rounded bg-white/90 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-white" @click.stop="resetZoom">
                    {{ __('Reset') }}
                </button>
                <button type="button" class="rounded bg-white/90 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-white" @click.stop="closeZoom">
                    {{ __('Close') }}
                </button>
            </div>

            <div class="absolute inset-0 z-10 pt-18 pb-4 px-4" @wheel="handleZoomWheel" @click="closeZoom">
                <div
                    class="h-full w-full flex items-center justify-center overflow-hidden select-none"
                >
                    <div
                        class="max-h-[92vh] max-w-[96vw] flex items-center justify-center"
                        :class="zoomScale > 1 ? (isPanning ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'"
                        @click.stop="handleZoomCanvasClick"
                        @pointerdown.stop="startPan($event)"
                        @pointermove.stop="continuePan($event)"
                        @pointerup.stop="stopPan"
                        @pointerleave.stop="stopPan"
                        @pointercancel.stop="stopPan"
                        @pointerup.window="stopPan"
                    >
                        <img
                            :src="zoomImage"
                            :alt="zoomTitle"
                            class="max-h-[92vh] max-w-[96vw] object-contain transition-transform duration-75 pointer-events-none"
                            :style="zoomTransformStyle()"
                            @dragstart.prevent
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
