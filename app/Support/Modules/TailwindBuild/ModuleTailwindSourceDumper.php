<?php

namespace App\Support\Modules\TailwindBuild;

use App\Models\Module;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ModuleTailwindSourceDumper
{
    private const TARGET_RELATIVE_PATH = 'tailwind/module-templates.html';

    /**
     * Dump module template classes into a Tailwind scan source file.
     *
     * @return void
     */
    public function dump(): void
    {
        // Keep a stable, deterministic scan file so Tailwind picks up classes from module templates.
        $targetPath = resource_path(self::TARGET_RELATIVE_PATH);
        File::ensureDirectoryExists(dirname($targetPath));

        if (!Schema::hasTable('modules')) {
            File::put($targetPath, $this->renderHeader());
            return;
        }

        $disk = Storage::disk('public');
        $chunks = [$this->renderHeader()];

        $modules = Module::query()
            ->orderBy('name')
            ->get(['id', 'name', 'template_path']);

        foreach ($modules as $module) {
            $path = trim((string) $module->template_path);
            if ($path === '' || !$disk->exists($path)) {
                continue;
            }

            $template = trim((string) $disk->get($path));
            if ($template === '') {
                continue;
            }

            // Extract unique class tokens and emit small containers that Tailwind can scan reliably.
            $classTokens = $this->extractClassTokens($template);
            if ($classTokens === []) {
                continue;
            }

            $chunks[] = sprintf('<!-- module:%s -->', $module->id);
            foreach ($classTokens as $classToken) {
                $chunks[] = $this->renderTokenContainer($classToken);
            }
        }

        $next = implode(PHP_EOL . PHP_EOL, $chunks) . PHP_EOL;
        $current = File::exists($targetPath) ? (string) File::get($targetPath) : null;

        if ($current !== $next) {
            File::put($targetPath, $next);
        }
    }

    /**
     * Render the static header included in the generated source.
     *
     * @return string
     */
    private function renderHeader(): string
    {
        return '<!-- AUTO-GENERATED. Source for Tailwind scanning of file-backed module templates. -->';
    }

    /**
     * Extract unique class tokens from HTML markup.
     *
     * @param string $html
     * @return array
     */
    private function extractClassTokens(string $html): array
    {
        $classSet = [];

        foreach (['/class\s*=\s*"([^"]+)"/i', "/class\\s*=\\s*'([^']+)'/i"] as $pattern) {
            $matchCount = preg_match_all($pattern, $html, $matches);
            if ($matchCount === false || $matchCount < 1 || !isset($matches[1])) {
                continue;
            }

            foreach ($matches[1] as $classAttr) {
                if (!is_string($classAttr) || trim($classAttr) === '') {
                    continue;
                }

                $decodedClassAttr = html_entity_decode($classAttr, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $tokens = $this->tokenizeClassAttribute($decodedClassAttr);

                foreach ($tokens as $token) {
                    $token = trim((string) $token);
                    if ($token === '') {
                        continue;
                    }

                    $classSet[$token] = true;
                }
            }
        }

        $classes = array_keys($classSet);
        sort($classes, SORT_STRING);

        return array_values(array_filter($classes, static fn (string $token): bool => $token !== ''));
    }

    /**
     * Split a class attribute into tokens while preserving arbitrary values.
     *
     * @param string $classAttr
     * @return array
     */
    private function tokenizeClassAttribute(string $classAttr): array
    {
        $tokens = [];
        $current = '';
        $bracketDepth = 0;
        $parenDepth = 0;
        $activeQuote = null;

        $length = strlen($classAttr);
        for ($i = 0; $i < $length; $i++) {
            $char = $classAttr[$i];

            if ($char === '\\' && $i + 1 < $length) {
                $current .= $char . $classAttr[$i + 1];
                $i++;
                continue;
            }

            if ($activeQuote !== null) {
                if ($char === $activeQuote) {
                    $activeQuote = null;
                }

                $current .= $char;
                continue;
            }

            if ($char === "'" || $char === '"') {
                $activeQuote = $char;
                $current .= $char;
                continue;
            }

            if ($char === '[') {
                $bracketDepth++;
                $current .= $char;
                continue;
            }

            if ($char === ']') {
                $bracketDepth = max(0, $bracketDepth - 1);
                $current .= $char;
                continue;
            }

            if ($char === '(') {
                $parenDepth++;
                $current .= $char;
                continue;
            }

            if ($char === ')') {
                $parenDepth = max(0, $parenDepth - 1);
                $current .= $char;
                continue;
            }

            if (ctype_space($char) && $bracketDepth === 0 && $parenDepth === 0) {
                $token = trim($current);
                if ($token !== '') {
                    $tokens[] = $token;
                }

                $current = '';
                continue;
            }

            $current .= $char;
        }

        $token = trim($current);
        if ($token !== '') {
            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * Render a minimal container for one class token.
     *
     * @param string $token
     * @return string
     */
    private function renderTokenContainer(string $token): string
    {
        if (!str_contains($token, '"')) {
            return '<div class="' . $token . '"></div>';
        }

        if (!str_contains($token, "'")) {
            return "<div class='" . $token . "'></div>";
        }

        return '<div class="' . str_replace('"', '&quot;', $token) . '"></div>';
    }
}

