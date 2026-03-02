<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\Services\ModulePreviewRequest;
use App\Http\Requests\Tom\Services\ModuleRequest;
use App\Models\ContentStatus;
use App\Models\ContentType;
use App\Models\Module;
use App\Models\ModuleCategory;
use App\Models\PageModule;
use App\Support\Modules\ModuleRenderer;
use App\Support\Modules\TailwindBuild\TailwindBuildScheduler;
use App\Support\Modules\TailwindBuild\TailwindBuildStatus;
use App\Support\Modules\Template\LegacyTemplateFactory;
use App\Support\Modules\Template\ModuleTemplateStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\View\View;

/**
 * Handle module CRUD, preview rendering, and template asset versioning.
 *
 * Key behavior:
 * - Stores template assets per revision.
 * - Sanitizes template HTML before persistence/preview.
 * - Restricts JavaScript editing/preview to admin users.
 */
class ModulesController extends Controller
{
    /**
     * Create a new instance.
     *
     * @param ModuleRenderer $moduleRenderer
     * @param ModuleTemplateStorage $templateStorage
     * @param TailwindBuildStatus $tailwindBuildStatus
     * @param TailwindBuildScheduler $tailwindBuildScheduler
     * @param LegacyTemplateFactory $legacyTemplateFactory
     * @return void
     */
    public function __construct(
        private readonly ModuleRenderer $moduleRenderer,
        private readonly ModuleTemplateStorage $templateStorage,
        private readonly TailwindBuildStatus $tailwindBuildStatus,
        private readonly TailwindBuildScheduler $tailwindBuildScheduler,
        private readonly LegacyTemplateFactory $legacyTemplateFactory,
    ) {
    }

    /**
     * Display a listing of modules.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        // Normalize category filters to positive integer ids.
        $categoryIds = collect((array) $request->query('category_ids', []))
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();

        // Accept only known widget flags to avoid malformed query state.
        $widgetFlags = collect((array) $request->query('is_widget_flags', []))
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => in_array($value, ['0', '1'], true))
            ->unique()
            ->values()
            ->all();

        $modules = Module::query()
            ->with([
                'moduleCategory:id,name,is_widget,is_system',
                'createdBy:id,name',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                // Search by module name and related category name.
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', '%' . $search . '%')
                        ->orWhereHas('moduleCategory', function (Builder $categoryQuery) use ($search): void {
                            $categoryQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($categoryIds !== [], fn (Builder $query) => $query->whereIn('category', $categoryIds))
            ->when($widgetFlags !== [] && !(in_array('0', $widgetFlags, true) && in_array('1', $widgetFlags, true)), function (Builder $query) use ($widgetFlags): void {
                // `1` means widget categories only; `0` means non-widget or uncategorized.
                if (in_array('1', $widgetFlags, true)) {
                    $query->whereHas('moduleCategory', function (Builder $categoryQuery): void {
                        $categoryQuery->where('is_widget', true);
                    });

                    return;
                }

                $query->where(function (Builder $nested): void {
                    $nested->whereDoesntHave('moduleCategory')
                        ->orWhereHas('moduleCategory', function (Builder $categoryQuery): void {
                            $categoryQuery->where('is_widget', false);
                        });
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $categories = ModuleCategory::query()
            ->withCount('modules')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_widget', 'is_system']);

        $searchSuggestions = Module::query()
            ->select('name')
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->limit(12)
            ->pluck('name')
            ->values()
            ->all();

        return view('services.modules.index', [
            'modules' => $modules,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_ids' => $categoryIds,
                'is_widget_flags' => $widgetFlags,
            ],
            'searchSuggestions' => $searchSuggestions,
            'tailwindBuild' => $this->tailwindBuildStatus->get(),
        ]);
    }

    /**
     * Display the requested module.
     *
     * @param Module $module
     * @param Request $request
     * @return View
     */
    public function show(Module $module, Request $request): View
    {
        $module->loadMissing([
            'moduleCategory:id,name,slug,is_widget,is_system',
            'createdBy:id,name,username',
        ]);

        [$selectedRevision, $availableRevisions] = $this->resolveRevisionContext($module, $request);
        [$templateHtml, $templateCss, $templateJs] = $this->readRevisionAssets($module, $selectedRevision);
        $previewHtml = $this->safeRenderPreview(
            module: $module,
            templateHtml: $templateHtml,
            templateCss: $templateCss,
            templateJs: $templateJs,
            selectedRevision: $selectedRevision,
            user: $request->user(),
        );

        return view('services.modules.show', [
            'module' => $module,
            'selectedRevision' => $selectedRevision,
            'availableRevisions' => $availableRevisions,
            'templateHtml' => $templateHtml,
            'templateCss' => $templateCss,
            'templateJs' => $templateJs,
            'previewHtml' => $previewHtml,
            'canEditScript' => $this->canEditScript($request),
        ]);
    }

