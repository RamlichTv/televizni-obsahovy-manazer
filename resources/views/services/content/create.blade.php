<x-app-layout title="{{ __('Create Content') }}">
    <x-slot name="contentNavTitle">
        {{ __('Create') }}
    </x-slot>

    {{-- Prepare picker-ready tag options and preserve selected tags after validation errors. --}}
    @php
        $contentTypes = \App\Models\ContentType::all();

        $tagsForPicker = \App\Models\Tag::query()
            ->select(['id', 'name'])
            ->get()
            ->map(fn ($tag) => [
                'id' => (string) $tag->id,
                'name' => $tag->name,
            ])
            ->values();

        $selectedTagIds = collect((array) old('tags', []))
            ->map(fn ($id) => (string) $id)
            ->values();
    @endphp

    {{-- Alpine state handles content type selection, upload restrictions, and client-side preview. --}}
    <div class="py-12" x-data="{ 
        selectedType: '{{ old('type_id', '') }}',
        types: {{ $contentTypes->mapWithKeys(fn($i) => [$i->id => $i])->toJson() }},
        fileNames: [],
        fileImagePreviews: [],
        selectedNonImageCount: 0,
        dragActive: false,
        dragDepth: 0,

        clearImagePreviews() {
            this.fileImagePreviews.forEach(preview => URL.revokeObjectURL(preview.url));
            this.fileImagePreviews = [];
        },

        applyFiles(files) {
            this.clearImagePreviews();
            this.fileNames = files.map(file => file.name);
            this.selectedNonImageCount = 0;

            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    this.fileImagePreviews.push({
                        name: file.name,
                        url: URL.createObjectURL(file),
                    });
                    return;
                }

                this.selectedNonImageCount += 1;
            });
        },

        updateFileInput(event) {
            const files = Array.from(event.target.files || []);
            this.applyFiles(files);
        },

        handleTypeChange() {
            this.dragActive = false;
            this.dragDepth = 0;
            this.fileNames = [];
            this.selectedNonImageCount = 0;
            this.clearImagePreviews();

            const fileInput = this.$refs.fileUploadInput;
            if (fileInput) {
                fileInput.value = '';
            }
        },

        handleDragEnter() {
            if (!this.currentType.is_file) {
                return;
            }

            this.dragDepth += 1;
            this.dragActive = true;
        },

        handleDragOver(event) {
            if (!this.currentType.is_file) {
                return;
            }

            event.dataTransfer.dropEffect = 'copy';
            this.dragActive = true;
        },

        handleDragLeave() {
            if (!this.currentType.is_file) {
                return;
            }

            this.dragDepth = Math.max(0, this.dragDepth - 1);
            if (this.dragDepth === 0) {
                this.dragActive = false;
            }
        },

        handleDrop(event) {
            if (!this.currentType.is_file) {
                return;
            }

            const files = Array.from(event.dataTransfer?.files || []);
            this.dragDepth = 0;
            this.dragActive = false;

            if (files.length === 0) {
                return;
            }

            const fileInput = this.$refs.fileUploadInput;
            if (!fileInput || typeof DataTransfer === 'undefined') {
                return;
            }

            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;

            this.applyFiles(files);
        },
        
        get currentType() {
            return this.types[this.selectedType] || {};
        },

        get acceptTypes() {
            let rules = this.currentType.extension_whitelist;

            if (!rules) return '';

            // If coming as JSON string, parse it.
            if (typeof rules === 'string') {
                try {
                    rules = JSON.parse(rules);
                } catch {
                    return '';
                }
            }

            if (!Array.isArray(rules)) return '';

            return rules.map(value => {
                // If it looks like a MIME type (contains slash), return as-is.
                if (value.includes('/')) {
                    return value;
                }

                // Otherwise treat it as extension.
                return '.' + value.replace(/^\./, '');
            }).join(',');
        },

        get selectedFileLabel() {
            if (this.fileNames.length === 0) {
                return '';
            }

            if (this.fileNames.length === 1) {
                return this.fileNames[0];
            }

            return `${this.fileNames.length} files selected`;
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    {{-- Shared content form partial for create workflow. --}}
                    @include('services.content.partials.form', [
                        'action' => route('content.store'),
                        'method' => 'POST',
                        'submitLabel' => __('Create Content'),
                        'cancelUrl' => route('content.index'),
                        'cancelLabel' => __('Cancel'),
                        'contentTypes' => $contentTypes,
                        'tagsForPicker' => $tagsForPicker,
                        'selectedTagIds' => $selectedTagIds,
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
