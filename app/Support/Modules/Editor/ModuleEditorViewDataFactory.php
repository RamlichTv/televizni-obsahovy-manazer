<?php

namespace App\Support\Modules\Editor;

use App\Models\Module;
use App\Models\Tag;
use Illuminate\Foundation\Vite as LaravelVite;

/**
 * Prepare normalized view data for the module editor shell.
 *
 * Blade templates stay intentionally thin. This factory gathers JSON payloads,
 * picker selections, preset state, and preview iframe scaffolding in one place.
 */
class ModuleEditorViewDataFactory
{
    /**
     * Create a new instance.
     *
     * @param LaravelVite $vite
     * @return void
     */
    public function __construct(
        private readonly LaravelVite $vite,
    ) {
    }

    /**
     * Build view data for create/edit module pages.
     *
     * @param Module $module
     * @param array $options
     * @return array<string, mixed>
     */
    public function buildFormViewData(Module $module, array $options = []): array
    {
        $schemaValue = old('data_schema', $module->data_schema ?? []);
        $moduleDataValue = old('module_data', $module->module_data ?? []);
        $dataSourceValue = old('data_source', $module->data_source ?? []);

        $schemaArray = $this->normalizeArrayValue($schemaValue);
        $moduleDataArray = $this->normalizeArrayValue($moduleDataValue);
        $dataSourceArray = $this->normalizeArrayValue($dataSourceValue);

        $schemaJson = $this->encodeJson($schemaArray);
        $moduleDataJson = $this->encodeJson($moduleDataArray);
        $dataSourceJson = $this->encodeJson($dataSourceArray);

        $schemaRawJson = old('data_schema_raw', is_string($schemaValue) ? $schemaValue : $schemaJson);
        $moduleDataRawJson = old('module_data_raw', is_string($moduleDataValue) ? $moduleDataValue : $moduleDataJson);
        $dataSourceRawJson = old('data_source_raw', is_string($dataSourceValue) ? $dataSourceValue : $dataSourceJson);

        $previewDocument = $this->previewDocumentScaffold();
        $settingsMode = old('module_settings_mode', 'builder');
        $selectedTypeNames = $this->resolveSelectedTypeNames($dataSourceArray);
        $selectedStatusNames = $this->resolveSelectedStatusNames($dataSourceArray);
        $selectedTagIds = $this->normalizeIntegerList((array) old('settings_tag_ids', data_get($dataSourceArray, 'filters.tag_ids', [])));

        $templatePresetOptions = array_filter(
            (array) ($options['templatePresets'] ?? []),
            static fn ($preset): bool => is_array($preset)
                && is_string($preset['label'] ?? null)
                && is_string($preset['template_html'] ?? null),
        );

        $initialState = [
            'providerSettings' => [
                'provider' => (string) data_get($dataSourceArray, 'provider', 'content_objects'),
                'authoredBy' => $this->resolveAuthoredByScope($dataSourceArray),
                'typeNames' => $selectedTypeNames,
                'statusNames' => $selectedStatusNames,
                'tagIds' => $selectedTagIds,
                'orderBy' => (string) data_get($dataSourceArray, 'order_by', 'published_at'),
                'orderDirection' => (string) data_get($dataSourceArray, 'order_direction', 'desc'),
                'limit' => max(1, (int) data_get($dataSourceArray, 'limit', 8)),
            ],
            'templatePresets' => $templatePresetOptions,
            'baseDataSchema' => $schemaArray,
            'baseModuleData' => $moduleDataArray,
            'baseDataSource' => $dataSourceArray,
            'rawJson' => [
                'dataSchema' => $schemaRawJson,
                'moduleData' => $moduleDataRawJson,
                'dataSource' => $dataSourceRawJson,
            ],
            'settingsMode' => in_array($settingsMode, ['builder', 'raw'], true) ? $settingsMode : 'builder',
            'previewHtml' => old('draft_preview_html', (string) ($options['previewHtml'] ?? '')),
            'previewError' => (string) old('draft_preview_error', ''),
            'previewRoute' => route('module.preview'),
            'previewDocumentPrefix' => $previewDocument['prefix'],
            'previewDocumentSuffix' => $previewDocument['suffix'],
            'layout' => 'vertical',
            'splitPosition' => 47,
            'sidebarTab' => 'code',
            'activeTab' => 'html',
        ];

        return [
            'flags' => [
                'isEdit' => $module->exists,
                'canEditScript' => (bool) ($options['canEditScript'] ?? false),
                'canBypassSanitizer' => (bool) ($options['canBypassSanitizer'] ?? false),
                'embeddedAccessLocked' => (bool) ($options['embeddedAccessLocked'] ?? false),
            ],
            'templates' => [
                'html' => old('template_html', (string) ($options['templateHtml'] ?? '')),
                'css' => old('template_css', (string) ($options['templateCss'] ?? '')),
                'js' => old('template_js', (string) ($options['templateJs'] ?? '')),
            ],
            'preview' => [
                'html' => $initialState['previewHtml'],
                'error' => $initialState['previewError'],
                'document' => $previewDocument,
            ],
            'jsonPayloads' => [
                'dataSchema' => $schemaJson,
                'moduleData' => $moduleDataJson,
                'dataSource' => $dataSourceJson,
            ],
            'rawJson' => $initialState['rawJson'],
            'settingsUi' => [
                'mode' => $initialState['settingsMode'],
                'selectedTypeNames' => $selectedTypeNames,
                'selectedStatusNames' => $selectedStatusNames,
                'selectedTagIds' => $selectedTagIds,
            ],
            'pickers' => [
                'typeItems' => $this->buildStringPickerItems((array) ($options['contentTypes'] ?? [])),
                'statusItems' => $this->buildTranslatedPickerItems((array) ($options['contentStatuses'] ?? [])),
                'tagItems' => $this->buildTagPickerItems((array) ($options['contentTags'] ?? [])),
            ],
            'revisions' => [
                'available' => array_values(array_filter(array_map('intval', (array) ($options['availableRevisions'] ?? [])), static fn (int $revision): bool => $revision > 0)),
                'selected' => (int) ($options['selectedRevision'] ?? ($module->revision ?? 1)),
            ],
            'presets' => $templatePresetOptions,
            'initialState' => $initialState,
        ];
    }

