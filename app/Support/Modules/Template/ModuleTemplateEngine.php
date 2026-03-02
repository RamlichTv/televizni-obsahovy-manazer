<?php

namespace App\Support\Modules\Template;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ModuleTemplateEngine
{
    /**
     * Render a sanitized template with a constrained Mustache-like feature set.
     *
     * @param string $templateHtml
     * @param array $context
     * @return string
     */
    public function render(string $templateHtml, array $context = []): string
    {
        $html = $templateHtml;
        // Expand loop aliases first, then named sections, and finally scalar placeholders.
        $html = $this->renderEachAliases($html, $context);
        $html = $this->renderNamedSections($html, $context);

        return $this->renderVariables($html, $context);
    }

    /**
     * Render each aliases.
     *
     * @param string $template
     * @param array $context
     * @return string
     */
    private function renderEachAliases(string $template, array $context): string
    {
        $pattern = '/\{\{#each\s+([a-zA-Z0-9_.]+)\s*\}\}(.*?)\{\{\/each\}\}/s';

        while (preg_match($pattern, $template) === 1) {
            $template = preg_replace_callback($pattern, function (array $matches) use ($context): string {
                $path = $matches[1];
                $innerTemplate = $matches[2];
                $value = $this->resolvePath($context, $path);

                $items = $this->iterableItems($value);
                if ($items === null) {
                    return '';
                }

                $rendered = '';
                foreach ($items as $item) {
                    $rendered .= $this->render($innerTemplate, $this->mergeItemContext($context, $item));
                }

                return $rendered;
            }, $template) ?? $template;
        }

        return $template;
    }

    /**
     * Render named sections.
     *
     * @param string $template
     * @param array $context
     * @return string
     */
    private function renderNamedSections(string $template, array $context): string
    {
        $pattern = '/\{\{#([a-zA-Z0-9_.]+)\}\}(.*?)\{\{\/\1\}\}/s';

        while (preg_match($pattern, $template) === 1) {
            $template = preg_replace_callback($pattern, function (array $matches) use ($context): string {
                $path = $matches[1];
                $innerTemplate = $matches[2];
                $value = $this->resolvePath($context, $path);

                $items = $this->iterableItems($value);
                if ($items !== null) {
                    $rendered = '';
                    foreach ($items as $item) {
                        $rendered .= $this->render($innerTemplate, $this->mergeItemContext($context, $item));
                    }

                    return $rendered;
                }

                if ($this->isTruthy($value)) {
                    if (is_array($value) || $value instanceof Arrayable || $value instanceof \stdClass) {
                        return $this->render($innerTemplate, $this->mergeItemContext($context, $value));
                    }

                    return $this->render($innerTemplate, $context);
                }

                return '';
            }, $template) ?? $template;
        }

        return $template;
    }

    /**
     * Render variables.
     *
     * @param string $template
     * @param array $context
     * @return string
     */
    private function renderVariables(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', function (array $matches) use ($context): string {
            $value = $this->resolvePath($context, $matches[1]);

            if ($value === null) {
                return '';
            }

            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            if (is_array($value) || $value instanceof Arrayable || is_object($value)) {
                $value = json_encode($this->normalizeValue($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // Escaped output is the default behavior for all variable substitutions.
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }, $template) ?? $template;
    }

    /**
     * Resolve path.
     *
     * @param array $context
     * @param string $path
     * @return mixed
     */
    private function resolvePath(array $context, string $path): mixed
    {
        return data_get($context, $path);
    }

    /**
     * Merge item context.
     *
     * @param array $context
     * @param mixed $item
     * @return array
     */
    private function mergeItemContext(array $context, mixed $item): array
    {
        $normalized = $this->normalizeValue($item);

        $next = $context;
        $next['this'] = $normalized;

        if (is_array($normalized)) {
            foreach ($normalized as $key => $value) {
                if (!is_string($key) && !is_int($key)) {
                    continue;
                }

                $next[(string) $key] = $value;
            }

            return $next;
        }

        $next['value'] = $normalized;

        return $next;
    }

    /**
     * Iterable items.
     *
     * @param mixed $value
     * @return array|null
     */
    private function iterableItems(mixed $value): ?array
    {
        if ($value instanceof Collection) {
            return $value->values()->all();
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (!is_array($value)) {
            return null;
        }

        if (array_is_list($value)) {
            return array_values($value);
        }

        return null;
    }

    /**
     * Determine whether truthy.
     *
     * @param mixed $value
     * @return bool
     */
    private function isTruthy(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return (bool) $value;
    }

    /**
     * Normalize value.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof Collection) {
            return $value->all();
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (is_object($value)) {
            return get_object_vars($value);
        }

        return $value;
    }
}
