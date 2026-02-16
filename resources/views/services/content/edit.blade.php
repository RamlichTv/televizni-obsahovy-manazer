<x-app-layout :title="__('Edit Content')">
    <x-slot name="contentNavTitle">
        {{ __('Editing: ' . $content->name) }}
    </x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="javascript:history.back()" class="text-gray-500 hover:text-gray-700 transition">
                    <x-icon class="size-6 rotate-180" name="arrow"/>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ __('Editing: ' . $content->name) }}
                </h1>
            </div>
            
            {{-- Form submit is handled via form ID or JS; here we keep the button in the sidebar and wrap the whole page with the form. --}}
        </div>
    </x-slot>

    {{-- Wrap the whole page in a form. --}}
    <form method="POST" action="{{ route('content.update', $content) }}" enctype="multipart/form-data" id="edit-form"
          class="flex flex-col lg:flex-row h-[calc(100vh-180px)] overflow-hidden" 
          x-data="{ sidebarOpen: true }">
        
        @csrf
        @method('PATCH')
        
        {{-- MAIN SECTION (Center) --}}
        <div class="flex-1 overflow-y-auto bg-gray-50 p-6 relative">
             <button type="button" @click="sidebarOpen = !sidebarOpen" class="absolute top-4 right-4 z-20 p-2 bg-white rounded shadow text-gray-500 hover:text-[#077CB6] transition">
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
                @php
                    $meta = is_array($content->meta) ? $content->meta : json_decode($content->meta, true);
                    $path = $meta['path'] ?? null;
                    $url = $path ? asset('storage/' . $path) : null;
                    $mime = $meta['mime_type'] ?? '';
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"
                     x-data="{
                        hasNewFile: false,
                        newPreviewType: null,
                        newPreviewUrl: null,
                        newFileName: '',
                        setPreview(event) {
                            const file = event.target.files[0];
                            this.hasNewFile = !!file;

                            if (this.newPreviewUrl) {
                                URL.revokeObjectURL(this.newPreviewUrl);
                                this.newPreviewUrl = null;
                            }

                            if (!file) {
                                this.newPreviewType = null;
                                this.newFileName = '';
                                return;
                            }

                            this.newFileName = file.name;

                            if (file.type.startsWith('image/')) {
                                this.newPreviewType = 'image';
                                this.newPreviewUrl = URL.createObjectURL(file);
                                return;
                            }

                            if (file.type.startsWith('video/')) {
                                this.newPreviewType = 'video';
                                this.newPreviewUrl = URL.createObjectURL(file);
                                return;
                            }

                            this.newPreviewType = 'other';
                        }
                     }">
                    <label class="block text-sm font-medium text-gray-700 mb-4">{{ __('File Content') }}</label>
                    
                    {{-- Current file --}}
                    <div class="mb-6 flex justify-center bg-gray-100 rounded-lg p-4 border border-dashed border-gray-300">
                        <div x-show="!hasNewFile">
                            @if($url && str_contains($mime, 'image'))
                                <img src="{{ $url }}" class="max-h-64 rounded shadow-sm">
                            @elseif($url && str_contains($mime, 'video'))
                                <video src="{{ $url }}" controls class="max-h-64 rounded shadow-sm"></video>
                            @else
                                <span class="text-gray-500">{{ __('Current file:') }} {{ $meta['original_filename'] ?? 'Unknown' }}</span>
                            @endif
                        </div>
                        <div x-show="hasNewFile" class="text-center">
                            <template x-if="newPreviewType === 'image'">
                                <img :src="newPreviewUrl" class="max-h-64 rounded shadow-sm">
                            </template>
                            <template x-if="newPreviewType === 'video'">
                                <video :src="newPreviewUrl" controls class="max-h-64 rounded shadow-sm"></video>
                            </template>
                            <template x-if="newPreviewType === 'other'">
                                <span class="text-gray-500">{{ __('Selected file:') }} <span x-text="newFileName"></span></span>
                            </template>
                        </div>
                    </div>

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
                        <span class="sr-only">{{ __('Choose new file') }}</span>
                        <input type="file" name="file_upload" accept="{{ $accept }}" class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-[#077CB6] file:text-white
                            file:cursor-pointer hover:file:bg-[#066a9c]
                        "
                        @change="setPreview($event)"/>
                    </label>
                    <p class="mt-2 text-xs text-gray-500">{{ __('Upload new file to replace the current one. Leave empty to keep current.') }}</p>
                    @error('file_upload') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                @endif

                {{-- 3. Description --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Description') }}</label>
                    <textarea name="description" id="description" rows="5" 
                              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#5EB4EE] focus:border-[#5EB4EE]">{{ old('description', $content->description) }}</textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
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
        <div class="w-full lg:w-80 h-[50%] lg:h-full bg-white border-t lg:border-t-0 border-l border-gray-200 overflow-y-auto transition-all duration-300"
             :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:hidden'"
             x-show="sidebarOpen">
            
            <div class="p-6 space-y-6">

                <hr class="border-gray-100">
                
                {{-- Author --}}
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ __('Author') }}</h4>
                    <div class="flex items-center gap-3">
                         @if ($content->author && Storage::disk('public')->exists('users/' . $content->author->username . '/' . $content->author->avatar))
                            <img src="{{ asset('storage/users/' . $content->author->username . '/' . $content->author->avatar) }}" class="w-10 h-10 rounded-full object-cover shadow-sm">
                        @else
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-lg">
                                {{ substr($content->author->name ?? '?', 0, 1) }}
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
                            <dt class="text-gray-500">{{ __('Allowed extensions') }}</dt>
                            <dd class="max-w-32 truncate font-medium text-gray-900">{{ implode(', ', $content->type->extension_whitelist ?? []) }}</dd>
                        </div>
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
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('File Size') }}</dt>
                            <dd class="text-gray-900">{{ round(($meta['size'] ?? 0) / 1024 / 1024, 2) }} MB</dd>
                        </div>
                    </dl>
                </div>

                {{-- Settings --}}
                <div class="space-y-4">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700">{{ __('Priority (0-20)') }}</label>
                        <input type="number" name="priority" id="priority" min="0" max="20" value="{{ old('priority', $content->priority) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#5EB4EE] focus:border-[#5EB4EE]">
                        @error('priority') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    @if (!$content->published_at || $content->published_at > date('Y-m-d H:i:s'))
                    <div>
                        <label for="published_at" class="block text-sm font-medium text-gray-700">{{  __('Publishing Date') }}</label>
                        <x-text-input type="datetime-local" name="published_at" id="published_at" value="{{ $content->published_at ? $content->published_at->format('Y-m-d\TH:i') : '' }}" class="w-full"/>
                    </div>
                    @endif
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700">{{ __('Expiration Date') }}</label>
                        <x-text-input type="datetime-local" name="expires_at" id="expires_at" value="{{ $content->expires_at ? $content->expires_at->format('Y-m-d\TH:i') : '' }}" class="w-full"/>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Tags --}}
                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tags') }}</label>
                    <select name="tags[]" id="tags" multiple class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#5EB4EE] focus:border-[#5EB4EE] h-40">
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" {{ $content->tags->contains($tag->id) ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">{{ __('Hold Ctrl (Cmd) to select multiple.') }}</p>
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