    /**
     * Build minimal preview-shell state for the read-only show page.
     *
     * @param string $previewHtml
     * @return array<string, mixed>
     */
    public function buildShowViewData(string $previewHtml): array
    {
        $previewDocument = $this->previewDocumentScaffold();

        return [
            'previewDocument' => $previewDocument,
            'showShellState' => [
                'previewHtml' => $previewHtml,
                'previewError' => '',
                'previewDocumentPrefix' => $previewDocument['prefix'],
                'previewDocumentSuffix' => $previewDocument['suffix'],
                'layout' => 'vertical',
                'splitPosition' => 32,
                'sidebarTab' => 'settings',
                'activeTab' => 'html',
            ],
        ];
    }

    /**
     * Build the iframe document wrapper used by module previews.
     *
     * @return array{prefix: string, suffix: string}
     */
    public function previewDocumentScaffold(): array
    {
        try {
            $runtimeCssHref = (string) $this->vite->asset('resources/css/app.css');
        } catch (\Throwable) {
            $runtimeCssHref = '';
        }

        try {
            $runtimeScriptHref = (string) $this->vite->asset('resources/js/module-runtime.js');
        } catch (\Throwable) {
            $runtimeScriptHref = '';
        }

        return [
            'prefix' => '<!doctype html><html lang="' . e(str_replace('_', '-', app()->getLocale())) . '"><head>'
                . '<meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1">'
                . '<link rel="preconnect" href="https://fonts.bunny.net">'
                . '<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />'
                . ($runtimeCssHref !== '' ? '<link rel="stylesheet" href="' . e($runtimeCssHref) . '">' : '')
                . ($runtimeScriptHref !== '' ? '<script type="module" src="' . e($runtimeScriptHref) . '"></script>' : '')
                . '<style>html,body{margin:0;padding:0;width:100%;height:100%;min-width:0;min-height:100%;background:#fff;overflow:hidden}body{display:block}*,*::before,*::after{box-sizing:border-box}[x-cloak]{display:none!important}</style>'
                . '</head><body class="font-sans antialiased">',
            'suffix' => '</body></html>',
        ];
    }

