@php
    /** @var array<string, mixed> $editorViewData */
    $flags = $editorViewData['flags'];
    $templates = $editorViewData['templates'];
    $preview = $editorViewData['preview'];
    $jsonPayloads = $editorViewData['jsonPayloads'];
    $rawJson = $editorViewData['rawJson'];
    $settingsUi = $editorViewData['settingsUi'];
    $pickers = $editorViewData['pickers'];
    $revisions = $editorViewData['revisions'];
    $templatePresetOptions = $editorViewData['presets'];
    $editorInitialState = $editorViewData['initialState'];
@endphp

{{--
    Shared full-screen module editor shell.
    Data preparation lives in ModuleEditorViewDataFactory so this template only orchestrates rendering.
--}}
<form
    method="POST"
    action="{{ $action }}"
    class="flex h-full min-h-0 flex-col overflow-hidden"
    x-data="moduleEditor(@js($editorInitialState))"
    x-ref="moduleEditorForm"
    @submit.capture="if (@js((bool) $flags['embeddedAccessLocked']) || !prepareForSubmit()) $event.preventDefault()"
>
    @csrf
    @if ($flags['isEdit'])
        @method('PATCH')
        <input type="hidden" name="module_id" value="{{ $module->id }}">
    @endif

    <input type="hidden" name="revision" value="{{ $revisions['selected'] }}">

    @include('services.modules.partials.form.top-bar', [
        'flags' => $flags,
        'revisions' => $revisions,
        'submitLabel' => $submitLabel,
        'module' => $module,
        'categories' => $categories,
    ])

    <div class="relative flex min-h-0 flex-1 overflow-hidden" x-ref="editorWorkspace" :class="workspaceClass()">
        @include('services.modules.partials.form.sidebar', [
            'flags' => $flags,
            'templates' => $templates,
            'jsonPayloads' => $jsonPayloads,
            'rawJson' => $rawJson,
            'settingsUi' => $settingsUi,
            'pickers' => $pickers,
            'templatePresetOptions' => $templatePresetOptions,
            'providers' => $providers,
        ])

        <div
            x-show="layout !== 'preview'"
            x-cloak
            @pointerdown.prevent="startDragging($event)"
            :class="gutterClass()"
            class="module-editor-gutter shrink-0 bg-gray-200/70 transition-colors hover:bg-[#5EB4EE]/40"
            :data-dragging="isDragging ? 'true' : 'false'"
        ></div>

        @include('services.modules.partials.form.main-pane', [
            'flags' => $flags,
            'preview' => $preview,
            'revisions' => $revisions,
            'module' => $module,
        ])

        @if ($flags['embeddedAccessLocked'])
            {{-- Read-only overlay for modules assigned to embedded categories without permission. --}}
            <div class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 px-6 backdrop-blur-[1px]">
                <div class="max-w-lg rounded-xl border border-amber-200 bg-white px-6 py-5 text-center shadow-lg">
                    <p class="text-sm font-semibold uppercase tracking-widest text-amber-700">{{ __('Embedded Access Restricted') }}</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        {{ __('This module belongs to a category that allows embedded content. You can inspect it, but editing and preview actions are locked until your account gets the embedded module permission.') }}
                    </p>
                </div>
            </div>
        @endif
    </div>
</form>
