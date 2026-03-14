{{--
    Full-screen module edit page.
    Messages and destructive actions are surfaced inside the shared editor shell instead of the standard app-page chrome.
--}}

<x-module-editor-layout title="{{ __('Edit Module') }}">
    @include('services.modules.partials.form', [
        'action' => route('module.update', ['module' => $module]),
        'submitLabel' => __('Save Changes'),
        'module' => $module,
        'categories' => $categories,
        'providers' => $providers,
        'editorViewData' => $editorViewData,
    ])

    @can('delete', $module)
        <form
            id="module-delete-form"
            action="{{ route('module.destroy', ['module' => $module]) }}"
            method="POST"
            class="hidden"
            data-confirm-title="{{ __('Delete module') }}"
            data-confirm-message="{{ __('Are you sure you want to delete the module :name?', ['name' => $module->name]) }}"
            data-confirm-label="{{ __('Delete') }}"
            data-cancel-label="{{ __('Cancel') }}"
            data-confirm-variant="danger"
        >
            @csrf
            @method('DELETE')
        </form>
    @endcan
</x-module-editor-layout>