    /**
     * Normalize a mixed value into an array for JSON-backed module fields.
     *
     * @param mixed $value
     * @return array
     */
    private function normalizeArrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Encode an array into pretty JSON for editor usage.
     *
     * @param array $value
     * @return string
     */
    private function encodeJson(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Resolve provider type selections from current request/module data.
     *
     * @param array $dataSourceArray
     * @return array<int, string>
     */
    private function resolveSelectedTypeNames(array $dataSourceArray): array
    {
        $selectedTypeNames = $this->normalizeStringList((array) old('settings_type_names', data_get($dataSourceArray, 'filters.type_names', [])));

        if ($selectedTypeNames !== []) {
            return $selectedTypeNames;
        }

        $legacyTypeName = trim((string) old('settings_type_name', data_get($dataSourceArray, 'filters.type_name', '')));

        return $legacyTypeName !== '' ? [$legacyTypeName] : [];
    }

    /**
     * Resolve provider status selections from current request/module data.
     *
     * @param array $dataSourceArray
     * @return array<int, string>
     */
    private function resolveSelectedStatusNames(array $dataSourceArray): array
    {
        $selectedStatusNames = $this->normalizeStringList((array) old('settings_status_names', data_get($dataSourceArray, 'filters.status_names', [])));

        if ($selectedStatusNames === []) {
            $legacyStatusName = trim((string) old('settings_status_name', data_get($dataSourceArray, 'filters.status_name', '')));
            $selectedStatusNames = $legacyStatusName !== '' ? [$legacyStatusName] : [];
        }

        return $selectedStatusNames === [] ? ['Published'] : $selectedStatusNames;
    }

    /**
     * Resolve the guided author scope used by provider widgets like "my content".
     *
     * @param array $dataSourceArray
     * @return string
     */
    private function resolveAuthoredByScope(array $dataSourceArray): string
    {
        $scope = trim((string) old('settings_authored_by', data_get($dataSourceArray, 'filters.authored_by', '')));

        return in_array(strtolower($scope), ['viewer', 'current_user'], true) ? 'viewer' : 'all';
    }

    /**
     * Convert arbitrary input into a unique list of strings.
     *
     * @param array $values
     * @return array<int, string>
     */
    private function normalizeStringList(array $values): array
    {
        return collect($values)
            ->map(static fn ($value): string => is_string($value) ? trim($value) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Convert arbitrary input into a unique list of positive integers.
     *
     * @param array $values
     * @return array<int, int>
     */
    private function normalizeIntegerList(array $values): array
    {
        return collect($values)
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build picker items from plain string lists.
     *
     * @param array $values
     * @return array<int, array{id: string, name: string}>
     */
    private function buildStringPickerItems(array $values): array
    {
        return collect($values)
            ->map(static fn ($value): string => is_string($value) ? trim($value) : '')
            ->filter()
            ->map(static fn (string $value): array => ['id' => $value, 'name' => $value])
            ->values()
            ->all();
    }

    /**
     * Build translated picker items from plain string lists.
     *
     * @param array $values
     * @return array<int, array{id: string, name: string}>
     */
    private function buildTranslatedPickerItems(array $values): array
    {
        return collect($values)
            ->map(static fn ($value): string => is_string($value) ? trim($value) : '')
            ->filter()
            ->map(static fn (string $value): array => ['id' => $value, 'name' => (string) __($value)])
            ->values()
            ->all();
    }

    /**
     * Build picker items from tag models/arrays.
     *
     * @param array $tags
     * @return array<int, array{id: string, name: string}>
     */
    private function buildTagPickerItems(array $tags): array
    {
        return collect($tags)
            ->map(function ($tag): array {
                if ($tag instanceof Tag) {
                    return [
                        'id' => (string) $tag->id,
                        'name' => trim((string) $tag->name),
                    ];
                }

                if (is_array($tag)) {
                    return [
                        'id' => (string) ((int) ($tag['id'] ?? 0)),
                        'name' => is_string($tag['name'] ?? null) ? trim((string) $tag['name']) : '',
                    ];
                }

                return ['id' => '', 'name' => ''];
            })
            ->filter(static fn (array $tag): bool => $tag['id'] !== '' && $tag['name'] !== '')
            ->values()
            ->all();
    }
}
