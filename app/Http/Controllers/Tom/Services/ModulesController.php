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
use App\Models\Tag;
use App\Support\Modules\ModuleRenderer;
use App\Support\Modules\Editor\ModuleEditorViewDataFactory;
use App\Support\Modules\TailwindBuild\TailwindBuildScheduler;
use App\Support\Modules\TailwindBuild\TailwindBuildStatus;
use App\Support\Modules\Template\LegacyTemplateFactory;
use App\Support\Modules\Template\ModuleTemplateStorage;
use Illuminate\Http\JsonResponse;
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
 * - Gates JavaScript editing and embedded-category usage by explicit permissions.
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
        private readonly ModuleEditorViewDataFactory $moduleEditorViewDataFactory,
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
                'moduleCategory:id,name,is_widget,allow_embedded,is_system',
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
            ->get(['id', 'name', 'slug', 'is_widget', 'is_screensaver', 'allow_embedded', 'is_system']);

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
            'moduleCategory:id,name,slug,is_widget,allow_embedded,is_system',
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
            ...$this->moduleEditorViewDataFactory->buildShowViewData($previewHtml),
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
        $templatePresets = $this->resolvedTemplatePresets($this->canEditScript($request));

        // Start new modules with empty template editors so authors begin from a
        // blank canvas instead of inheriting a preset automatically.
        $module = new Module([
            'is_active' => true,
            'revision' => 1,
            'data_schema' => [],
            'module_data' => [],
            'data_source' => [],
        ]);

        return view('services.modules.create', [
            'module' => $module,
            'categories' => $this->editorModuleCategories($request),
            'providers' => $this->availableProviders(),
            'editorViewData' => $this->moduleEditorViewDataFactory->buildFormViewData($module, [
                'templateHtml' => '',
                'templateCss' => '',
                'templateJs' => '',
                'previewHtml' => '',
                'availableRevisions' => [1],
                'selectedRevision' => 1,
                'contentTypes' => $this->contentTypeNames(),
                'contentStatuses' => $this->contentStatusNames(),
                'contentTags' => $this->contentTags(),
                'templatePresets' => $templatePresets,
                'canEditScript' => $this->canEditScript($request),
                'canBypassSanitizer' => $this->canBypassTemplateSanitization($request),
                'embeddedAccessLocked' => false,
            ]),
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
        $templateHtml = $this->resolveTemplateHtml(
            request: $request,
            templateHtml: (string) $validated['template_html'],
            allowEmbedded: (bool) ($category->allow_embedded ?? false),
        );
        $templateCss = (string) ($validated['template_css'] ?? '');
        $templateJs = $this->canEditScript($request)
            ? (string) ($validated['template_js'] ?? '')
            : '';

        $module = Module::query()->create([
            'name' => (string) $validated['name'],
            'data_schema' => $payload['data_schema'],
            'module_data' => $payload['module_data'],
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
            'moduleCategory:id,name,slug,is_widget,allow_embedded,is_system',
            'createdBy:id,name,username',
        ]);
        $embeddedAccessLocked = $this->embeddedAccessLocked($request, $module->moduleCategory);

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
            'categories' => $this->editorModuleCategories($request, $module),
            'providers' => $this->availableProviders(),
            'editorViewData' => $this->moduleEditorViewDataFactory->buildFormViewData($module, [
                'templateHtml' => $templateHtml,
                'templateCss' => $templateCss,
                'templateJs' => $templateJs,
                'previewHtml' => $previewHtml,
                'availableRevisions' => $availableRevisions,
                'selectedRevision' => $selectedRevision,
                'contentTypes' => $this->contentTypeNames(),
                'contentStatuses' => $this->contentStatusNames(),
                'contentTags' => $this->contentTags(),
                'templatePresets' => $this->resolvedTemplatePresets($this->canEditScript($request)),
                'canEditScript' => $this->canEditScript($request),
                'canBypassSanitizer' => $this->canBypassTemplateSanitization($request),
                'embeddedAccessLocked' => $embeddedAccessLocked,
            ]),
        ]);
    }

    /**
     * Render a draft preview from form values.
     *
     * @param ModulePreviewRequest $request
     * @return JsonResponse|RedirectResponse
     */
    public function preview(ModulePreviewRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->buildModulePayload($validated);
        $category = ModuleCategory::query()->find((int) $validated['category']);
        $previewError = '';

        // Preview reuses the same sanitization and script access policy as store/update.
        $templateHtml = $this->resolveTemplateHtml(
            request: $request,
            templateHtml: (string) $validated['template_html'],
            allowEmbedded: (bool) ($category?->allow_embedded ?? false),
        );
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
                schema: $payload['data_schema'],
                defaults: $payload['module_data'],
                instanceConfig: [],
                viewer: $request->user(),
            );
        } catch (Throwable $exception) {
            // Keep user on form with explicit render error instead of failing the request.
            $previewHtml = '';
            $previewError = (string) __('Preview failed: :message', ['message' => $exception->getMessage()]);
        }

        $moduleId = $validated['module_id'] ?? null;
        $module = is_string($moduleId) ? Module::query()->find($moduleId) : null;
        $redirect = $module instanceof Module
            ? redirect()->route('module.edit', ['module' => $module])
            : redirect()->route('module.create');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'preview_html' => $previewHtml,
                'preview_error' => $previewError,
            ]);
        }

        return $redirect
            ->withInput(array_merge(
                $request->except(['_token']),
                [
                    'draft_preview_html' => $previewHtml,
                    'draft_preview_error' => $previewError,
                ],
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
        $templateHtml = $this->resolveTemplateHtml(
            request: $request,
            templateHtml: (string) $validated['template_html'],
            allowEmbedded: (bool) ($category->allow_embedded ?? false),
        );
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
            'data_schema' => $payload['data_schema'],
            'module_data' => $payload['module_data'],
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
        $dataSchema = is_array($validated['data_schema'] ?? null) ? $validated['data_schema'] : [];
        $moduleData = is_array($validated['module_data'] ?? null) ? $validated['module_data'] : [];
        $dataSource = is_array($validated['data_source'] ?? null) ? $validated['data_source'] : [];

        return [
            'data_schema' => $dataSchema,
            'module_data' => $moduleData,
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
                schema: is_array($module->data_schema) ? $module->data_schema : [],
                defaults: is_array($module->module_data) ? $module->module_data : [],
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
            ->get(['id', 'name', 'slug', 'is_widget', 'is_screensaver', 'allow_embedded', 'is_system']);
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
     * Return content tags for module filters.
     *
     * @return array<int, array{id:int,name:string}>
     */
    private function contentTags(): array
    {
        return Tag::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Tag $tag): array => [
                'id' => (int) $tag->id,
                'name' => (string) $tag->name,
            ])
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
            ->where('name', '!=', 'Deleted')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Determine whether the current user may bypass HTML sanitization.
     *
     * @param Request $request
     * @return bool
     */
    private function canBypassTemplateSanitization(Request $request): bool
    {
        return (bool) ($request->user()?->isAdmin());
    }

    /**
     * Resolve template HTML for save/preview with optional admin bypass.
     *
     * @param Request $request
     * @param string $templateHtml
     * @param bool $allowEmbedded
     * @return string
     */
    private function resolveTemplateHtml(Request $request, string $templateHtml, bool $allowEmbedded = false): string
    {
        if ($this->canBypassTemplateSanitization($request)) {
            return $templateHtml;
        }

        return $this->moduleRenderer->sanitizeTemplate($templateHtml, $allowEmbedded);
    }

    /**
     * Resolve the template presets shown in module create/edit.
     *
     * @param bool $includeScriptTemplates
     * @return array
     */
    private function resolvedTemplatePresets(bool $includeScriptTemplates = true): array
    {
        $configured = (array) config('modules.template_presets', []);
        $legacy = $this->legacyTemplateFactory->presets();

        if ($configured === []) {
            $configured = $legacy;
        }

        if ($configured !== $legacy) {
            foreach ($legacy as $key => $preset) {
                if (!array_key_exists($key, $configured)) {
                    $configured[$key] = $preset;
                }
            }
        }

        $presets = array_filter(
            $configured,
            static fn ($preset): bool => is_array($preset)
                && is_string($preset['label'] ?? null)
                && is_string($preset['template_html'] ?? null),
        );

        if ($includeScriptTemplates) {
            return $presets;
        }

        return array_map(static function ($preset): array {
            if (!is_array($preset)) {
                return [];
            }

            unset($preset['template_js']);

            return $preset;
        }, $presets);
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
        return (bool) ($request->user()?->can('manage_module_javascript'));
    }

    /**
     * Determine whether the current user may use embedded-capable categories.
     *
     * @param Request $request
     * @return bool
     */
    private function canManageEmbeddedModules(Request $request): bool
    {
        return (bool) ($request->user()?->can('manage_embedded_modules'));
    }

    /**
     * Return categories shown in the module editor.
     *
     * Editors without embedded-module permission should not be able to select
     * embedded-capable categories. When editing an already embedded module, keep
     * the current category visible so the read-only state has context.
     *
     * @param Request $request
     * @param Module|null $module
     * @return \Illuminate\Support\Collection
     */
    private function editorModuleCategories(Request $request, ?Module $module = null)
    {
        $currentCategoryId = (int) ($module?->category ?? 0);

        return ModuleCategory::query()
            ->when(
                !$this->canManageEmbeddedModules($request),
                function (Builder $query) use ($currentCategoryId): void {
                    $query->where(function (Builder $nested) use ($currentCategoryId): void {
                        $nested->where('allow_embedded', false);

                        if ($currentCategoryId > 0) {
                            $nested->orWhere('id', $currentCategoryId);
                        }
                    });
                }
            )
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_widget', 'is_screensaver', 'allow_embedded', 'is_system']);
    }

    /**
     * Determine whether the current editor session must become read-only because
     * the module already belongs to an embedded-capable category.
     *
     * @param Request $request
     * @param ModuleCategory|null $category
     * @return bool
     */
    private function embeddedAccessLocked(Request $request, ?ModuleCategory $category): bool
    {
        return (bool) ($category?->allow_embedded)
            && !$this->canManageEmbeddedModules($request);
    }
}
