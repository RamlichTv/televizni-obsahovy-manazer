{{--
    Overview:
    Module preview shell. The rendered pane and inspection sidebar are delegated to components.
--}}

<x-app-layout title="{{ $module->name }}">
    <x-slot name="modulesNavTitle">
        {{ __('Preview') }}
    </x-slot>

    <x-slot name="header">

        {{-- Header with revision context and edit shortcut --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between w-full gap-3">
            <div class="flex items-center gap-4">
                <x-secondary-link href="{{ route('modules.index') }}">
                    <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180"/>
                    {{ __('Back to Modules') }}
                </x-secondary-link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $module->name }}</h1>
                    <p class="text-xs text-gray-500">
                        {{ __('Viewing revision') }} {{ $selectedRevision }}
                        @if ((int) $selectedRevision !== (int) $module->revision)
                            {{ __('(Current revision: :revision)', ['revision' => $module->revision]) }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @can('update', $module)
                    <x-primary-link href="{{ route('module.edit', ['module' => $module, 'revision' => $selectedRevision]) }}">
                        {{ __('Edit Module') }}
                    </x-primary-link>
                @endcan

                @can('delete', $module)
                    <form
                        action="{{ route('module.destroy', ['module' => $module]) }}"
                        method="POST"
                        data-confirm-title="{{ __('Delete module') }}"
                        data-confirm-message="{{ __('Are you sure you want to delete the module :name?', ['name' => $module->name]) }}"
                        data-confirm-label="{{ __('Delete') }}"
                        data-cancel-label="{{ __('Cancel') }}"
                        data-confirm-variant="danger"
                    >
                        @csrf
                        @method('DELETE')
                        <x-danger-button>
                            {{ __('Delete Module') }}
                        </x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <section class="mx-auto w-full max-w-7xl p-3 md:p-5" x-data="{ sidebarOpen: true, sidebarTab: 'code', codeTab: 'html' }">
        @if ($errors->has('module'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first('module') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col lg:flex-row min-h-[78vh]">
                
                {{-- Rendered preview surface --}}
                <x-services.modules.show.preview-pane
                    :module="$module"
                    :preview-html="$previewHtml"
                    :available-revisions="$availableRevisions"
                    :selected-revision="$selectedRevision"
                />

                {{-- Read-only code/settings metadata --}}
                <x-services.modules.show.sidebar
                    :module="$module"
                    :template-html="$templateHtml"
                    :template-css="$templateCss"
                    :template-js="$templateJs"
                    :can-edit-script="$canEditScript"
                />
            </div>
        </div>
    </section>
</x-app-layout>
