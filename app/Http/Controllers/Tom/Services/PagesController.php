<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Page;
use App\Models\PageModule;
use App\Models\Profile;
use App\Support\Modules\ModuleRenderer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PagesController extends Controller
{
    /**
     * Create a new instance.
     *
     * @param ModuleRenderer $moduleRenderer
     * @return void
     */
    public function __construct(
        private readonly ModuleRenderer $moduleRenderer,
    ) {
    }

    /**
     * Store a newly created page for the given profile.
     *
     * @param Request $request
     * @param Profile $profile
     * @return JsonResponse
     */
    public function store(Request $request, Profile $profile): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'is_active' => ['nullable', 'boolean'],
            'row_count' => ['required', 'integer', 'min:1', 'max:24'],
            'column_count' => ['required', 'integer', 'min:1', 'max:24'],
            'row_sizes' => ['nullable', 'array'],
            'row_sizes.*' => ['numeric', 'min:1', 'max:100'],
            'column_sizes' => ['nullable', 'array'],
            'column_sizes.*' => ['numeric', 'min:1', 'max:100'],
        ]);

        $page = $profile->pages()->create([
            'name' => trim((string) $validated['name']) !== '' ? (string) $validated['name'] : 'Page',
            'order' => isset($validated['order'])
                ? (int) $validated['order']
                : ((int) ($profile->pages()->max('order') ?? 0) + 1),
            'duration' => isset($validated['duration']) ? (int) $validated['duration'] : null,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
            'row_count' => (int) $validated['row_count'],
            'column_count' => (int) $validated['column_count'],
        ]);

        $this->syncTrackDefinitions(
            page: $page,
            rowCount: (int) $validated['row_count'],
            columnCount: (int) $validated['column_count'],
            rowSizes: is_array($validated['row_sizes'] ?? null) ? $validated['row_sizes'] : [],
            columnSizes: is_array($validated['column_sizes'] ?? null) ? $validated['column_sizes'] : [],
        );

        $page->refresh();

        return response()->json([
            'message' => __('Page created successfully.'),
            'page' => $this->serializePage($page, $request->user()),
        ]);
    }

    /**
     * Update the selected profile page.
     *
     * @param Request $request
     * @param Profile $profile
     * @param Page $page
     * @return JsonResponse
     */
    public function update(Request $request, Profile $profile, Page $page): JsonResponse
    {
        $this->ensurePageBelongsToProfile($profile, $page);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:1', 'max:100000'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'is_active' => ['nullable', 'boolean'],
            'row_count' => ['required', 'integer', 'min:1', 'max:24'],
            'column_count' => ['required', 'integer', 'min:1', 'max:24'],
            'row_sizes' => ['nullable', 'array'],
            'row_sizes.*' => ['numeric', 'min:1', 'max:100'],
            'column_sizes' => ['nullable', 'array'],
            'column_sizes.*' => ['numeric', 'min:1', 'max:100'],
        ]);

        $page->update([
            'name' => trim((string) $validated['name']) !== '' ? (string) $validated['name'] : 'Page',
            'order' => (int) $validated['order'],
            'duration' => isset($validated['duration']) ? (int) $validated['duration'] : null,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : false,
            'row_count' => (int) $validated['row_count'],
            'column_count' => (int) $validated['column_count'],
        ]);

        $this->syncTrackDefinitions(
            page: $page,
            rowCount: (int) $validated['row_count'],
            columnCount: (int) $validated['column_count'],
            rowSizes: is_array($validated['row_sizes'] ?? null) ? $validated['row_sizes'] : [],
            columnSizes: is_array($validated['column_sizes'] ?? null) ? $validated['column_sizes'] : [],
        );
        $this->clampPlacementsWithinGrid($page);

        $page->refresh();

        return response()->json([
            'message' => __('Page updated successfully.'),
            'page' => $this->serializePage($page, $request->user()),
        ]);
    }

    /**
     * Remove the selected page from profile editor.
     *
     * @param Request $request
     * @param Profile $profile
     * @param Page $page
     * @return JsonResponse
     */
    public function destroy(Request $request, Profile $profile, Page $page): JsonResponse
    {
        $this->ensurePageBelongsToProfile($profile, $page);

        if ($profile->pages()->count() <= 1) {
            throw ValidationException::withMessages([
                'page' => __('At least one page must remain in the profile.'),
            ]);
        }

        $page->delete();

        return response()->json([
            'message' => __('Page deleted successfully.'),
        ]);
    }

    /**
     * Place a module on one profile page.
     *
     * @param Request $request
     * @param Profile $profile
     * @param Page $page
     * @return JsonResponse
     */
    public function storeModule(Request $request, Profile $profile, Page $page): JsonResponse
    {
        $this->ensurePageBelongsToProfile($profile, $page);

        $validated = $request->validate([
            'module_id' => [
                'required',
                'uuid',
                Rule::exists('modules', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'row_index' => ['required', 'integer', 'min:1', 'max:24'],
            'column_index' => ['required', 'integer', 'min:1', 'max:24'],
            'row_span' => ['nullable', 'integer', 'min:1', 'max:24'],
            'column_span' => ['nullable', 'integer', 'min:1', 'max:24'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'config_overrides' => ['nullable'],
        ]);

        $placement = [
            'module_id' => (string) $validated['module_id'],
            'row_index' => (int) $validated['row_index'],
            'column_index' => (int) $validated['column_index'],
            'row_span' => isset($validated['row_span']) ? (int) $validated['row_span'] : 1,
            'column_span' => isset($validated['column_span']) ? (int) $validated['column_span'] : 1,
            'order' => isset($validated['order'])
                ? (int) $validated['order']
                : ((int) ($page->modules()->max('order') ?? 0) + 1),
            'config_overrides' => $this->decodeConfigOverrides($request->input('config_overrides')),
        ];

        $this->assertPlacementWithinGrid($page, $placement);
        $this->assertPlacementNoOverlap($page, $placement);

        $page->modules()->create($placement);
        $page->refresh();

        return response()->json([
            'message' => __('Module added to page successfully.'),
            'page' => $this->serializePage($page, $request->user()),
        ]);
    }

    /**
     * Update one placed module on a profile page.
     *
     * @param Request $request
     * @param Profile $profile
     * @param Page $page
     * @param PageModule $placement
     * @return JsonResponse
     */
    public function updateModule(Request $request, Profile $profile, Page $page, PageModule $placement): JsonResponse
    {
        $this->ensurePageBelongsToProfile($profile, $page);
        $this->ensurePlacementBelongsToPage($page, $placement);

        $validated = $request->validate([
            'module_id' => [
                'required',
                'uuid',
                Rule::exists('modules', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'row_index' => ['required', 'integer', 'min:1', 'max:24'],
            'column_index' => ['required', 'integer', 'min:1', 'max:24'],
            'row_span' => ['nullable', 'integer', 'min:1', 'max:24'],
            'column_span' => ['nullable', 'integer', 'min:1', 'max:24'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'config_overrides' => ['nullable'],
        ]);

        $nextPlacement = [
            'module_id' => (string) $validated['module_id'],
            'row_index' => (int) $validated['row_index'],
            'column_index' => (int) $validated['column_index'],
            'row_span' => isset($validated['row_span']) ? (int) $validated['row_span'] : 1,
            'column_span' => isset($validated['column_span']) ? (int) $validated['column_span'] : 1,
            'order' => isset($validated['order']) ? (int) $validated['order'] : (int) $placement->order,
            'config_overrides' => $this->decodeConfigOverrides($request->input('config_overrides')),
        ];

        $this->assertPlacementWithinGrid($page, $nextPlacement);
        $this->assertPlacementNoOverlap($page, $nextPlacement, (int) $placement->id);

        $placement->update($nextPlacement);
        $page->refresh();

        return response()->json([
            'message' => __('Module placement updated successfully.'),
            'page' => $this->serializePage($page, $request->user()),
        ]);
    }

    /**
     * Remove one placed module from profile page.
     *
     * @param Request $request
     * @param Profile $profile
     * @param Page $page
     * @param PageModule $placement
     * @return JsonResponse
     */
    public function destroyModule(Request $request, Profile $profile, Page $page, PageModule $placement): JsonResponse
    {
        $this->ensurePageBelongsToProfile($profile, $page);
        $this->ensurePlacementBelongsToPage($page, $placement);

        $placement->delete();
        $page->refresh();

        return response()->json([
            'message' => __('Module placement removed successfully.'),
            'page' => $this->serializePage($page, $request->user()),
        ]);
    }

    /**
     * Decode config overrides.
     *
     * @param mixed $rawValue
     * @return array
     */
    private function decodeConfigOverrides(mixed $rawValue): array
    {
        if (is_array($rawValue)) {
            return $rawValue;
        }

        if (!is_string($rawValue)) {
            return [];
        }

        $trimmed = trim($rawValue);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw ValidationException::withMessages([
                'config_overrides' => __('Config overrides must be a valid JSON object.'),
            ]);
        }

        return $decoded;
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

        if ($sizes === []) {
            return array_fill(0, $count, round(100 / $count, 4));
        }

        $normalized = array_slice(array_values(array_map(
            static fn ($value): float => max(1.0, (float) $value),
            $sizes
        )), 0, $count);

        while (count($normalized) < $count) {
            $normalized[] = 100 / $count;
        }

        $sum = array_sum($normalized);
        if ($sum <= 0.0) {
            return array_fill(0, $count, round(100 / $count, 4));
        }

        return array_map(
            static fn (float $value): float => round(($value / $sum) * 100, 4),
            $normalized
        );
    }

    /**
     * Sync track definitions.
     *
     * @param Page $page
     * @param int $rowCount
     * @param int $columnCount
     * @param array $rowSizes
     * @param array $columnSizes
     * @return void
     */
    private function syncTrackDefinitions(
        Page $page,
        int $rowCount,
        int $columnCount,
        array $rowSizes = [],
        array $columnSizes = []
    ): void {
        $rowSizes = $this->normalizeTrackSizes($rowSizes, $rowCount);
        $columnSizes = $this->normalizeTrackSizes($columnSizes, $columnCount);

        for ($index = 1; $index <= $rowCount; $index++) {
            $page->rows()->updateOrCreate(
                ['index' => $index],
                ['height' => sprintf('%.4f%%', $rowSizes[$index - 1])]
            );
        }
        $page->rows()->where('index', '>', $rowCount)->delete();

        for ($index = 1; $index <= $columnCount; $index++) {
            $page->columns()->updateOrCreate(
                ['index' => $index],
                ['width' => sprintf('%.4f%%', $columnSizes[$index - 1])]
            );
        }
        $page->columns()->where('index', '>', $columnCount)->delete();
    }

    /**
     * Clamp all placements to the current page grid after track changes.
     *
     * @param Page $page
     * @return void
     */
    private function clampPlacementsWithinGrid(Page $page): void
    {
        $rowCount = max(1, (int) $page->row_count);
        $columnCount = max(1, (int) $page->column_count);

        foreach ($page->modules()->get() as $placement) {
            $rowIndex = max(1, min($rowCount, (int) $placement->row_index));
            $columnIndex = max(1, min($columnCount, (int) $placement->column_index));
            $rowSpan = max(1, min((int) $placement->row_span, $rowCount - $rowIndex + 1));
            $columnSpan = max(1, min((int) $placement->column_span, $columnCount - $columnIndex + 1));

            $placement->update([
                'row_index' => $rowIndex,
                'column_index' => $columnIndex,
                'row_span' => $rowSpan,
                'column_span' => $columnSpan,
            ]);
        }
    }

    /**
     * Validate one placement against page bounds.
     *
     * @param Page $page
     * @param array $placement
     * @return void
     */
    private function assertPlacementWithinGrid(Page $page, array $placement): void
    {
        $rowCount = max(1, (int) $page->row_count);
        $columnCount = max(1, (int) $page->column_count);
        $rowEnd = (int) $placement['row_index'] + (int) $placement['row_span'] - 1;
        $columnEnd = (int) $placement['column_index'] + (int) $placement['column_span'] - 1;

        if ($rowEnd > $rowCount || $columnEnd > $columnCount) {
            throw ValidationException::withMessages([
                'placement' => __('Module placement exceeds page bounds.'),
            ]);
        }
    }

    /**
     * Ensure one placement does not overlap existing modules.
     *
     * @param Page $page
     * @param array $candidate
     * @param int|null $ignorePlacementId
     * @return void
     */
    private function assertPlacementNoOverlap(Page $page, array $candidate, ?int $ignorePlacementId = null): void
    {
        $placements = $page->modules()->get();

        foreach ($placements as $placement) {
            if ($ignorePlacementId !== null && (int) $placement->id === $ignorePlacementId) {
                continue;
            }

            if ($this->placementsOverlap($candidate, [
                'row_index' => (int) $placement->row_index,
                'column_index' => (int) $placement->column_index,
                'row_span' => (int) $placement->row_span,
                'column_span' => (int) $placement->column_span,
            ])) {
                throw ValidationException::withMessages([
                    'placement' => __('Module placements cannot overlap.'),
                ]);
            }
        }
    }

    /**
     * Determine overlap between two rectangle placements.
     *
     * @param array $a
     * @param array $b
     * @return bool
     */
    private function placementsOverlap(array $a, array $b): bool
    {
        $aRowStart = (int) $a['row_index'];
        $aRowEnd = $aRowStart + max(1, (int) $a['row_span']) - 1;
        $aColumnStart = (int) $a['column_index'];
        $aColumnEnd = $aColumnStart + max(1, (int) $a['column_span']) - 1;

        $bRowStart = (int) $b['row_index'];
        $bRowEnd = $bRowStart + max(1, (int) $b['row_span']) - 1;
        $bColumnStart = (int) $b['column_index'];
        $bColumnEnd = $bColumnStart + max(1, (int) $b['column_span']) - 1;

        $rowsOverlap = $aRowStart <= $bRowEnd && $aRowEnd >= $bRowStart;
        $columnsOverlap = $aColumnStart <= $bColumnEnd && $aColumnEnd >= $bColumnStart;

        return $rowsOverlap && $columnsOverlap;
    }

    /**
     * Ensure one page belongs to the resolved profile route parameter.
     *
     * @param Profile $profile
     * @param Page $page
     * @return void
     */
    private function ensurePageBelongsToProfile(Profile $profile, Page $page): void
    {
        if ((string) $page->profile_id !== (string) $profile->id) {
            abort(404);
        }
    }

    /**
     * Ensure one placement belongs to the resolved page route parameter.
     *
     * @param Page $page
     * @param PageModule $placement
     * @return void
     */
    private function ensurePlacementBelongsToPage(Page $page, PageModule $placement): void
    {
        if ((int) $placement->page_id !== (int) $page->id) {
            abort(404);
        }
    }

    /**
     * Extract track sizes.
     *
     * @param iterable $tracks
     * @param int $count
     * @param string $field
     * @return array
     */
    private function extractTrackSizes(iterable $tracks, int $count, string $field): array
    {
        if ($count <= 0) {
            return [];
        }

        $indexedValues = [];
        foreach ($tracks as $track) {
            $index = (int) ($track->index ?? 0);
            if ($index <= 0 || $index > $count) {
                continue;
            }

            $raw = (string) ($track->{$field} ?? '');
            if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $raw, $match) === 1) {
                $indexedValues[$index] = (float) $match[1];
            }
        }

        $values = [];
        for ($index = 1; $index <= $count; $index++) {
            $values[] = $indexedValues[$index] ?? (100 / $count);
        }

        return $this->normalizeTrackSizes($values, $count);
    }

    /**
     * Serialize page.
     *
     * @param Page $page
     * @param Authenticatable|null $viewer
     * @return array
     */
    private function serializePage(Page $page, ?Authenticatable $viewer = null): array
    {
        $page->load([
            'rows',
            'columns',
            'modules.module',
            'modules.module.moduleCategory:id,name,slug,is_widget,is_system',
        ]);

        return [
            'id' => (int) $page->id,
            'name' => (string) $page->name,
            'order' => (int) $page->order,
            'duration' => $page->duration !== null ? (int) $page->duration : null,
            'is_active' => (bool) $page->is_active,
            'row_count' => (int) $page->row_count,
            'column_count' => (int) $page->column_count,
            'row_sizes' => $this->extractTrackSizes($page->rows, (int) $page->row_count, 'height'),
            'column_sizes' => $this->extractTrackSizes($page->columns, (int) $page->column_count, 'width'),
            'placements' => $page->modules
                ->map(fn (PageModule $placement): array => $this->serializePlacement($placement, $viewer))
                ->values()
                ->all(),
        ];
    }

    /**
     * Serialize placement.
     *
     * @param PageModule $placement
     * @param Authenticatable|null $viewer
     * @return array
     */
    private function serializePlacement(PageModule $placement, ?Authenticatable $viewer = null): array
    {
        return [
            'id' => (int) $placement->id,
            'module_id' => (string) $placement->module_id,
            'module_name' => (string) ($placement->module?->name ?? 'Unknown module'),
            'module_category' => (string) ($placement->module?->moduleCategory?->name ?? ''),
            'row_index' => (int) $placement->row_index,
            'column_index' => (int) $placement->column_index,
            'row_span' => (int) $placement->row_span,
            'column_span' => (int) $placement->column_span,
            'order' => (int) $placement->order,
            'config_overrides' => $placement->config_overrides ?? [],
            'rendered_html' => $this->safeRenderPlacementHtml(
                module: $placement->module,
                config: $placement->config_overrides ?? [],
                viewer: $viewer,
            ),
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
