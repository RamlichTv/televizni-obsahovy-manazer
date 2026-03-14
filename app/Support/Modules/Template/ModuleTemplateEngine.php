<?php

namespace App\Support\Modules\Template;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Mustache\Engine as MustacheEngine;

class ModuleTemplateEngine
{
    /**
     * Shared Mustache engine instance.
     *
     * @var MustacheEngine
     */
    private MustacheEngine $engine;

    /**
     * Create a new template engine wrapper.
     *
     * @param MustacheEngine|null $engine
     * @return void
     */
    public function __construct(?MustacheEngine $engine = null)
    {
        $this->engine = $engine ?? new MustacheEngine([
            // Keep variable escaping aligned with the previous custom renderer.
            'escape' => fn (mixed $value): string => htmlspecialchars(
                $this->stringifyValue($value),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ),
        ]);
    }

    /**
     * Render a sanitized template through Mustache.
     *
     * Compatibility notes:
     * - Native Mustache sections are supported as-is.
     * - Legacy `{{#each items}}...{{/each}}` aliases are rewritten to
     *   standard Mustache sections before rendering so existing modules keep working.
     *
     * @param string $templateHtml
     * @param array $context
     * @return string
     */
    public function render(string $templateHtml, array $context = []): string
    {
        $template = $this->normalizeEachAliases($templateHtml);

        return $this->engine->render($template, $this->normalizeValue($context));
    }

    /**
     * Rewrite `#each` aliases into native Mustache section syntax.
     *
     * @param string $template
     * @return string
     */
    private function normalizeEachAliases(string $template): string
    {
        $pattern = '/\{\{#each\s+([a-zA-Z0-9_.]+)\s*\}\}(.*?)\{\{\/each\}\}/s';

        while (preg_match($pattern, $template) === 1) {
            $template = preg_replace_callback($pattern, function (array $matches): string {
                $path = trim((string) $matches[1]);
                $innerTemplate = $this->normalizeEachAliases($matches[2]);

                return '{{#' . $path . '}}' . $innerTemplate . '{{/' . $path . '}}';
            }, $template) ?? $template;
        }

        return $template;
    }

    /**
     * Normalize arrays, collections, and objects into Mustache-friendly data.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeValue($item);
        }

        return $value;
    }

    /**
     * Convert complex values to strings before HTML escaping.
     *
     * @param mixed $value
     * @return string
     */
    private function stringifyValue(mixed $value): string
    {
        $normalized = $this->normalizeValue($value);

        if ($normalized === null) {
            return '';
        }

        if (is_bool($normalized)) {
            return $normalized ? '1' : '0';
        }

        if (!is_array($normalized)) {
            return (string) $normalized;
        }

        return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
