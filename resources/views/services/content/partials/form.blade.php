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
                @change="handleTypeChange"
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
            <x-input-label for="file_upload" x-text="'{{ __('Upload') }} ' + (currentType.name || 'File')" :required="true"/>

            <div
                class="mt-1 relative overflow-hidden rounded-2xl border-2 border-dashed transition"
                :class="isSingleSelectedFile || dragActive ? 'border-[#077CB6] bg-[#e7f4fb]' : 'border-gray-300 hover:bg-gray-50'"
                @dragenter.prevent="handleDragEnter"
                @dragover.prevent="handleDragOver"
                @dragleave.prevent="handleDragLeave"
                @drop.prevent="handleDrop"
            >
                <input
                    id="file_upload"
                    :name="currentType.multiple_file ? 'file_upload[]' : 'file_upload'"
                    type="file"
                    :multiple="!!currentType.multiple_file"
                    x-ref="fileUploadInput"
                    class="sr-only"
                    @change="updateFileInput"
                    :accept="acceptTypes"
                >

                <template x-if="isSingleSelectedFile">
                    <div class="group relative flex min-h-68 items-center justify-center p-6">
                        <button
                            type="button"
                            class="absolute inset-0 z-10"
                            @click="$refs.fileUploadInput.click()"
                            :aria-label="'Replace ' + (currentType.name || 'file')"
                        ></button>

                        <template x-if="selectedFileItems[0]?.previewKind === 'image'">
                            <div class="relative">
                                <img
                                    :src="selectedFileItems[0].previewUrl"
                                    :alt="selectedFileItems[0].name"
                                    class="pointer-events-none max-h-64 max-w-full rounded-xl object-contain shadow-lg transition duration-200 ease-out group-hover:scale-[0.985] group-hover:opacity-80"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-0 z-20 flex items-center justify-center rounded-xl bg-red-500/0 text-white opacity-0 transition duration-200 ease-out group-hover:bg-red-500/75 group-hover:opacity-100"
                                    @click.stop="removeSelectedFile(0)"
                                    aria-label="{{ __('Remove file') }}"
                                >
                                    <x-icon name="x" class="h-14 w-14 scale-75 transition duration-200 ease-out group-hover:scale-100" />
                                </button>
                            </div>
                        </template>

                        <template x-if="selectedFileItems[0]?.previewKind === 'pdf'">
                            <div class="relative overflow-hidden rounded-xl bg-white shadow-lg">
                                <iframe
                                    :src="selectedFileItems[0].previewUrl"
                                    :title="selectedFileItems[0].name"
                                    class="pointer-events-none h-64 w-[min(36rem,72vw)] max-w-full rounded-xl bg-white transition duration-200 ease-out group-hover:scale-[0.985] group-hover:opacity-80"
                                ></iframe>

                                <div class="pointer-events-none absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm">
                                    {{ __('PDF Preview') }}
                                </div>

                                <button
                                    type="button"
                                    class="absolute inset-0 z-20 flex items-center justify-center rounded-xl bg-red-500/0 text-white opacity-0 transition duration-200 ease-out group-hover:bg-red-500/75 group-hover:opacity-100"
                                    @click.stop="removeSelectedFile(0)"
                                    aria-label="{{ __('Remove file') }}"
                                >
                                    <x-icon name="x" class="h-14 w-14 scale-75 transition duration-200 ease-out group-hover:scale-100" />
                                </button>
                            </div>
                        </template>

                        <template x-if="!selectedFileItems[0]?.previewKind">
                            <div class="relative">
                                <div class="pointer-events-none flex h-28 w-28 items-center justify-center rounded-3xl bg-white text-gray-500 shadow-lg transition duration-200 ease-out group-hover:scale-[0.985] group-hover:opacity-80">
                                    <x-icon name="upload-picture" class="h-12 w-12" />
                                </div>
                                <button
                                    type="button"
                                    class="absolute inset-0 z-20 flex items-center justify-center rounded-3xl bg-red-500/0 text-white opacity-0 transition duration-200 ease-out group-hover:bg-red-500/75 group-hover:opacity-100"
                                    @click.stop="removeSelectedFile(0)"
                                    aria-label="{{ __('Remove file') }}"
                                >
                                    <x-icon name="x" class="h-10 w-10 scale-75 transition duration-200 ease-out group-hover:scale-100" />
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="currentType.multiple_file && hasSelectedFiles">
                    <div class="space-y-4 p-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <template x-for="(file, index) in selectedFileItems" :key="file.id">
                                <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                    <div class="relative flex h-36 items-center justify-center overflow-hidden bg-slate-100">
                                        <template x-if="file.previewKind === 'image'">
                                            <img :src="file.previewUrl" :alt="file.name" class="pointer-events-none h-full w-full object-cover transition duration-200 ease-out group-hover:scale-[0.985] group-hover:opacity-80">
                                        </template>

                                        <template x-if="file.previewKind === 'pdf'">
                                            <div class="relative h-full w-full overflow-hidden bg-white">
                                                <iframe :src="file.previewUrl" :title="file.name" class="pointer-events-none h-full w-full bg-white transition duration-200 ease-out group-hover:scale-[0.985] group-hover:opacity-80"></iframe>
                                                <div class="pointer-events-none absolute left-3 top-3 rounded-full bg-white/90 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-sm">
                                                    PDF
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="!file.previewKind">
                                            <div class="pointer-events-none flex h-20 w-20 items-center justify-center rounded-3xl bg-white text-gray-500 shadow-sm transition duration-200 ease-out group-hover:scale-[0.985] group-hover:opacity-80">
                                                <x-icon name="upload-picture" class="h-10 w-10" />
                                            </div>
                                        </template>

                                        <button
                                            type="button"
                                            class="absolute inset-0 z-20 flex items-center justify-center bg-red-500/0 text-white opacity-0 transition duration-200 ease-out group-hover:bg-red-500/75 group-hover:opacity-100"
                                            @click.stop="removeSelectedFile(index)"
                                            :aria-label="'Remove ' + file.name"
                                        >
                                            <x-icon name="x" class="h-12 w-12 scale-75 transition duration-200 ease-out group-hover:scale-100" />
                                        </button>
                                    </div>

                                    <div class="space-y-1 p-3">
                                        <p class="truncate text-sm font-semibold text-gray-900" x-text="file.name"></p>
                                        <div class="flex items-center justify-between gap-2 text-xs text-gray-500">
                                            <span x-text="file.kindLabel"></span>
                                            <span x-text="file.sizeLabel"></span>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-4 py-3">
                            <button
                                type="button"
                                class="rounded-full bg-[#077CB6] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#066a9c]"
                                @click="$refs.fileUploadInput.click()"
                            >
                                {{ __('Add files') }}
                            </button>
                            <p class="text-xs text-gray-500">{{ __('or drag and drop more files') }}</p>
                        </div>

                        <p class="text-center text-xs text-gray-500" x-text="'Allowed: ' + currentType.extension_whitelist"></p>
                    </div>
                </template>

                <template x-if="!hasSelectedFiles">
                    <div class="space-y-3 px-6 py-8 text-center">
                        <x-icon name="upload-picture" class="mx-auto h-12 w-12 text-gray-400" />

                        <div class="flex justify-center text-sm text-gray-600">
                            <button
                                type="button"
                                class="rounded-md bg-white font-medium text-[#077CB6] transition hover:text-[#E69C24] focus:outline-none focus:ring-2 focus:ring-[#E69C24] focus:ring-offset-2"
                                @click="$refs.fileUploadInput.click()"
                            >
                                {{ __('Upload file(s)') }}
                            </button>
                            <p class="pl-1">{{ __('or drag and drop') }}</p>
                        </div>

                        <template x-if="currentType.is_file">
                            <p class="text-xs text-gray-500" x-text="'Allowed: ' + currentType.extension_whitelist"></p>
                        </template>
                    </div>
                </template>
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
    <x-input-error class="mt-2" :messages="$errors->get('file_upload.*')" />
</form>
