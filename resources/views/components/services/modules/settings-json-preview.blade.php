{{--
    Reusable generated JSON snapshot used by the module settings UI.
    It keeps live payloads available without letting debug output dominate the editor pane.
--}}

@props([
    'title',
    'description' => null,
    'expanded' => false,
    'preview' => '',
])

<x-services.modules.settings-accordion
    :title="$title"
    :description="$description"
    :expanded="$expanded"
    body-class="pt-0"
>
    <pre class="overflow-x-auto rounded-md border border-gray-200 bg-gray-50 p-4 text-xs leading-6 text-gray-600" x-text="{{ $preview }}"></pre>
</x-services.modules.settings-accordion>
