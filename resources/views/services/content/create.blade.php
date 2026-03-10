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
        selectedFiles: [],
        selectedFileItems: [],
        dragActive: false,
        dragDepth: 0,

        clearSelectedFiles() {
            this.selectedFileItems.forEach(item => {
                if (item.previewUrl) {
                    URL.revokeObjectURL(item.previewUrl);
                }
            });

            this.selectedFiles = [];
            this.selectedFileItems = [];
        },

        normalizeFiles(files) {
            return this.currentType.multiple_file ? files : files.slice(0, 1);
        },

        fileKindLabel(file) {
            if (file.type.startsWith('image/')) {
                return 'Image';
            }

            if (file.type.startsWith('video/')) {
                return 'Video';
            }

            const extension = file.name.split('.').pop();
            return extension ? extension.toUpperCase() : 'File';
        },

        formatFileSize(bytes) {
            if (!Number.isFinite(bytes) || bytes <= 0) {
                return '';
            }

            if (bytes < 1024 * 1024) {
                return `${Math.round(bytes / 1024)} KB`;
            }

            return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
        },

        syncInputFiles() {
            const fileInput = this.$refs.fileUploadInput;
            if (!fileInput) {
                return;
            }

            if (this.selectedFiles.length === 0) {
                fileInput.value = '';
                return;
            }

            if (typeof DataTransfer === 'undefined') {
                return;
            }

            const dataTransfer = new DataTransfer();
            this.selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        },

        applyFiles(files) {
            const normalizedFiles = this.normalizeFiles(files);

            this.clearSelectedFiles();
            this.selectedFiles = normalizedFiles;
            this.selectedFileItems = normalizedFiles.map((file, index) => ({
                id: `${file.name}-${file.size}-${file.lastModified}-${index}`,
                name: file.name,
                previewUrl: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
                kindLabel: this.fileKindLabel(file),
                sizeLabel: this.formatFileSize(file.size),
            }));
        },

        updateFileInput(event) {
            const files = Array.from(event.target.files || []);
            this.applyFiles(files);
        },

        handleTypeChange() {
            this.dragActive = false;
            this.dragDepth = 0;
            this.clearSelectedFiles();

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

            this.applyFiles(files);
            this.syncInputFiles();
        },

        removeSelectedFile(index) {
            const nextFiles = this.selectedFiles.filter((_, currentIndex) => currentIndex !== index);
            this.applyFiles(nextFiles);
            this.syncInputFiles();
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

        get hasSelectedFiles() {
            return this.selectedFileItems.length > 0;
        },

        get isSingleSelectedFile() {
            return this.hasSelectedFiles && !this.currentType.multiple_file;
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
