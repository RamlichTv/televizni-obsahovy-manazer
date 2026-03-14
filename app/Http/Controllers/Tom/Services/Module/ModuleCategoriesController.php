<?php

namespace App\Http\Controllers\Tom\Services\Module;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\Services\ModuleCategoryRequest;
use App\Models\Module;
use App\Models\ModuleCategory;
use App\Support\Modules\Template\ModuleTemplateStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Manage module category CRUD operations.
 *
 * Responsibilities:
 * - Enforce admin-only handling of system categories.
 * - Keep slugs unique and deterministic.
 * - Move module template directories when a category slug changes.
 */
class ModuleCategoriesController extends Controller
{
    /**
     * Create controller instance.
     *
     * @param ModuleTemplateStorage $templateStorage Filesystem helper for template assets.
     * @return void
     */
    public function __construct(
        private readonly ModuleTemplateStorage $templateStorage,
    ) {
    }

    /**
     * Store a newly created resource.
     *
     * @param ModuleCategoryRequest $request
     * @return RedirectResponse
     */
    public function store(ModuleCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $isAdmin = (bool) ($request->user()?->isAdmin());
        $canManageEmbedded = (bool) ($request->user()?->can('manage_embedded_modules'));

        // Non-admin users can never mark categories as system categories.
        $category = ModuleCategory::create([
            'name' => $validated['name'],
            'slug' => $this->resolveUniqueSlug($validated['name']),
            'is_widget' => (bool) ($validated['is_widget'] ?? false),
            'is_screensaver' => (bool) ($validated['is_screensaver'] ?? false),
            'allow_embedded' => $canManageEmbedded ? (bool) ($validated['allow_embedded'] ?? false) : false,
            'is_system' => $isAdmin ? (bool) ($validated['is_system'] ?? false) : false,
        ]);

        $this->logEvent(
            typeName: 'module_category_created',
            message: 'Module category "' . $category->name . '" was created.',
            context: [
                'action' => 'module.categories.store',
                'category_id' => $category->id,
                'category_slug' => $category->slug,
                'is_widget' => (bool) $category->is_widget,
                'is_screensaver' => (bool) $category->is_screensaver,
                'allow_embedded' => (bool) $category->allow_embedded,
                'is_system' => (bool) $category->is_system,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('modules.index')
            ->with('success', 'Module category created successfully.');
    }

    /**
     * Update the current resource.
     *
     * @param ModuleCategoryRequest $request
     * @param ModuleCategory $category
     * @return RedirectResponse
     */
    public function update(ModuleCategoryRequest $request, ModuleCategory $category): RedirectResponse
    {
        $isAdmin = (bool) ($request->user()?->isAdmin());
        $canManageEmbedded = (bool) ($request->user()?->can('manage_embedded_modules'));

        // System categories are immutable for non-admin users.
        if ($category->is_system && !$isAdmin) {
            $this->logFailure(
                message: 'Module category update failed: system category cannot be edited by non-admin user.',
                context: [
                    'action' => 'module.categories.update',
                    'category_id' => $category->id,
                    'category_slug' => $category->slug,
                ],
                userId: $request->user()?->id,
            );

            return back()->withErrors([
                'category' => 'System categories cannot be edited.',
            ]);
        }

        $validated = $request->validated();
        $oldSlug = (string) $category->slug;
        $oldName = (string) $category->name;
        $oldWidget = (bool) $category->is_widget;
        $oldScreensaver = (bool) $category->is_screensaver;
        $oldAllowEmbedded = (bool) $category->allow_embedded;
        $oldSystem = (bool) $category->is_system;
        $newSlug = $this->resolveUniqueSlug($validated['name'], $category->id);

        // Preserve existing system flag when editor is not an admin.
        $category->update([
            'name' => $validated['name'],
            'slug' => $newSlug,
            'is_widget' => (bool) ($validated['is_widget'] ?? false),
            'is_screensaver' => (bool) ($validated['is_screensaver'] ?? false),
            'allow_embedded' => $canManageEmbedded
                ? (bool) ($validated['allow_embedded'] ?? false)
                : (bool) $category->allow_embedded,
            'is_system' => $isAdmin ? (bool) ($validated['is_system'] ?? false) : (bool) $category->is_system,
        ]);

        // Keep on-disk template paths in sync with category slug changes.
        if ($oldSlug !== '' && $oldSlug !== $newSlug) {
            $modules = Module::query()
                ->where('category', $category->id)
                ->with('createdBy:id,username')
                ->get();

            $this->templateStorage->moveCategoryDirectories($modules, $oldSlug, $newSlug);

            foreach ($modules as $module) {
                if (!is_string($module->template_path) || $module->template_path === '') {
                    continue;
                }

                // Rewrite persisted template path to new category directory.
                $module->update([
                    'template_path' => $this->templateStorage->rewritePathCategory(
                        $module->template_path,
                        $oldSlug,
                        $newSlug,
                    ),
                ]);
            }
        }

        $this->logEvent(
            typeName: 'module_category_edited',
            message: 'Module category "' . $category->name . '" was updated.',
            context: [
                'action' => 'module.categories.update',
                'category_id' => $category->id,
                'old_name' => $oldName,
                'new_name' => $category->name,
                'old_slug' => $oldSlug,
                'new_slug' => $newSlug,
                'old_is_widget' => $oldWidget,
                'new_is_widget' => (bool) $category->is_widget,
                'old_is_screensaver' => $oldScreensaver,
                'new_is_screensaver' => (bool) $category->is_screensaver,
                'old_allow_embedded' => $oldAllowEmbedded,
                'new_allow_embedded' => (bool) $category->allow_embedded,
                'old_is_system' => $oldSystem,
                'new_is_system' => (bool) $category->is_system,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('modules.index')
            ->with('success', 'Module category updated successfully.');
    }

    /**
     * Delete the current resource.
     *
     * @param Request $request
     * @param ModuleCategory $category
     * @return RedirectResponse
     */
    public function destroy(Request $request, ModuleCategory $category): RedirectResponse
    {
        $isAdmin = (bool) ($request->user()?->isAdmin());

        // System categories cannot be deleted by non-admin users.
        if ($category->is_system && !$isAdmin) {
            $this->logFailure(
                message: 'Module category deletion failed: system category cannot be deleted by non-admin user.',
                context: [
                    'action' => 'module.categories.destroy',
                    'category_id' => $category->id,
                    'category_slug' => $category->slug,
                ],
                userId: $request->user()?->id,
            );

            return back()->withErrors([
                'category' => 'System categories cannot be deleted.',
            ]);
        }

        // Prevent orphaned modules by blocking deletion while assignments exist.
        if ($category->modules()->exists()) {
            $this->logFailure(
                message: 'Module category deletion failed because modules are still assigned.',
                context: [
                    'action' => 'module.categories.destroy',
                    'category_id' => $category->id,
                    'category_slug' => $category->slug,
                ],
                userId: $request->user()?->id,
            );

            return back()->withErrors([
                'category' => 'Category cannot be deleted while modules are assigned.',
            ]);
        }

        $categoryName = $category->name;
        $categoryId = $category->id;
        $category->delete();

        $this->logEvent(
            typeName: 'module_category_deleted',
            message: 'Module category "' . $categoryName . '" was deleted.',
            context: [
                'action' => 'module.categories.destroy',
                'category_id' => $categoryId,
                'category_name' => $categoryName,
            ],
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('modules.index')
            ->with('success', 'Module category deleted successfully.');
    }

    /**
     * Resolve unique slug.
     *
     * @param string $name
     * @param int|null $ignoreId
     * @return string
     */
    private function resolveUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        // Use URL-safe base slug and fallback label when name cannot be slugified.
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'category';
        }

        $slug = $baseSlug;
        $suffix = 2;

        // Increment numeric suffix until an unused slug is found.
        while (
            ModuleCategory::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
