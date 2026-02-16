<x-app-layout title="{{ __('Create Content') }}">
    <x-slot name="contentNavTitle">
        {{ __('Create') }}
    </x-slot>

    <div class="py-12" x-data="{ 
        selectedType: '',
        isFile: false,
        types: {{ \App\Models\ContentType::all()->mapWithKeys(fn($i) => [$i->id => $i])->toJson() }},
        fileName: null,
        filePreview: null,
        
        updateFileInput(event) {
            const file = event.target.files[0];
            this.fileName = file ? file.name : null;
            
            // If it is an image, show a preview.
            if (file && (file.type.includes('image'))) {
                const reader = new FileReader();
                reader.onload = (e) => this.filePreview = e.target.result;
                reader.readAsDataURL(file);
            } else {
                this.filePreview = null;
            }
        },
        
        get currentType() {
            return this.types[this.selectedType] || {};
        },

        get acceptTypes() {
            let rules = this.currentType.extension_whitelist;

            if (!rules) return '';

            // If coming as JSON string, parse it
            if (typeof rules === 'string') {
                try {
                    rules = JSON.parse(rules);
                } catch {
                    return '';
                }
            }

            if (!Array.isArray(rules)) return '';

            return rules.map(value => {
                // If it looks like a MIME type (contains slash), return as-is
                if (value.includes('/')) {
                    return value;
                }

                // Otherwise treat it as extension
                return '.' + value.replace(/^\./, '');})
                .join(',');
            }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <form action="{{ route('content.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <!-- 1. Content type and upload -->
                        <div class="flex flex-col md:flex-row gap-6 md:gap-8">
                            <!-- Type selection -->
                            <div class="flex-1">
                                <x-input-label for="type_id" :value="__('Content Type') . '*'" />
                                <x-select 
                                    name="type_id" 
                                    id="type_id" 
                                    x-model="selectedType"
                                    :required="true"
                                >
                                    <option value="" disabled>{{ __('Select type...') }}</option>
                                    @foreach(\App\Models\ContentType::all() as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </x-select>
                                <x-input-error class="mt-2" :messages="$errors->get('type_id')" />
                            </div>

                            <!-- File upload (shown only after type selection) -->
                            <div x-show="selectedType && currentType.is_file" class="flex-1" x-transition.opacity>
                                <x-input-label for="file_upload" x-text="'Upload ' + (currentType.name || 'File') + '*'" />
                                
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative hover:bg-gray-50 transition">
                                    <div class="space-y-1 text-center">
                                        <!-- Image preview -->
                                        <template x-if="filePreview">
                                            <img :src="filePreview" class="mx-auto h-32 object-contain mb-2 rounded">
                                        </template>
                                        
                                        <!-- Icon (when there is no preview) -->
                                        <template x-if="!filePreview">
                                            <x-icon name="upload-picture" class="mx-auto h-12 w-12 text-gray-400" />
                                        </template>

                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="file_upload" class="relative cursor-pointer bg-white rounded-md font-medium text-[#077CB6] hover:text-[#E69C24] focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-[#E69C24]">
                                                <span>{{ __('Upload a file') }}</span>
                                                <input id="file_upload" name="file_upload" type="file" class="sr-only" 
                                                       @change="updateFileInput" 
                                                       {{-- This does't work --}}
                                                       :accept="acceptTypes" 
                                                       >
                                            </label>
                                            <p class="pl-1">{{ __('or drag and drop')}}</p>
                                        </div>
                                        <template x-if="currentType.is_file">
                                            <p class="text-xs text-gray-500" x-text="'Allowed: ' + currentType.extension_whitelist"></p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Row: Name and Priority -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-3">
                                <x-input-label for="name" :value="__('Content Name') . '*'" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="priority" :value="__('Priority (0-20)')" />
                                <x-text-input id="priority" name="priority" type="number" min="0" max="20" class="mt-1 block w-full" :value="old('priority', 0)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('priority')" />
                            </div>
                        </div>

                        <!-- 3. Description -->
                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <x-textarea 
                                id="description" 
                                name="description" 
                                rows="3"
                            >{{ old('description') }}</x-textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <!-- 4. Tags, Expiration and Publishment -->
                        <div class="grid items-center grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="tags" :value="__('Tags')" />
                                <x-select class="h-full" name="tags[]" id="tags" multiple>
                                    @foreach(\App\Models\Tag::all() as $tag)
                                        <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', [])) ? 'selected' : '' }}>
                                            {{ $tag->name }}
                                        </option>
                                    @endforeach
                                </x-select>
                                <p class="text-xs text-gray-500 mt-1">{{ __('Hold CTRL (CMD) to select multiple') }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get('tags')" />
                            </div>

                            <div class="flex flex-col gap-2">
                                @can('publish_content')
                                <div>
                                    <x-input-label for="published_at" :value="__('Publish Date')" />
                                    <x-text-input id="published_at" name="published_at" type="datetime-local" class="mt-1 block w-full" :value="old('published_at')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('published_at')" />
                                </div>
                                @endcan
                                <div>
                                    <x-input-label for="expires_at" :value="__('Expiration Date')" />
                                    <x-text-input id="expires_at" name="expires_at" type="datetime-local" class="mt-1 block w-full" :value="old('expires_at')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('expires_at')" />
                                </div>
                            </div>
                        </div>

                        <!-- 5. Buttons -->
                        <div class="flex items-center gap-4 border-t border-gray-200 pt-6">
                            <x-primary-button>
                                {{ __('Create Content') }}
                            </x-primary-button>
                            
                            <a href="{{ route('content.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('file_upload')" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
