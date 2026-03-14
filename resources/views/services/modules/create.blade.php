{{--
    Full-screen module creation page.
    The shared form partial owns the editor shell so create stays aligned with the dedicated module workspace layout.
--}}

<x-module-editor-layout title="{{ __('Create Module') }}">
    @include('services.modules.partials.form', [
        'action' => route('module.store'),
        'submitLabel' => __('Create Module'),
        'module' => $module,
        'categories' => $categories,
        'providers' => $providers,
        'editorViewData' => $editorViewData,
    ])
</x-module-editor-layout>
