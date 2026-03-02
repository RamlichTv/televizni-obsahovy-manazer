{{--
    Overview:
    This view composes the services > modules > create interface, arranging reusable components and route-driven actions so the page stays readable while presentation concerns remain separated from backend logic.
--}}

<x-app-layout title="{{ __('Create Module') }}">
    <x-slot name="modulesNavTitle">
        {{ __('Create') }}
    </x-slot>

    <x-slot name="header">
        {{-- Page header with back navigation --}}
        <div class="flex items-center gap-4 w-full">
            <x-secondary-link href="{{ route('modules.index') }}">
                <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                {{ __('Back to Modules') }}
            </x-secondary-link>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Create Module') }}</h1>
        </div>
    </x-slot>

    <section class="py-6 px-3 md:px-4">
        <div class="mx-auto w-full max-w-7xl">
            {{-- Shared create/edit module editor --}}
            @include('services.modules.partials.form', [
                'action' => route('module.store'),
                'submitLabel' => __('Create Module'),
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
    </section>
</x-app-layout>
