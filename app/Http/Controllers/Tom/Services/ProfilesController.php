<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\Services\ProfileRequest;
use App\Models\Module;
use App\Models\Page;
use App\Models\PageColumn;
use App\Models\PageModule;
use App\Models\PageRow;
use App\Models\Profile;
use App\Support\Modules\ModuleRenderer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfilesController extends Controller
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
     * Display a listing of profiles.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $filters = ['search' => $search];

        $profiles = Profile::query()
            ->with(['createdBy:id,name'])
            ->withCount(['pages', 'screens'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $searchSuggestions = Profile::query()
            ->select('name')
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->limit(8)
            ->pluck('name')
            ->values();

        return view('services.profiles.index', [
            'profiles' => $profiles,
            'filters' => $filters,
            'searchSuggestions' => $searchSuggestions,
        ]);
    }

    /**
     * Display the requested profile.
     *
     * @param Profile $profile
     * @return View
     */
    public function show(Profile $profile): View
    {
        $profile->loadMissing([
            'screens:id,name,profile_id',
            'pages' => fn ($query) => $query->orderBy('order')->orderBy('id'),
            'pages.rows' => fn ($query) => $query->orderBy('index'),
            'pages.columns' => fn ($query) => $query->orderBy('index'),
            'pages.modules' => fn ($query) => $query->orderBy('order')->orderBy('id'),
            'pages.modules.module.moduleCategory:id,name,slug,is_widget,is_system',
        ]);

        return view('services.profiles.show', ['profile' => $profile]);
    }

    /**
     * Show the profile creation form.
     *
     * @return View
     */
    public function create(): View
    {
        return view('services.profiles.create');
    }

    /**
     * Store a newly created profile.
     *
     * @param ProfileRequest $request
     * @return RedirectResponse
     */
    public function store(ProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $profile = DB::transaction(function () use ($validated, $request): Profile {
            $slug = $this->resolveUniqueSlug(
                name: (string) $validated['name'],
                requestedSlug: is_string($validated['slug'] ?? null) ? $validated['slug'] : null,
            );

            $profile = Profile::create([
                'name' => (string) $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'version' => '1',
                'settings' => is_array($validated['settings'] ?? null) ? $validated['settings'] : [],
                'created_by' => $request->user()?->id,
            ]);

            $rowCount = max(1, min(24, (int) ($validated['initial_page_rows'] ?? 1)));
            $columnCount = max(1, min(24, (int) ($validated['initial_page_columns'] ?? 1)));

            $page = Page::create([
                'profile_id' => $profile->id,
                'name' => trim((string) ($validated['initial_page_name'] ?? '')) !== ''
                    ? (string) $validated['initial_page_name']
                    : 'Page 1',
                'order' => 1,
                'row_count' => $rowCount,
                'column_count' => $columnCount,
                'is_active' => true,
                'duration' => isset($validated['initial_page_duration']) && $validated['initial_page_duration'] !== null
                    ? (int) $validated['initial_page_duration']
                    : null,
            ]);

            $defaultRowSize = round(100 / $rowCount, 4);
            $defaultColumnSize = round(100 / $columnCount, 4);

            for ($index = 1; $index <= $rowCount; $index++) {
                PageRow::create([
                    'page_id' => $page->id,
                    'index' => $index,
                    'height' => sprintf('%.4f%%', $defaultRowSize),
                ]);
            }

            for ($index = 1; $index <= $columnCount; $index++) {
                PageColumn::create([
                    'page_id' => $page->id,
                    'index' => $index,
                    'width' => sprintf('%.4f%%', $defaultColumnSize),
                ]);
            }

            return $profile;
        });

        $this->logEvent(
            typeName: 'profile_created',
            message: 'Profile "' . $profile->name . '" was created.',
            context: [
                'action' => 'profile.store',
                'profile_id' => $profile->id,
                'profile_slug' => $profile->slug,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('profiles.index')
            ->with('success', __('Profile created successfully.'));
    }

    /**
     * Show the profile edit form.
     *
     * @param Profile $profile
     * @return View
     */
    public function edit(Profile $profile): View
    {
        $profile->loadMissing([
            'pages' => fn ($query) => $query->orderBy('order')->orderBy('id'),
            'pages.rows' => fn ($query) => $query->orderBy('index'),
            'pages.columns' => fn ($query) => $query->orderBy('index'),
            'pages.modules' => fn ($query) => $query->orderBy('order')->orderBy('id'),
            'pages.modules.module.moduleCategory:id,name,slug,is_widget,is_system',
        ]);

        $editorPages = $profile->pages
            ->sortBy(fn (Page $page): string => sprintf('%08d-%08d', (int) $page->order, (int) $page->id))
            ->values()
            ->map(fn (Page $page): array => $this->serializeEditorPage($page, auth()->user()))
            ->all();

        $moduleCatalog = Module::query()
            ->with('moduleCategory:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category'])
            ->map(fn (Module $module): array => [
                'id' => (string) $module->id,
                'name' => (string) $module->name,
                'category' => (string) ($module->moduleCategory?->name ?? ''),
            ])
            ->values()
            ->all();

        return view('services.profiles.edit', [
            'profile' => $profile,
            'editorPages' => $editorPages,
            'moduleCatalog' => $moduleCatalog,
            'editorRoutes' => [
                'pageStore' => route('profile.pages.store', ['profile' => $profile]),
                'pageUpdate' => route('profile.pages.update', ['profile' => $profile, 'page' => '__PAGE__']),
                'pageDestroy' => route('profile.pages.destroy', ['profile' => $profile, 'page' => '__PAGE__']),
                'placementStore' => route('profile.pages.modules.store', ['profile' => $profile, 'page' => '__PAGE__']),
                'placementUpdate' => route('profile.pages.modules.update', ['profile' => $profile, 'page' => '__PAGE__', 'placement' => '__PLACEMENT__']),
                'placementDestroy' => route('profile.pages.modules.destroy', ['profile' => $profile, 'page' => '__PAGE__', 'placement' => '__PLACEMENT__']),
            ],
        ]);
    }

    /**
     * Update the given profile.
     *
     * @param ProfileRequest $request
     * @param Profile $profile
     * @return RedirectResponse
     */
    public function update(ProfileRequest $request, Profile $profile): RedirectResponse
    {
        $validated = $request->validated();
        $before = [
            'name' => $profile->name,
            'slug' => $profile->slug,
            'description' => $profile->description,
            'settings' => $profile->settings,
            'version' => $profile->version,
        ];

        $profile->update([
            'name' => (string) $validated['name'],
            'slug' => $this->resolveUniqueSlug(
                name: (string) $validated['name'],
                requestedSlug: is_string($validated['slug'] ?? null) ? $validated['slug'] : null,
                ignoreProfileId: $profile->id,
            ),
            'description' => $validated['description'] ?? null,
            'settings' => is_array($validated['settings'] ?? null) ? $validated['settings'] : [],
            'version' => $this->profileVersionNext($profile),
        ]);

        $this->logEvent(
            typeName: 'profile_edited',
            message: 'Profile "' . $profile->name . '" was updated.',
            context: [
                'action' => 'profile.update',
                'profile_id' => $profile->id,
                'old_name' => $before['name'],
                'new_name' => $profile->name,
                'old_slug' => $before['slug'],
                'new_slug' => $profile->slug,
                'old_description' => $before['description'],
                'new_description' => $profile->description,
                'old_settings' => $before['settings'],
                'new_settings' => $profile->settings,
                'old_version' => $before['version'],
                'new_version' => $profile->version,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('profile.edit', ['profile' => $profile])
            ->with('success', __('Profile updated successfully.'));
    }

    /**
     * Remove the given profile.
     *
     * @param Request $request
     * @param Profile $profile
     * @return RedirectResponse
     */
    public function destroy(Request $request, Profile $profile): RedirectResponse
    {
        if ($profile->screens()->exists()) {
            return back()->withErrors([
                'profile' => __('Profile cannot be deleted while screens are assigned.'),
            ]);
        }

        $profileName = $profile->name;
        $profileId = $profile->id;
        DB::transaction(function () use ($profile): void {
            // Profile uses soft deletes, so DB cascades do not execute from profile->delete().
            // Remove concrete page rows first so page_modules/page_rows/page_columns cascade correctly.
            Page::query()->where('profile_id', $profile->id)->delete();
            $profile->delete();
        });

        $this->logEvent(
            typeName: 'profile_deleted',
            message: 'Profile "' . $profileName . '" was deleted.',
            context: [
                'action' => 'profile.destroy',
                'profile_id' => $profileId,
                'profile_name' => $profileName,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('profiles.index')
            ->with('success', __('Profile deleted successfully.'));
    }

    /**
     * Serialize one page for the profile layout editor.
     *
     * @param Page $page
     * @param Authenticatable|null $viewer
     * @return array
     */
    private function serializeEditorPage(Page $page, ?Authenticatable $viewer = null): array
    {
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
                ->sortBy(fn (PageModule $placement): string => sprintf('%08d-%08d', (int) $placement->order, (int) $placement->id))
                ->values()
                ->map(fn (PageModule $placement): array => [
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
                ])
                ->all(),
        ];
    }

    /**
     * Extract normalized percentage track sizes from row/column models.
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
            $values[] = max(0.0001, (float) ($indexedValues[$index] ?? (100 / $count)));
        }

        $total = array_sum($values);
        if ($total <= 0.0) {
            return array_fill(0, $count, round(100 / $count, 4));
        }

        return array_map(
            static fn (float $value): float => round(($value / $total) * 100, 4),
            $values,
        );
    }

    /**
     * Render one page placement safely for editor previews.
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

    /**
     * Resolve a unique profile slug from name and optional requested slug.
     *
     * @param string $name
     * @param string|null $requestedSlug
     * @param string|null $ignoreProfileId
     * @return string
     */
    private function resolveUniqueSlug(string $name, ?string $requestedSlug = null, ?string $ignoreProfileId = null): string
    {
        $seed = trim((string) $requestedSlug) !== '' ? (string) $requestedSlug : $name;
        $baseSlug = Str::slug($seed);
        if ($baseSlug === '') {
            $baseSlug = 'profile';
        }

        $slug = $baseSlug;
        $suffix = 2;
        while (
            Profile::query()
                ->where('slug', $slug)
                ->when($ignoreProfileId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreProfileId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Calculate the next profile version string.
     *
     * @param Profile $profile
     * @return string
     */
    private function profileVersionNext(Profile $profile): string
    {
        $current = trim((string) $profile->version);
        if ($current === '' || !ctype_digit($current)) {
            return '1';
        }

        return (string) (((int) $current) + 1);
    }
}
