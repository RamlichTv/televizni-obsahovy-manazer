<x-app-layout :title="__('Edit Content')">
    <x-slot name="contentNavTitle">
        {{ __('Editing content') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between w-full">
            <div class="flex items-center gap-3">
                <x-secondary-link href="{{ route('content.show', $content) }}">
                    <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                    {{ __('Back to Content') }}
                </x-secondary-link>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ __('Editing: ' . $content->name) }}
                </h1>
            </div>
            
            {{-- Form submit is handled via form ID or JS; here we keep the button in the sidebar and wrap the whole page with the form. --}}
        </div>
    </x-slot>

    {{-- Build picker data and restore selected tags when validation fails. --}}
    @php
        $tagsForPicker = collect($tags ?? [])
            ->map(fn ($tag) => [
                'id' => (string) $tag->id,
                'name' => $tag->name,
            ])
            ->values();

        $selectedTagIds = collect((array) old('tags', $content->tags->pluck('id')->all()))
            ->map(fn ($id) => (string) $id)
            ->values();
        $meta = is_array($content->meta) ? $content->meta : json_decode($content->meta, true);
        $fileItems = collect($content->fileItems());
        $totalFileSizeBytes = (int) $fileItems->sum(fn ($file) => (int) ($file['size'] ?? 0));
    @endphp

    {{-- Wrap the whole page in a form so sidebar controls can submit the same payload. --}}
    <form method="POST" action="{{ route('content.update', $content) }}" enctype="multipart/form-data" id="edit-form"
          class="flex flex-col lg:flex-row h-[calc(100vh-180px)] overflow-hidden" 
          x-data="{
              // Controls right sidebar visibility on both desktop and mobile layouts.
              sidebarOpen: true
          }">
        
        @csrf
        @method('PATCH')
        
        {{-- MAIN SECTION (Center) --}}
        <div class="flex-1 overflow-y-auto bg-gray-50 p-6 relative">
            <button type="button" @click="sidebarOpen = !sidebarOpen" class="fixed top-20 right-4 z-20 p-2 bg-white rounded shadow-md text-gray-500 hover:text-[#077CB6] transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>

            <div class="max-w-4xl mx-auto space-y-8">
                
                {{-- 1. Name --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Content Name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $content->name) }}" 
                           class="block w-full text-lg font-bold border-gray-300 rounded-md shadow-sm focus:ring-[#5EB4EE] focus:border-[#5EB4EE]">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                {{-- 2. Preview & upload --}}
                @if ($content->type->is_file)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"
                     x-data="{
                        hasNewFile: false,
                        newFileLabel: '',
                        newPreviewItems: [],
                        selectedNonPreviewCount: 0,
                        previewKind(file) {
                            const lowerName = (file.name || '').toLowerCase();

                            if (file.type.startsWith('image/')) {
                                return 'image';
                            }

                            if (file.type === 'application/pdf' || lowerName.endsWith('.pdf')) {
                                return 'pdf';
                            }

                            return null;
                        },
                        fileKindLabel(file) {
                            if (file.type.startsWith('image/')) {
                                return 'Image';
                            }

                            if (file.type === 'application/pdf' || (file.name || '').toLowerCase().endsWith('.pdf')) {
                                return 'PDF';
                            }

                            if (file.type.startsWith('video/')) {
                                return 'Video';
                            }

                            const extension = (file.name || '').split('.').pop();
                            return extension ? extension.toUpperCase() : 'File';
                        },
                        clearPreviews() {
                            this.newPreviewItems.forEach(preview => {
                                if (preview.previewUrl) {
                                    URL.revokeObjectURL(preview.previewUrl);
                                }
                            });
                            this.newPreviewItems = [];
                        },
                        applySelectedFiles(files) {
                            this.clearPreviews();
                            this.hasNewFile = files.length > 0;
                            this.newFileLabel = files.length > 1 ? `${files.length} files selected` : (files[0]?.name || '');
                            this.newPreviewItems = files.map(file => {
                                const previewKind = this.previewKind(file);

                                return {
                                    name: file.name,
                                    previewKind,
                                    previewUrl: previewKind ? URL.createObjectURL(file) : null,
                                    kindLabel: this.fileKindLabel(file),
                                };
                            });
                            this.selectedNonPreviewCount = this.newPreviewItems.filter(file => !file.previewKind).length;
                        },
                        setPreview(event) {
                            const files = Array.from(event.target.files || []);
                            this.applySelectedFiles(files);
                        }
                     }">
                    <label class="block text-sm font-medium text-gray-700 mb-4">{{ __('File Content') }}</label>
                    
                    {{-- Current file --}}
                    <div class="mb-6 bg-gray-100 rounded-lg p-4 border border-dashed border-gray-300">
                        <div x-show="!hasNewFile">
                            @if ($content->type->multiple_file)
                                @if ($fileItems->isEmpty())
                                    <div class="flex min-h-32 items-center justify-center text-sm text-gray-500">
                                        {{ __('No files are available for this content.') }}
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        @foreach ($fileItems as $file)
                                            @php
                                                $itemUrl = (string) ($file['url'] ?? '');
                                                $itemMime = strtolower((string) ($file['mime_type'] ?? ''));
                                                $itemName = (string) ($file['original_filename'] ?? '');
                                                $itemPath = strtolower((string) (parse_url($itemUrl, PHP_URL_PATH) ?: $itemUrl));
                                                $itemIsPdf = $itemUrl !== '' && ($itemMime === 'application/pdf' || str_ends_with($itemPath, '.pdf') || str_ends_with(strtolower($itemName), '.pdf'));
                                            @endphp
                                            <article class="rounded-lg border border-gray-200 bg-white p-2">
                                                @if ($itemUrl !== '' && str_starts_with($itemMime, 'image/'))
                                                    <img src="{{ $itemUrl }}" alt="{{ $itemName }}" class="h-32 w-full rounded object-cover">
                                                @elseif ($itemUrl !== '' && str_starts_with($itemMime, 'video/'))
                                                    <video src="{{ $itemUrl }}" controls class="h-32 w-full rounded object-cover"></video>
                                                @elseif ($itemIsPdf)
                                                    <div class="overflow-hidden rounded border border-gray-200 bg-white">
                                                        <iframe src="{{ $itemUrl }}" title="{{ $itemName !== '' ? $itemName : __('PDF preview') }}" class="h-40 w-full bg-white"></iframe>
                                                    </div>
                                                @else
                                                    <div class="flex h-32 items-center justify-center rounded border border-dashed border-gray-300 text-xs text-gray-500">
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
                                    $name = (string) ($primaryFile['original_filename'] ?? 'Unknown');
                                    $path = strtolower((string) (parse_url($url, PHP_URL_PATH) ?: $url));
                                    $isPdf = $url !== '' && ($mime === 'application/pdf' || str_ends_with($path, '.pdf') || str_ends_with(strtolower($name), '.pdf'));
                                @endphp
                                <div class="flex justify-center">
                                    @if($url !== '' && str_starts_with($mime, 'image/'))
                                        <img src="{{ $url }}" class="max-h-64 rounded shadow-sm">
                                    @elseif($url !== '' && str_starts_with($mime, 'video/'))
                                        <video src="{{ $url }}" controls class="max-h-64 rounded shadow-sm"></video>
                                    @elseif($isPdf)
                                        <div class="w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                                            <iframe src="{{ $url }}" title="{{ $name }}" class="h-128 w-full bg-white"></iframe>
                                        </div>
                                    @else
                                        <div class="text-center text-gray-500">
                                            <span>{{ __('Current file:') }} {{ $name }}</span>
                                            @if ($url !== '')
                                                <div class="mt-2">
                                                    <a href="{{ $url }}" target="_blank" class="text-[#077CB6] underline">{{ __('Download file') }}</a>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div x-show="hasNewFile" class="text-center">
                            <template x-if="newPreviewItems.length > 0">
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <template x-for="preview in newPreviewItems.filter(file => file.previewKind)" :key="preview.previewUrl">
                                        <div class="overflow-hidden rounded border border-gray-200 bg-white shadow-sm">
                                            <template x-if="preview.previewKind === 'image'">
                                                <img :src="preview.previewUrl" :alt="preview.name" class="h-32 w-full object-cover">
                                            </template>

                                            <template x-if="preview.previewKind === 'pdf'">
                                                <iframe :src="preview.previewUrl" :title="preview.name" class="h-40 w-full bg-white"></iframe>
                                            </template>

                                            <div class="border-t border-gray-100 px-3 py-2 text-left">
                                                <p class="truncate text-xs font-medium text-gray-700" x-text="preview.name"></p>
                                                <p class="text-[11px] uppercase tracking-wide text-gray-400" x-text="preview.kindLabel"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="newPreviewItems.filter(file => file.previewKind).length === 0">
                                <span class="text-gray-500" x-text="newFileLabel || '{{ __('Selected files') }}'"></span>
                            </template>
                        </div>
                    </div>
                    <p x-show="hasNewFile && newFileLabel" class="mb-2 text-xs text-gray-500" x-text="newFileLabel"></p>
                    <template x-if="hasNewFile && selectedNonPreviewCount > 0">
                        <p class="mb-3 text-xs text-gray-500" x-text="`${selectedNonPreviewCount} file(s) without preview selected`"></p>
                    </template>

                    {{-- Upload new file --}}
                    <label class="block">
                        @php
                            $accept = collect($content->type->extension_whitelist ?? [])
                                ->map(function ($value) {
                                    // If it's a MIME type or wildcard, return as-is
                                    if (str_contains($value, '/')) {
                                        return $value;
                                    }

                                    // Otherwise treat as extension
                                    return '.' . ltrim($value, '.');
                                })
                                ->implode(',');
                        @endphp
                        <span class="sr-only">{{ $content->type->multiple_file ? __('Choose new files') : __('Choose new file') }}</span>
                        <input type="file" name="{{ $content->type->multiple_file ? 'file_upload[]' : 'file_upload' }}" accept="{{ $accept }}" @if ($content->type->multiple_file) multiple @endif class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-[#077CB6] file:text-white
                            file:cursor-pointer hover:file:bg-[#066a9c]
                        "
                        @change="setPreview($event)"/>
                    </label>
                    <p class="mt-2 text-xs text-gray-500">
                        @if ($content->type->multiple_file)
                            {{ __('Upload new files to replace the current set. Leave empty to keep current files.') }}
                        @else
                            {{ __('Upload new file to replace the current one. Leave empty to keep current.') }}
                        @endif
                    </p>
                    @error('file_upload') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    @error('file_upload.*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                @endif

                {{-- 3. Description --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Description') }}</label>
                    <textarea name="description" id="description" rows="5" 
                              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#5EB4EE] focus:border-[#5EB4EE]">{{ old('description', $content->description) }}</textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
                {{-- Validation summary to surface all backend errors at once. --}}
                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <strong class="font-bold">{{ __('Validation Error!') }}</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
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

                <hr class="border-gray-100">
                
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
                        @if ($content->type->is_file)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('Allowed extensions') }}</dt>
                            <dd class="max-w-32 truncate font-medium text-gray-900">{{ implode(', ', $content->type->extension_whitelist ?? []) }}</dd>
                        </div>
                        @if ($content->type->multiple_file)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('Files') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $fileItems->count() }}</dd>
                        </div>
                        @endif
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('Created') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $content->created_at->format('d.m.Y') }}</dd>
                        </div>
                        @if ($content->deleted_at)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 font-semibold">{{ __('Deleted') }}</dt>
                                <dd class="font-medium text-red-600">{{ $content->deleted_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        @endif
                        @if ($content->type->is_file)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('File Size') }}</dt>
                                <dd class="text-gray-900">{{ round($totalFileSizeBytes / 1024 / 1024, 2) }} MB</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Editable publishing and scheduling settings. --}}
                <div class="space-y-4">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700">{{ __('Priority (0-20)') }}</label>
                        <input type="number" name="priority" id="priority" min="0" max="20" value="{{ old('priority', $content->priority) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#5EB4EE] focus:border-[#5EB4EE]">
                        @error('priority') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    @can('publish_content')
                        @if (!$content->published_at || $content->published_at > date('Y-m-d H:i:s'))
                        <div>
                            <label for="published_at" class="block text-sm font-medium text-gray-700">{{  __('Publishing Date') }}</label>
                            <x-text-input type="datetime-local" name="published_at" id="published_at" value="{{ $content->published_at ? $content->published_at->format('Y-m-d\TH:i') : '' }}" class="w-full"/>
                        </div>
                        @endif
                    @endcan
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700">{{ __('Expiration Date') }}</label>
                        <x-text-input type="datetime-local" name="expires_at" id="expires_at" value="{{ $content->expires_at ? $content->expires_at->format('Y-m-d\TH:i') : '' }}" class="w-full"/>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Tags --}}
                <div>
                    <x-multi-select-picker
                        :label="__('Tags')"
                        input-name="tags[]"
                        :items="$tagsForPicker"
                        :selected="$selectedTagIds"
                        :placeholder="__('Tag')"
                        :no-results-text="__('No tags available to add.')"
                        :add-aria-label="__('Add tag')"
                        :remove-aria-label="__('Remove tag')"
                        chip-prefix="#"
                        option-prefix="#"
                        panel-position="up"
                    />
                    @error('tags') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    @error('tags.*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <hr class="border-gray-100">
                
                {{-- Actions --}}
                <div class="flex flex-1 gap-2">
                    <x-primary-button class="flex-1 justify-center py-2">
                        {{ __('Save') }}
                    </x-primary-button>
                    <x-secondary-link href="{{ route('content.show', $content) }}" class="flex-1 justify-center py-2">
                        {{ __('Cancel') }}
                    </x-secondary-link>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