    /**
     * Show the module creation form.
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        $templatePresets = $this->resolvedTemplatePresets();
        $defaultPreset = is_array($templatePresets['generic'] ?? null) ? $templatePresets['generic'] : null;

        // Provide safe defaults so the create form always starts in valid state.
        $module = new Module([
            'is_active' => true,
            'revision' => 1,
            'config_schema' => [],
            'default_config' => [],
            'data_source' => [],
        ]);

        return view('services.modules.create', [
            'module' => $module,
            'categories' => $this->moduleCategories(),
            'providers' => $this->availableProviders(),
            'templateHtml' => old('template_html', (string) ($defaultPreset['template_html'] ?? '')),
            'templateCss' => old('template_css', (string) ($defaultPreset['template_css'] ?? '')),
            'templateJs' => old('template_js', (string) ($defaultPreset['template_js'] ?? '')),
            'previewHtml' => old('draft_preview_html', ''),
            'availableRevisions' => [1],
            'selectedRevision' => 1,
            'contentTypes' => $this->contentTypeNames(),
            'contentStatuses' => $this->contentStatusNames(),
            'templatePresets' => $templatePresets,
            'canEditScript' => $this->canEditScript($request),
        ]);
    }

    /**
     * Store a newly created module.
     *
     * @param ModuleRequest $request
     * @return RedirectResponse
     */
    public function store(ModuleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $category = ModuleCategory::query()->findOrFail((int) $validated['category']);
        $payload = $this->buildModulePayload($validated);

        // Sanitize HTML and gate JavaScript by role before any write operations.
        $templateHtml = $this->moduleRenderer->sanitizeTemplate((string) $validated['template_html']);
        $templateCss = (string) ($validated['template_css'] ?? '');
        $templateJs = $this->canEditScript($request)
            ? (string) ($validated['template_js'] ?? '')
            : '';

        $module = Module::query()->create([
            'name' => (string) $validated['name'],
            'config_schema' => $payload['config_schema'],
            'default_config' => $payload['default_config'],
            'data_source' => $payload['data_source'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'revision' => 1,
            'category' => (int) $category->id,
            'created_by' => $request->user()?->id,
        ]);

        // Write revision 1 template assets and persist generated storage paths.
        $assetPaths = $this->writeRevisionAssets(
            module: $module->loadMissing('createdBy:id,username'),
            categorySlug: (string) $category->slug,
            revision: 1,
            templateHtml: $templateHtml,
            templateCss: $templateCss,
            templateJs: $templateJs,
        );

        $module->update($assetPaths);
        $this->tailwindBuildScheduler->queueBuild();

        $this->logEvent(
            typeName: 'module_created',
            message: 'Module "' . $module->name . '" was created.',
            context: [
                'action' => 'module.store',
                'module_id' => $module->id,
                'module_name' => $module->name,
                'module_category_id' => $module->category,
                'module_revision' => (int) $module->revision,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('module.edit', ['module' => $module])
            ->with('success', __('Module created successfully.'));
    }

    /**
     * Show the module edit form.
     *
     * @param Module $module
     * @param Request $request
     * @return View
     */
    public function edit(Module $module, Request $request): View
    {
        $module->loadMissing([
            'moduleCategory:id,name,slug,is_widget,is_system',
            'createdBy:id,name,username',
        ]);

        [$selectedRevision, $availableRevisions] = $this->resolveRevisionContext($module, $request);
        [$templateHtml, $templateCss, $templateJs] = $this->readRevisionAssets($module, $selectedRevision);
        $previewHtml = $this->safeRenderPreview(
            module: $module,
            templateHtml: $templateHtml,
            templateCss: $templateCss,
            templateJs: $templateJs,
            selectedRevision: $selectedRevision,
            user: $request->user(),
        );

        return view('services.modules.edit', [
            'module' => $module,
            'categories' => $this->moduleCategories(),
            'providers' => $this->availableProviders(),
            'templateHtml' => $templateHtml,
            'templateCss' => $templateCss,
            'templateJs' => $templateJs,
            'previewHtml' => $previewHtml,
            'availableRevisions' => $availableRevisions,
            'selectedRevision' => $selectedRevision,
            'contentTypes' => $this->contentTypeNames(),
            'contentStatuses' => $this->contentStatusNames(),
            'templatePresets' => $this->resolvedTemplatePresets(),
            'canEditScript' => $this->canEditScript($request),
        ]);
    }

    /**
     * Render a draft preview from form values.
     *
     * @param ModulePreviewRequest $request
     * @return RedirectResponse
     */
    public function preview(ModulePreviewRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->buildModulePayload($validated);
        $category = ModuleCategory::query()->find((int) $validated['category']);

        // Preview reuses the same sanitization and script access policy as store/update.
        $templateHtml = $this->moduleRenderer->sanitizeTemplate((string) $validated['template_html']);
        $templateCss = (string) ($validated['template_css'] ?? '');
        $templateJs = $this->canEditScript($request)
            ? (string) ($validated['template_js'] ?? '')
            : '';

        $moduleData = [
            'id' => is_string($validated['module_id'] ?? null) ? $validated['module_id'] : null,
            'name' => (string) $validated['name'],
            'category' => $category?->name,
            'category_slug' => $category?->slug,
            'revision' => (int) ($validated['revision'] ?? 1),
        ];

        try {
            // Render draft directly from request data without mutating persisted module state.
            $previewHtml = $this->moduleRenderer->renderDraftHtml(
                templateHtml: $templateHtml,
                templateCss: $templateCss,
                templateJs: $templateJs,
                moduleData: $moduleData,
                source: $payload['data_source'],
                schema: $payload['config_schema'],
                defaults: $payload['default_config'],
                instanceConfig: [],
                viewer: $request->user(),
            );
        } catch (Throwable $exception) {
            // Keep user on form with explicit render error instead of failing the request.
            $previewHtml = '<div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">'
                . e(__('Preview failed: :message', ['message' => $exception->getMessage()]))
                . '</div>';
        }

        $moduleId = $validated['module_id'] ?? null;
        $module = is_string($moduleId) ? Module::query()->find($moduleId) : null;
        $redirect = $module instanceof Module
            ? redirect()->route('module.edit', ['module' => $module])
            : redirect()->route('module.create');

        return $redirect
            ->withInput(array_merge(
                $request->except(['_token']),
                ['draft_preview_html' => $previewHtml],
            ));
    }

    /**
     * Update the given module.
     *
     * @param ModuleRequest $request
     * @param Module $module
     * @return RedirectResponse
     */
    public function update(ModuleRequest $request, Module $module): RedirectResponse
    {
        $validated = $request->validated();
        $category = ModuleCategory::query()->findOrFail((int) $validated['category']);
        $payload = $this->buildModulePayload($validated);

        // Apply same sanitization pipeline as create/preview.
        $templateHtml = $this->moduleRenderer->sanitizeTemplate((string) $validated['template_html']);
        $templateCss = (string) ($validated['template_css'] ?? '');
        $templateJs = $this->canEditScript($request)
            ? (string) ($validated['template_js'] ?? '')
            : '';

        $oldValues = [
            'name' => $module->name,
            'category' => $module->category,
            'revision' => (int) $module->revision,
            'is_active' => (bool) $module->is_active,
        ];

        // Every update creates a new immutable revision snapshot.
        $nextRevision = max(1, (int) $module->revision + 1);
        $assetPaths = $this->writeRevisionAssets(
            module: $module->loadMissing('createdBy:id,username'),
            categorySlug: (string) $category->slug,
            revision: $nextRevision,
            templateHtml: $templateHtml,
            templateCss: $templateCss,
            templateJs: $templateJs,
        );

        $module->update(array_merge($assetPaths, [
            'name' => (string) $validated['name'],
            'config_schema' => $payload['config_schema'],
            'default_config' => $payload['default_config'],
            'data_source' => $payload['data_source'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'revision' => $nextRevision,
            'category' => (int) $category->id,
        ]));

        $this->tailwindBuildScheduler->queueBuild();

        $this->logEvent(
            typeName: 'module_edited',
            message: 'Module "' . $module->name . '" was updated.',
            context: [
                'action' => 'module.update',
                'module_id' => $module->id,
                'old_name' => $oldValues['name'],
                'new_name' => $module->name,
                'old_category_id' => $oldValues['category'],
                'new_category_id' => $module->category,
                'old_revision' => $oldValues['revision'],
                'new_revision' => (int) $module->revision,
                'old_is_active' => $oldValues['is_active'],
                'new_is_active' => (bool) $module->is_active,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('module.edit', ['module' => $module])
            ->with('success', __('Module updated successfully.'));
    }

    /**
     * Remove the given module.
     *
     * @param Request $request
     * @param Module $module
     * @return RedirectResponse
     */
    public function destroy(Request $request, Module $module): RedirectResponse
    {
        if (PageModule::query()->where('module_id', $module->id)->exists()) {
            return back()->withErrors([
                'module' => __('Module cannot be deleted while it is placed on profile pages.'),
            ]);
        }

        $moduleName = $module->name;
        $moduleId = $module->id;
        $module->delete();

        $this->tailwindBuildScheduler->queueBuild();

        $this->logEvent(
            typeName: 'module_deleted',
            message: 'Module "' . $moduleName . '" was deleted.',
            context: [
                'action' => 'module.destroy',
                'module_id' => $moduleId,
                'module_name' => $moduleName,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('modules.index')
            ->with('success', __('Module deleted successfully.'));
    }

    /**
     * Build normalized module JSON payloads from validated form data.
     *
     * @param array $validated
     * @return array
     */
    private function buildModulePayload(array $validated): array
    {
        $configSchema = is_array($validated['config_schema'] ?? null) ? $validated['config_schema'] : [];
        $defaultConfig = is_array($validated['default_config'] ?? null) ? $validated['default_config'] : [];
        $dataSource = is_array($validated['data_source'] ?? null) ? $validated['data_source'] : [];

        $title = trim((string) ($validated['settings_title'] ?? ''));
        if ($title !== '') {
            $defaultConfig['title'] = $title;
            $titleDefinition = is_array($configSchema['title'] ?? null) ? $configSchema['title'] : [];
            $titleDefinition['type'] = $titleDefinition['type'] ?? 'string';
            $titleDefinition['default'] = $title;
            $configSchema['title'] = $titleDefinition;
        }

        $provider = trim((string) ($validated['settings_provider'] ?? (string) ($dataSource['provider'] ?? '')));
        if ($provider === '') {
            $provider = 'content_objects';
        }
        $dataSource['provider'] = $provider;

        $filters = is_array($dataSource['filters'] ?? null) ? $dataSource['filters'] : [];
        $typeName = trim((string) ($validated['settings_type_name'] ?? ''));
        $statusName = trim((string) ($validated['settings_status_name'] ?? ''));

        if ($typeName === '') {
            unset($filters['type_name']);
        } else {
            $filters['type_name'] = $typeName;
        }

        if ($statusName === '') {
            unset($filters['status_name']);
        } else {
            $filters['status_name'] = $statusName;
        }

        $dataSource['filters'] = $filters;
        $dataSource['order_by'] = (string) ($validated['settings_order_by'] ?? ($dataSource['order_by'] ?? 'published_at'));
        $dataSource['order_direction'] = strtolower((string) ($validated['settings_order_direction'] ?? ($dataSource['order_direction'] ?? 'desc'))) === 'asc'
            ? 'asc'
            : 'desc';

        $settingsLimit = isset($validated['settings_limit']) ? (int) $validated['settings_limit'] : null;
        $sourceLimit = isset($dataSource['limit']) ? (int) $dataSource['limit'] : null;
        $maxLimit = max(1, (int) config('modules.max_items_per_module', 30));
        $dataSource['limit'] = max(1, min($maxLimit, $settingsLimit ?? $sourceLimit ?? 8));

        return [
            'config_schema' => $configSchema,
            'default_config' => $defaultConfig,
            'data_source' => $dataSource,
        ];
    }

    /**
     * Resolve selected revision and all available revisions.
     *
     * @param Module $module
     * @param Request $request
     * @return array
     */
    private function resolveRevisionContext(Module $module, Request $request): array
    {
        $categorySlug = (string) ($module->moduleCategory?->slug ?? 'uncategorized');
        $currentRevision = max(1, (int) ($module->revision ?: 1));
        $availableRevisions = $this->templateStorage->availableRevisions($module, $categorySlug);
        $availableRevisions[] = $currentRevision;
        $availableRevisions = array_values(array_unique(array_map('intval', $availableRevisions)));
        sort($availableRevisions, SORT_NUMERIC);

        $requestedRevision = max(1, (int) $request->query('revision', $currentRevision));
        if (!in_array($requestedRevision, $availableRevisions, true)) {
            $requestedRevision = $currentRevision;
        }

        return [$requestedRevision, $availableRevisions];
    }

    /**
     * Read template assets for one selected revision.
     *
     * @param Module $module
     * @param int $selectedRevision
     * @return array
     */
    private function readRevisionAssets(Module $module, int $selectedRevision): array
    {
        $currentRevision = max(1, (int) ($module->revision ?: 1));
        $categorySlug = (string) ($module->moduleCategory?->slug ?? 'uncategorized');

        if ($selectedRevision === $currentRevision) {
            return [
                $this->templateStorage->readCurrentTemplate($module),
                $this->templateStorage->readCurrentStylesheet($module),
                $this->templateStorage->readCurrentScript($module),
            ];
        }

        $templateHtml = $this->templateStorage->readRevisionTemplate($module, $categorySlug, $selectedRevision);
        $templateCss = $this->templateStorage->readRevisionStylesheet($module, $categorySlug, $selectedRevision);
        $templateJs = $this->templateStorage->readRevisionScript($module, $categorySlug, $selectedRevision);

        if (trim($templateHtml) !== '') {
            return [$templateHtml, $templateCss, $templateJs];
        }

        return [
            $this->templateStorage->readCurrentTemplate($module),
            $this->templateStorage->readCurrentStylesheet($module),
            $this->templateStorage->readCurrentScript($module),
        ];
    }

    /**
     * Render preview HTML without breaking the page when rendering fails.
     *
     * @param Module $module
     * @param string $templateHtml
     * @param string $templateCss
     * @param string $templateJs
     * @param int $selectedRevision
     * @param mixed $user
     * @return string
     */
    private function safeRenderPreview(
        Module $module,
        string $templateHtml,
        string $templateCss,
        string $templateJs,
        int $selectedRevision,
        mixed $user = null,
    ): string {
        if (trim($templateHtml) === '') {
            return '';
        }

        try {
            return $this->moduleRenderer->renderDraftHtml(
                templateHtml: $templateHtml,
                templateCss: $templateCss,
                templateJs: $templateJs,
                moduleData: [
                    'id' => $module->id,
                    'name' => $module->name,
                    'category' => $module->moduleCategory?->name,
                    'category_slug' => $module->moduleCategory?->slug,
                    'revision' => $selectedRevision,
                ],
                source: is_array($module->data_source) ? $module->data_source : [],
                schema: is_array($module->config_schema) ? $module->config_schema : [],
                defaults: is_array($module->default_config) ? $module->default_config : [],
                instanceConfig: [],
                viewer: $user,
            );
        } catch (Throwable $exception) {
            return '<div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">'
                . e(__('Preview failed: :message', ['message' => $exception->getMessage()]))
                . '</div>';
        }
    }

    /**
     * Return available module categories.
     *
     * @return \Illuminate\Support\Collection
     */
    private function moduleCategories()
    {
        return ModuleCategory::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_widget', 'is_system']);
    }

    /**
     * Return available data providers.
     *
     * @return array
     */
    private function availableProviders(): array
    {
        $providers = $this->moduleRenderer->availableProviders();
        if ($providers !== []) {
            return $providers;
        }

        $configured = (array) config('modules.providers', []);
        $fallback = [];
        foreach ($configured as $key => $className) {
            if (is_string($key) && trim($key) !== '') {
                $fallback[$key] = $key;
            }
        }

        return $fallback;
    }

    /**
     * Return content type names for module filters.
     *
     * @return array
     */
    private function contentTypeNames(): array
    {
        return ContentType::query()
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Return content status names for module filters.
     *
     * @return array
     */
    private function contentStatusNames(): array
    {
        return ContentStatus::query()
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Resolve the template presets shown in module create/edit.
     *
     * @return array
     */
    private function resolvedTemplatePresets(): array
    {
        $configured = (array) config('modules.template_presets', []);
        $legacy = $this->legacyTemplateFactory->presets();

        if ($configured === []) {
            return $legacy;
        }

        foreach ($legacy as $key => $preset) {
            if (!array_key_exists($key, $configured)) {
                $configured[$key] = $preset;
            }
        }

        return array_filter(
            $configured,
            static fn ($preset): bool => is_array($preset)
                && is_string($preset['label'] ?? null)
                && is_string($preset['template_html'] ?? null),
        );
    }

    /**
     * Persist one full revision set (html/css/js) and return path/checksum columns.
     *
     * @param Module $module
     * @param string $categorySlug
     * @param int $revision
     * @param string $templateHtml
     * @param string $templateCss
     * @param string $templateJs
     * @return array
     */
    private function writeRevisionAssets(
        Module $module,
        string $categorySlug,
        int $revision,
        string $templateHtml,
        string $templateCss,
        string $templateJs,
    ): array {
        $owner = is_string($module->createdBy?->username ?? null) && trim((string) $module->createdBy?->username) !== ''
            ? (string) $module->createdBy?->username
            : 'system';
        $normalizedCategory = Str::slug(trim($categorySlug));
        if ($normalizedCategory === '') {
            $normalizedCategory = 'uncategorized';
        }

        $revision = max(1, $revision);
        $baseDirectory = sprintf('users/%s/modules/%s/%s', $owner, $normalizedCategory, $module->id);
        $templatePath = $baseDirectory . '/v' . $revision . '.html';
        $stylePath = $baseDirectory . '/v' . $revision . '.css';
        $scriptPath = $baseDirectory . '/v' . $revision . '.js';

        Storage::disk('public')->makeDirectory($baseDirectory);
        Storage::disk('public')->put($templatePath, $templateHtml);
        Storage::disk('public')->put($stylePath, $templateCss);
        Storage::disk('public')->put($scriptPath, $templateJs);

        return [
            'template_path' => $templatePath,
            'template_checksum' => hash('sha256', $templateHtml),
            'style_path' => $stylePath,
            'style_checksum' => hash('sha256', $templateCss),
            'script_path' => $scriptPath,
            'script_checksum' => hash('sha256', $templateJs),
        ];
    }

    /**
     * Determine whether the current user can edit module JavaScript.
     *
     * @param Request $request
     * @return bool
     */
    private function canEditScript(Request $request): bool
    {
        return (bool) ($request->user()?->isAdmin());
    }
}
