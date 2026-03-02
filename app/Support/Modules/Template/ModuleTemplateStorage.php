<?php

namespace App\Support\Modules\Template;

use App\Models\Module;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleTemplateStorage
{
    /**
     * Read current module template markup.
     *
     * @param Module $module
     * @return string
     */
    public function readCurrentTemplate(Module $module): string
    {
        return $this->readAsset((string) ($module->template_path ?? ''));
    }

    /**
     * Read current module stylesheet.
     *
     * @param Module $module
     * @return string
     */
    public function readCurrentStylesheet(Module $module): string
    {
        return $this->readAsset((string) ($module->style_path ?? ''));
    }

    /**
     * Read current module script.
     *
     * @param Module $module
     * @return string
     */
    public function readCurrentScript(Module $module): string
    {
        return $this->readAsset((string) ($module->script_path ?? ''));
    }

    /**
     * Read revision template markup.
     *
     * @param Module $module
     * @param string $categorySlug
     * @param int $revision
     * @return string
     */
    public function readRevisionTemplate(Module $module, string $categorySlug, int $revision): string
    {
        return $this->readAsset($this->buildRevisionPath($module, $categorySlug, $revision));
    }

    /**
     * Read revision stylesheet.
     *
     * @param Module $module
     * @param string $categorySlug
     * @param int $revision
     * @return string
     */
    public function readRevisionStylesheet(Module $module, string $categorySlug, int $revision): string
    {
        return $this->readAsset($this->buildRevisionStylePath($module, $categorySlug, $revision));
    }

    /**
     * Read revision script.
     *
     * @param Module $module
     * @param string $categorySlug
     * @param int $revision
     * @return string
     */
    public function readRevisionScript(Module $module, string $categorySlug, int $revision): string
    {
        return $this->readAsset($this->buildRevisionScriptPath($module, $categorySlug, $revision));
    }

    /**
     * Rewrite an asset path to a new category slug.
     *
     * @param string|null $path
     * @param string $oldCategorySlug
     * @param string $newCategorySlug
     * @return string|null
     */
    public function rewritePathCategory(?string $path, string $oldCategorySlug, string $newCategorySlug): ?string
    {
        if (!is_string($path) || trim($path) === '') {
            return $path;
        }

        $old = '/' . $this->normalizeCategorySlug($oldCategorySlug) . '/';
        $new = '/' . $this->normalizeCategorySlug($newCategorySlug) . '/';

        return str_replace($old, $new, $path);
    }

    /**
     * Move all module directories from one category slug to another.
     *
     * @param iterable $modules
     * @param string $oldCategorySlug
     * @param string $newCategorySlug
     * @return void
     */
    public function moveCategoryDirectories(iterable $modules, string $oldCategorySlug, string $newCategorySlug): void
    {
        foreach ($modules as $module) {
            if ($module instanceof Module) {
                $this->moveModuleDirectory($module, $oldCategorySlug, $newCategorySlug);
            }
        }
    }

    /**
     * Move one module directory and rewrite stored asset paths.
     *
     * @param Module $module
     * @param string $oldCategorySlug
     * @param string $newCategorySlug
     * @return void
     */
    public function moveModuleDirectory(Module $module, string $oldCategorySlug, string $newCategorySlug): void
    {
        $oldDirectory = $this->buildModuleDirectory($module, $oldCategorySlug);
        $newDirectory = $this->buildModuleDirectory($module, $newCategorySlug);

        $this->moveDirectory($oldDirectory, $newDirectory);

        $module->forceFill([
            'template_path' => $this->rewritePathCategory($module->template_path, $oldCategorySlug, $newCategorySlug),
            'style_path' => $this->rewritePathCategory($module->style_path, $oldCategorySlug, $newCategorySlug),
            'script_path' => $this->rewritePathCategory($module->script_path, $oldCategorySlug, $newCategorySlug),
        ])->saveQuietly();
    }

    /**
     * List revision numbers available for a module.
     *
     * @param Module $module
     * @param string $categorySlug
     * @return array
     */
    public function availableRevisions(Module $module, string $categorySlug): array
    {
        $directory = $this->buildModuleDirectory($module, $categorySlug);
        $disk = Storage::disk('public');

        if (!$disk->exists($directory)) {
            return [];
        }

        $revisions = [];

        foreach ($disk->files($directory) as $path) {
            if (!is_string($path)) {
                continue;
            }

            if (preg_match('/\/v(\d+)\.html$/', $path, $matches) !== 1) {
                continue;
            }

            $revisions[(int) $matches[1]] = true;
        }

        $values = array_keys($revisions);
        sort($values, SORT_NUMERIC);

        return array_values($values);
    }

    /**
     * Build revision HTML path.
     *
     * @param Module $module
     * @param string $categorySlug
     * @param int $revision
     * @return string
     */
    private function buildRevisionPath(Module $module, string $categorySlug, int $revision): string
    {
        return $this->buildRevisionAssetPath($module, $categorySlug, $revision, 'html');
    }

    /**
     * Build revision CSS path.
     *
     * @param Module $module
     * @param string $categorySlug
     * @param int $revision
     * @return string
     */
    private function buildRevisionStylePath(Module $module, string $categorySlug, int $revision): string
    {
        return $this->buildRevisionAssetPath($module, $categorySlug, $revision, 'css');
    }

    /**
     * Build revision JS path.
     *
     * @param Module $module
     * @param string $categorySlug
     * @param int $revision
     * @return string
     */
    private function buildRevisionScriptPath(Module $module, string $categorySlug, int $revision): string
    {
        return $this->buildRevisionAssetPath($module, $categorySlug, $revision, 'js');
    }

    /**
     * Build revision asset path.
     *
     * @param Module $module
     * @param string $categorySlug
     * @param int $revision
     * @param string $extension
     * @return string
     */
    private function buildRevisionAssetPath(Module $module, string $categorySlug, int $revision, string $extension): string
    {
        $revision = max(1, $revision);

        return $this->buildModuleDirectory($module, $categorySlug) . '/v' . $revision . '.' . ltrim($extension, '.');
    }

    /**
     * Move one directory tree on the public disk.
     *
     * @param string $oldDirectory
     * @param string $newDirectory
     * @return void
     */
    private function moveDirectory(string $oldDirectory, string $newDirectory): void
    {
        $disk = Storage::disk('public');
        if (!$disk->exists($oldDirectory)) {
            return;
        }

        // Move files individually to keep revisions intact even when directory move is unsupported.
        foreach ($disk->allFiles($oldDirectory) as $oldFilePath) {
            $newFilePath = Str::replaceFirst($oldDirectory, $newDirectory, $oldFilePath);
            $disk->makeDirectory(dirname($newFilePath));
            $disk->move($oldFilePath, $newFilePath);
        }

        foreach (array_reverse($disk->allDirectories($oldDirectory)) as $directory) {
            if ($directory !== $oldDirectory) {
                $disk->deleteDirectory($directory);
            }
        }

        $disk->deleteDirectory($oldDirectory);
    }

    /**
     * Read one asset from the public disk.
     *
     * @param string $path
     * @return string
     */
    private function readAsset(string $path): string
    {
        if ($path === '' || !Storage::disk('public')->exists($path)) {
            return '';
        }

        return (string) Storage::disk('public')->get($path);
    }

    /**
     * Build the base module directory path.
     *
     * @param Module $module
     * @param string $categorySlug
     * @return string
     */
    private function buildModuleDirectory(Module $module, string $categorySlug): string
    {
        return sprintf(
            'users/%s/modules/%s/%s',
            $this->ownerSegment($module),
            $this->normalizeCategorySlug($categorySlug),
            $module->id
        );
    }

    /**
     * Resolve owner segment used for file paths.
     *
     * @param Module $module
     * @return string
     */
    private function ownerSegment(Module $module): string
    {
        $createdBy = $module->createdBy;
        if ($createdBy !== null && is_string($createdBy->username) && $createdBy->username !== '') {
            return $createdBy->username;
        }

        return 'system';
    }

    /**
     * Normalize category slug for path-safe usage.
     *
     * @param string $categorySlug
     * @return string
     */
    private function normalizeCategorySlug(string $categorySlug): string
    {
        $slug = trim($categorySlug);
        if ($slug === '') {
            return 'uncategorized';
        }

        return Str::slug($slug, '-');
    }
}
