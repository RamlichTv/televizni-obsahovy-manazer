@php
    /** @var \App\Models\Module $module */
    // Resolve editor mode and normalize persisted JSON fields into arrays for safe binding.
    $isEdit = $module->exists;

    $schemaValue = old('config_schema', $module->config_schema ?? []);
    $defaultConfigValue = old('default_config', $module->default_config ?? []);
    $dataSourceValue = old('data_source', $module->data_source ?? []);

    $schemaArray = is_array($schemaValue) ? $schemaValue : (json_decode((string) $schemaValue, true) ?: []);
    $defaultConfigArray = is_array($defaultConfigValue) ? $defaultConfigValue : (json_decode((string) $defaultConfigValue, true) ?: []);
    $dataSourceArray = is_array($dataSourceValue) ? $dataSourceValue : (json_decode((string) $dataSourceValue, true) ?: []);

    // Keep JSON snapshots for hidden payload fields and read-only JSON display.
    $schemaJson = json_encode($schemaArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    $defaultConfigJson = json_encode($defaultConfigArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    $dataSourceJson = json_encode($dataSourceArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';

    // Resolve editor textareas and preview content from old() first, then persisted values.
    $resolvedTemplateHtml = old('template_html', $templateHtml ?? '');
    $resolvedTemplateCss = old('template_css', $templateCss ?? '');
    $resolvedTemplateJs = old('template_js', $templateJs ?? '');
    $resolvedPreviewHtml = old('draft_preview_html', $previewHtml ?? '');
    $resolvedCanEditScript = (bool) ($canEditScript ?? false);
    $resolvedAvailableRevisions = array_values(array_filter(
        array_map('intval', (array) ($availableRevisions ?? [])),
        static fn (int $revision): bool => $revision > 0,
    ));
    $resolvedSelectedRevision = (int) ($selectedRevision ?? ($module->revision ?? 1));

    // Option lists used by the settings UI.
    $contentTypeOptions = array_values(array_filter(array_map(
        static fn ($name): string => is_string($name) ? trim($name) : '',
        (array) ($contentTypes ?? []),
    )));

    $contentStatusOptions = array_values(array_filter(array_map(
        static fn ($name): string => is_string($name) ? trim($name) : '',
        (array) ($contentStatuses ?? []),
    )));

    $settingsModuleTitle = (string) data_get($defaultConfigArray, 'title', data_get($schemaArray, 'title.default', 'My Module'));
    $settingsProvider = (string) data_get($dataSourceArray, 'provider', 'content_objects');
    $settingsTypeName = (string) data_get($dataSourceArray, 'filters.type_name', '');
    $settingsStatusName = (string) data_get($dataSourceArray, 'filters.status_name', 'Published');
    $settingsOrderBy = (string) data_get($dataSourceArray, 'order_by', 'published_at');
    $settingsOrderDirection = (string) data_get($dataSourceArray, 'order_direction', 'desc');
    $settingsLimit = max(1, (int) data_get($dataSourceArray, 'limit', 8));

    // Keep only valid presets so Alpine never receives malformed entries.
    $resolvedTemplatePresets = array_filter(
        (array) ($templatePresets ?? []),
        static fn ($preset): bool => is_array($preset)
            && is_string($preset['label'] ?? null)
            && is_string($preset['template_html'] ?? null),
    );

    /**
     * Initial state consumed by `moduleFormEditor()` in resources/js/services/modules/form-editor.js.
     *
     * @var array{
     *   settings: array{
     *     title: string,
     *     provider: string,
     *     typeName: string,
     *     statusName: string,
     *     orderBy: string,
     *     orderDirection: string,
     *     limit: int
     *   },
     *   templatePresets: array<string, mixed>,
     *   baseConfigSchema: array<string, mixed>,
     *   baseDefaultConfig: array<string, mixed>,
     *   baseDataSource: array<string, mixed>
     * } $moduleFormInitialState
     */
    $moduleFormInitialState = [
        'settings' => [
            'title' => $settingsModuleTitle,
            'provider' => $settingsProvider,
            'typeName' => $settingsTypeName,
            'statusName' => $settingsStatusName,
            'orderBy' => $settingsOrderBy,
            'orderDirection' => $settingsOrderDirection,
            'limit' => $settingsLimit,
        ],
        'templatePresets' => $resolvedTemplatePresets,
        'baseConfigSchema' => $schemaArray,
        'baseDefaultConfig' => $defaultConfigArray,
        'baseDataSource' => $dataSourceArray,
    ];
@endphp

{{--
    Form shell:
    Keeps only shared server-side preparation and delegates UI sections to partials.
--}}
<form
    method="POST"
    action="{{ $action }}"
    class="space-y-5"
    x-data="moduleFormEditor(@js($moduleFormInitialState))"
>
    {{-- Shared module editor form used by create and edit pages --}}
    @csrf
    @if ($isEdit)
        @method('PATCH')
        <input type="hidden" name="module_id" value="{{ $module->id }}">
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col lg:flex-row min-h-[78vh]">
            @include('services.modules.partials.form.main-pane')
            @include('services.modules.partials.form.sidebar')
        </div>
    </div>
</form>
