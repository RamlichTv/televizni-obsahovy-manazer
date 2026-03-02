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
        selectedType: '',
        isFile: false,
        types: {{ $contentTypes->mapWithKeys(fn($i) => [$i->id => $i])->toJson() }},
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
