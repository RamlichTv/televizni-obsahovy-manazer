<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\DashboardLayoutRequest;
use App\Models\Module;
use App\Models\User;
use App\Support\Modules\ModuleRenderer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param ModuleRenderer $moduleRenderer
     * @return void
     */
    public function __construct(
        private readonly ModuleRenderer $moduleRenderer,
    ) {
    }

    /**
     * Display the dashboard preview.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $modules = $this->widgetModules();
        $layout = $this->resolveUserLayout($user, $modules);

        return view('dashboard.index', [
            'dashboardLayout' => $this->presentLayout($layout, $modules, $user),
        ]);
    }

    /**
     * Display the dashboard editor.
     *
     * @param Request $request
     * @return View
     */
    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $modules = $this->widgetModules();
        $layout = $this->resolveUserLayout($user, $modules);

        $moduleCatalog = $modules
            ->map(fn (Module $module): array => [
                'id' => (string) $module->id,
                'name' => (string) $module->name,
                'category' => (string) ($module->moduleCategory?->name ?? ''),
                'rendered_html' => $this->safeRenderPlacementHtml(
                    module: $module,
                    config: [],
                    viewer: $user,
                ),
            ])
            ->values()
            ->all();

        return view('dashboard.edit', [
            'dashboardLayout' => $this->presentLayout($layout, $modules, $user),
            'moduleCatalog' => $moduleCatalog,
            'dashboardRoutes' => [
                'save' => route('dashboard.update'),
            ],
        ]);
    }

    /**
     * Persist dashboard layout changes.
     *
     * @param DashboardLayoutRequest $request
     * @return JsonResponse
     */
    public function update(DashboardLayoutRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $modules = $this->widgetModules();

        $layout = $this->normalizeLayout(
            layout: (array) $request->validated('layout', []),
            modules: $modules,
            strict: true,
        );

        $user->forceFill([
            'dashboard_layout' => $layout,
        ])->save();

        return response()->json([
            'message' => __('Dashboard layout saved.'),
            'layout' => $this->presentLayout($layout, $modules, $user),
        ]);
    }

    /**
     * Widget modules.
     *
     * @return Collection
     */
    private function widgetModules(): Collection
    {
        return Module::query()
            ->with('moduleCategory:id,name,slug,is_widget,is_system')
            ->where('is_active', true)
            ->whereHas('moduleCategory', function (Builder $query): void {
                $query->where('is_widget', true);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve user layout.
     *
     * @param User $user
     * @param Collection $modules
     * @return array
     */
    private function resolveUserLayout(User $user, Collection $modules): array
    {
        $rawLayout = $user->dashboard_layout;
        if (!is_array($rawLayout)) {
            $rawLayout = $this->defaultLayout();
        }

        return $this->normalizeLayout(
            layout: $rawLayout,
            modules: $modules,
            strict: false,
        );
    }

    /**
     * Default layout.
     *
     * @return array
     */
    private function defaultLayout(): array
    {
        return [
            'row_count' => 1,
            'column_count' => 1,
        ];
    }

    /**
     * Normalize layout.
     *
     * @param array $layout
     * @param Collection $modules
     * @param bool $strict
     * @return array
     */
    private function normalizeLayout(array $layout, Collection $modules, bool $strict): array
    {
        $modulesById = $modules->keyBy(fn (Module $module): string => (string) $module->id);
        $rowCount = $this->clamp((int) ($layout['row_count'] ?? 2), 1, 24);
        $columnCount = $this->clamp((int) ($layout['column_count'] ?? 2), 1, 24);

        $rowSizes = $this->normalizeTrackSizes(
            is_array($layout['row_sizes'] ?? null) ? $layout['row_sizes'] : [],
            $rowCount,
        );
        $columnSizes = $this->normalizeTrackSizes(
            is_array($layout['column_sizes'] ?? null) ? $layout['column_sizes'] : [],
            $columnCount,
        );

        $placements = [];
        $idsInUse = [];
        $nextId = 1;
        $rawPlacements = is_array($layout['placements'] ?? null) ? $layout['placements'] : [];

        foreach ($rawPlacements as $placement) {
            if (!is_array($placement)) {
                continue;
            }

            $moduleId = trim((string) ($placement['module_id'] ?? ''));
            if ($moduleId === '') {
                continue;
            }

            $module = $modulesById->get($moduleId);
            if (!$module instanceof Module) {
                // In strict mode (save endpoint) we reject invalid modules.
                // In non-strict mode (index/edit bootstrap) we silently discard stale entries.
                if ($strict) {
                    throw ValidationException::withMessages([
                        'layout.placements' => 'Only active widget modules can be placed on dashboard.',
                    ]);
                }

                continue;
            }

            $rowIndex = $this->clamp((int) ($placement['row_index'] ?? 1), 1, $rowCount);
            $columnIndex = $this->clamp((int) ($placement['column_index'] ?? 1), 1, $columnCount);
            $rowSpan = max(1, (int) ($placement['row_span'] ?? 1));
            $columnSpan = max(1, (int) ($placement['column_span'] ?? 1));
            $rowSpan = min($rowSpan, $rowCount - $rowIndex + 1);
            $columnSpan = min($columnSpan, $columnCount - $columnIndex + 1);

            $id = max(1, (int) ($placement['id'] ?? $nextId));
            while (isset($idsInUse[$id])) {
                $id++;
            }
            $idsInUse[$id] = true;
            $nextId = $id + 1;

            $candidate = [
                'id' => $id,
                'module_id' => $moduleId,
                'row_index' => $rowIndex,
                'column_index' => $columnIndex,
                'row_span' => $rowSpan,
                'column_span' => $columnSpan,
                'order' => max(0, (int) ($placement['order'] ?? 0)),
                'config_overrides' => is_array($placement['config_overrides'] ?? null)
                    ? $placement['config_overrides']
                    : [],
            ];

            if ($this->hasOverlap($candidate, $placements)) {
                // Prevent double-occupying cells after clamping spans and coordinates.
                if ($strict) {
                    throw ValidationException::withMessages([
                        'layout.placements' => 'Dashboard modules cannot overlap each other.',
                    ]);
                }

                continue;
            }

            $placements[] = $candidate;
        }

        return [
            'row_count' => $rowCount,
            'column_count' => $columnCount,
            'row_sizes' => $rowSizes,
            'column_sizes' => $columnSizes,
            'placements' => $placements,
        ];
    }

    /**
     * Determine whether overlap.
     *
     * @param array $candidate
     * @param array $placements
     * @return bool
     */
    private function hasOverlap(array $candidate, array $placements): bool
    {
        foreach ($placements as $placement) {
            if ($this->placementsOverlap($candidate, $placement)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Placements overlap.
     *
     * @param array $first
     * @param array $second
     * @return bool
     */
    private function placementsOverlap(array $first, array $second): bool
    {
        $firstRowStart = (int) $first['row_index'];
        $firstRowEnd = $firstRowStart + ((int) $first['row_span']) - 1;
        $firstColumnStart = (int) $first['column_index'];
        $firstColumnEnd = $firstColumnStart + ((int) $first['column_span']) - 1;

        $secondRowStart = (int) $second['row_index'];
        $secondRowEnd = $secondRowStart + ((int) $second['row_span']) - 1;
        $secondColumnStart = (int) $second['column_index'];
        $secondColumnEnd = $secondColumnStart + ((int) $second['column_span']) - 1;

        if ($firstRowEnd < $secondRowStart || $secondRowEnd < $firstRowStart) {
            return false;
        }

        if ($firstColumnEnd < $secondColumnStart || $secondColumnEnd < $firstColumnStart) {
            return false;
        }

        return true;
    }

    /**
     * Normalize track sizes.
     *
     * @param array $sizes
     * @param int $count
     * @return array
     */
    private function normalizeTrackSizes(array $sizes, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $clean = collect($sizes)
            ->map(fn ($value): float => max(1.0, (float) $value))
            ->take($count)
            ->values()
            ->all();

        while (count($clean) < $count) {
            $clean[] = 100 / $count;
        }

        $sum = array_sum($clean);
        if ($sum <= 0.0) {
            return array_fill(0, $count, round(100 / $count, 4));
        }

        return array_map(
            static fn (float $value): float => round(($value / $sum) * 100, 4),
            $clean
        );
    }

    /**
     * Clamp an integer into a defined range.
     *
     * @param int $value
     * @param int $min
     * @param int $max
     * @return int
     */
    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    /**
     * Present layout.
     *
     * @param array $layout
     * @param Collection $modules
     * @param Authenticatable|null $viewer
     * @return array
     */
    private function presentLayout(array $layout, Collection $modules, ?Authenticatable $viewer): array
    {
        $modulesById = $modules->keyBy(fn (Module $module): string => (string) $module->id);

        // Attach stable module metadata and rendered HTML to each placement
        // so preview/index views can render without additional queries.
        $placements = collect($layout['placements'] ?? [])
            ->filter(fn ($placement): bool => is_array($placement))
            ->map(function (array $placement) use ($modulesById, $viewer): array {
                $module = $modulesById->get((string) ($placement['module_id'] ?? ''));

                return [
                    'id' => (int) ($placement['id'] ?? 0),
                    'module_id' => (string) ($placement['module_id'] ?? ''),
                    'module_name' => (string) ($module?->name ?? 'Unknown module'),
                    'module_category' => (string) ($module?->moduleCategory?->name ?? ''),
                    'row_index' => (int) ($placement['row_index'] ?? 1),
                    'column_index' => (int) ($placement['column_index'] ?? 1),
                    'row_span' => (int) ($placement['row_span'] ?? 1),
                    'column_span' => (int) ($placement['column_span'] ?? 1),
                    'order' => (int) ($placement['order'] ?? 0),
                    'config_overrides' => is_array($placement['config_overrides'] ?? null)
                        ? $placement['config_overrides']
                        : [],
                    'rendered_html' => $this->safeRenderPlacementHtml(
                        module: $module,
                        config: is_array($placement['config_overrides'] ?? null) ? $placement['config_overrides'] : [],
                        viewer: $viewer,
                    ),
                ];
            })
            ->values()
            ->all();

        return [
            'row_count' => (int) ($layout['row_count'] ?? 1),
            'column_count' => (int) ($layout['column_count'] ?? 1),
            'row_sizes' => is_array($layout['row_sizes'] ?? null) ? $layout['row_sizes'] : [100],
            'column_sizes' => is_array($layout['column_sizes'] ?? null) ? $layout['column_sizes'] : [100],
            'placements' => $placements,
        ];
    }

    /**
     * Safe render placement html.
     *
     * @param Module|null $module
     * @param array $config
     * @param Authenticatable|null $viewer
     * @return string
     */
    private function safeRenderPlacementHtml(?Module $module, array $config, ?Authenticatable $viewer = null): string
    {
        if (!$module instanceof Module) {
            return '<div class="text-xs text-gray-500">Module unavailable.</div>';
        }

        try {
            return $this->moduleRenderer->renderHtml(
                module: $module,
                instanceConfig: $config,
                viewer: $viewer,
            );
        } catch (\Throwable) {
            return '<div class="text-xs text-red-500">Module failed to render.</div>';
        }
    }
}
