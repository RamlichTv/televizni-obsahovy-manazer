@php
    $httpMethod = strtoupper($method ?? 'POST');
@endphp

{{-- Reusable content form used by the create page (and ready for edit usage). --}}
<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($httpMethod !== 'POST')
        @method($httpMethod)
    @endif

    {{-- 1. Content type and optional upload input (for file-based types). --}}
    <div class="flex flex-col md:flex-row gap-6 md:gap-8">
        <div class="flex-1">
            <x-input-label for="type_id" :value="__('Content Type')" :required="true"/>
            <x-select
                name="type_id"
                id="type_id"
                x-model="selectedType"
                :required="true"
            >
                <option value="" disabled>{{ __('Select type...') }}</option>
                @foreach($contentTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('type_id')" />
        </div>

        <div x-show="selectedType && currentType.is_file" class="flex-1" x-transition.opacity>
            <x-input-label for="file_upload" x-text="'Upload ' + (currentType.name || 'File')" :required="true"/>

            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative hover:bg-gray-50 transition">
                <div class="space-y-1 text-center">
                    {{-- Preview selected image files before submit. --}}
                    <template x-if="filePreview">
                        <img :src="filePreview" class="mx-auto h-32 object-contain mb-2 rounded">
                    </template>

                    {{-- Generic icon for non-image files or no selection. --}}
                    <template x-if="!filePreview">
                        <x-icon name="upload-picture" class="mx-auto h-12 w-12 text-gray-400" />
                    </template>

                    <div class="flex text-sm text-gray-600 justify-center">
                        <label for="file_upload" class="relative cursor-pointer bg-white rounded-md font-medium text-[#077CB6] hover:text-[#E69C24] focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-[#E69C24]">
                            <span>{{ __('Upload a file') }}</span>
                            <input
                                id="file_upload"
                                name="file_upload"
                                type="file"
                                class="sr-only"
                                @change="updateFileInput"
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

    {{-- 2. Core metadata fields. --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="md:col-span-3">
            <x-input-label for="name" :value="__('Content Name')" :required="true"/>
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="priority" :value="__('Priority (0-20)')" />
            <x-text-input id="priority" name="priority" type="number" min="0" max="20" class="mt-1 block w-full" :value="old('priority', 0)" required />
            <x-input-error class="mt-2" :messages="$errors->get('priority')" />
        </div>
    </div>

    {{-- 3. Optional description text. --}}
    <div>
        <x-input-label for="description" :value="__('Description')" />
        <x-textarea
            id="description"
            name="description"
            rows="3"
        >{{ old('description') }}</x-textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    {{-- 4. Tag assignment and scheduling controls. --}}
    <div class="grid items-center grid-cols-1 md:grid-cols-2 gap-6">
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

            <x-input-error class="mt-2" :messages="$errors->get('tags')" />
            <x-input-error class="mt-2" :messages="$errors->get('tags.*')" />
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

    {{-- 5. Primary action buttons. --}}
    <div class="flex items-center gap-4 border-t border-gray-200 pt-6">
        <x-primary-button>
            {{ $submitLabel }}
        </x-primary-button>

        <a href="{{ $cancelUrl }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
            {{ $cancelLabel }}
        </a>
    </div>

    <x-input-error class="mt-2" :messages="$errors->get('file_upload')" />
</form>
