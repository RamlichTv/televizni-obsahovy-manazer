{{--
    Overview:
    This view composes the services > modules > edit interface, arranging reusable components and route-driven actions so the page stays readable while presentation concerns remain separated from backend logic.
--}}

<x-app-layout title="{{ __('Edit Module') }}">
    <x-slot name="modulesNavTitle">
        {{ __('Edit') }}
    </x-slot>

    <x-slot name="header">
        {{-- Page header with module name context --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between w-full gap-3">
            <div class="flex items-center gap-4">
                <x-secondary-link href="{{ route('modules.index') }}">
                    <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180"/>
                    {{ __('Back to Modules') }}
                </x-secondary-link>
                <h1 class="text-2xl font-bold text-gray-800">{{ __('Edit Module') }}</h1>
            </div>

            <span class="text-sm text-gray-600">{{ $module->name }}</span>
        </div>
    </x-slot>

    <section class="py-6 px-3 md:px-4 space-y-6">
        @if ($errors->has('module'))
            <div class="mx-auto w-full max-w-7xl rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first('module') }}
            </div>
        @endif

        <div class="mx-auto w-full max-w-7xl">
            {{-- Shared create/edit module editor --}}
            @include('services.modules.partials.form', [
                'action' => route('module.update', ['module' => $module]),
                'submitLabel' => __('Save Changes'),
                'module' => $module,
                'categories' => $categories,
                'providers' => $providers,
                'templateHtml' => $templateHtml,
                'templateCss' => $templateCss,
                'templateJs' => $templateJs,
                'previewHtml' => $previewHtml,
                'availableRevisions' => $availableRevisions,
                'selectedRevision' => $selectedRevision,
                'contentTypes' => $contentTypes ?? [],
                'contentStatuses' => $contentStatuses ?? [],
                'templatePresets' => $templatePresets ?? [],
                'canEditScript' => $canEditScript,
            ])
        </div>

        @can('delete', $module)
            {{-- Destructive action block --}}
            <div class="mx-auto w-full max-w-7xl">
                <div class="rounded-lg border border-red-200 bg-red-50 p-6">
                    <h2 class="text-base font-semibold text-red-800">{{ __('Delete Module') }}</h2>
                    <p class="mt-1 text-sm text-red-700">
                        {{ __('Deleting module data keeps revision files on disk for audit purposes.') }}
                    </p>
                    <form
                        action="{{ route('module.destroy', ['module' => $module]) }}"
                        method="POST"
                        class="mt-4"
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
                </div>
            </div>
        @endcan
    </section>
</x-app-layout>
