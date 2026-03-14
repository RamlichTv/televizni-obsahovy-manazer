<?php

namespace App\Support\Modules;

use App\Models\Module;
use App\Support\Modules\Contracts\ModuleDataProvider;
use App\Support\Modules\Template\ModuleTemplateEngine;
use App\Support\Modules\Template\ModuleTemplateSanitizer;
use App\Support\Modules\Template\ModuleTemplateStorage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Vite as LaravelVite;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class ModuleRenderer
{
    private const MODULE_SCOPE_ATTRIBUTE = 'data-module-scope';

    /**
     * Create a new renderer instance.
     *
     * @param ModuleTemplateEngine $engine
     * @param ModuleTemplateSanitizer $sanitizer
     * @param ModuleTemplateStorage $storage
     * @return void
     */
    public function __construct(
        private readonly ModuleTemplateEngine $engine,
        private readonly ModuleTemplateSanitizer $sanitizer,
        private readonly ModuleTemplateStorage $storage,
    ) {
    }

    /**
     * Render the associated view output.
     *
     * @param Module $module
     * @param array $instanceConfig
     * @param Authenticatable|null $viewer
     * @return View
     */
    public function render(Module $module, array $instanceConfig = [], ?Authenticatable $viewer = null): View
    {
        try {
            $html = $this->renderIsolatedHtml($module, $instanceConfig, $viewer);

            return view('modules.system.rendered', [
                'module' => $module,
                'html' => $html,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to render module.', [
                'module_id' => $module->id,
                'module_name' => $module->name,
                'error' => $exception->getMessage(),
            ]);

            return view('modules.system.unavailable', [
                'module' => $module,
                'reason' => app()->environment('production') ? null : $exception->getMessage(),
            ]);
        }
    }

    /**
     * Render html.
     *
     * @param Module $module
     * @param array $instanceConfig
     * @param Authenticatable|null $viewer
     * @return string
     */
    public function renderHtml(Module $module, array $instanceConfig = [], ?Authenticatable $viewer = null): string
    {
        $template = $this->storage->readCurrentTemplate($module);
        if ($template === '') {
            throw new InvalidArgumentException('Module template file is missing.');
        }

        $stylesheet = $this->storage->readCurrentStylesheet($module);
        $script = $this->storage->readCurrentScript($module);

        $config = $this->buildConfig($module->data_schema ?? [], $module->module_data ?? [], $instanceConfig);
        $payload = $this->resolvePayloadFromSource((array) $module->data_source, $config, $viewer);
        $renderedTemplate = $this->engine->render($template, $this->buildContext($module, $config, $payload));

        return $this->composeOutput($renderedTemplate, $stylesheet, $script);
    }

    /**
     * Render runtime html inside an isolated iframe document.
     *
     * This is used for dashboard/profile/page cells so module CSS and JS cannot
     * leak into the host page and host markup cannot disturb module rendering.
     *
     * @param Module $module
     * @param array $instanceConfig
     * @param Authenticatable|null $viewer
     * @return string
     */
    public function renderIsolatedHtml(Module $module, array $instanceConfig = [], ?Authenticatable $viewer = null): string
    {
        return $this->wrapRuntimeFrame($this->renderHtml($module, $instanceConfig, $viewer));
    }

    /**
     * Render draft html.
     *
     * @param string $templateHtml
     * @param string $templateCss
     * @param string $templateJs
     * @param array $moduleData
     * @param array $source
     * @param array $schema
     * @param array $defaults
     * @param array $instanceConfig
     * @param Authenticatable|null $viewer
     * @return string
     */
    public function renderDraftHtml(
        string $templateHtml,
        string $templateCss,
        string $templateJs,
        array $moduleData,
        array $source,
        array $schema,
        array $defaults,
        array $instanceConfig = [],
        ?Authenticatable $viewer = null,
    ): string {
        $config = $this->buildConfig($schema, $defaults, $instanceConfig);
        $payload = $this->resolvePayloadFromSource($source, $config, $viewer);
        $renderedTemplate = $this->engine->render($templateHtml, $this->buildContext($moduleData, $config, $payload));

        return $this->composeOutput($renderedTemplate, $templateCss, $templateJs);
    }

    /**
     * Sanitize raw template markup before saving or previewing.
     *
     * @param string $templateHtml
     * @param bool $allowEmbedded
     * @return string
     */
    public function sanitizeTemplate(string $templateHtml, bool $allowEmbedded = false): string
    {
        return $this->sanitizer->sanitize($templateHtml, $allowEmbedded);
    }

    /**
     * Available providers.
     *
     * @return array
     */
    public function availableProviders(): array
    {
        $providerLabels = config('modules.provider_labels', []);
        $providers = config('modules.providers', []);

        if (!is_array($providerLabels) || !is_array($providers)) {
            return [];
        }

        $result = [];
        foreach ($providers as $key => $className) {
            if (!is_string($key)) {
                continue;
            }

            $result[$key] = is_string($providerLabels[$key] ?? null)
                ? $providerLabels[$key]
                : $key;
        }

        return $result;
    }

    /**
     * Resolve payload from source.
     *
     * @param array $source
     * @param array $config
     * @param Authenticatable|null $viewer
     * @return array
     */
    public function resolvePayloadFromSource(array $source, array $config, ?Authenticatable $viewer = null): array
    {
        if ($source === []) {
            return [];
        }

        $providerKey = trim((string) Arr::get($source, 'provider'));
        if ($providerKey === '') {
            return [];
        }

        $providers = config('modules.providers', []);
        if (!is_array($providers)) {
            throw new InvalidArgumentException('Provider map is not configured.');
        }

        // Providers are resolved strictly from config to avoid arbitrary class resolution.
        $providerClass = $providers[$providerKey] ?? null;
        if (!is_string($providerClass) || !class_exists($providerClass)) {
            throw new InvalidArgumentException('Invalid module provider.');
        }

        $provider = app($providerClass);
        if (!$provider instanceof ModuleDataProvider) {
            throw new InvalidArgumentException('Configured provider does not implement ModuleDataProvider.');
        }

        return $provider->resolve($source, $config, $viewer);
    }

    /**
     * Build config.
     *
     * @param array $schema
     * @param array $defaults
     * @param array $instanceConfig
     * @return array
     */
    private function buildConfig(array $schema, array $defaults, array $instanceConfig): array
    {
        $mergedConfig = array_replace_recursive($defaults, $instanceConfig);
        if ($schema === []) {
            return $mergedConfig;
        }

        $sanitized = [];

        foreach ($schema as $key => $definition) {
            if (!is_string($key) || !is_array($definition)) {
                continue;
            }

            $value = Arr::get($mergedConfig, $key, Arr::get($definition, 'default'));
            $sanitized[$key] = $this->normalizeValue($value, $definition);
        }

        return $sanitized;
    }

    /**
     * Normalize value.
     *
     * @param mixed $value
     * @param array $definition
     * @return mixed
     */
    private function normalizeValue(mixed $value, array $definition): mixed
    {
        $type = strtolower((string) ($definition['type'] ?? 'string'));
        $default = Arr::get($definition, 'default');

        if ($value === null) {
            $value = $default;
        }

        $normalized = match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $default,
            'object' => $this->normalizeObjectValue($value, $default),
            'array', 'json' => is_array($value) ? $value : (is_string($value) ? (json_decode($value, true) ?: (array) $default) : (array) $default),
            default => trim((string) $value),
        };

        $enum = Arr::get($definition, 'enum');
        if (is_array($enum) && $enum !== [] && !in_array($normalized, $enum, true)) {
            $normalized = $default;
        }

        if (is_numeric($normalized)) {
            $min = Arr::get($definition, 'min');
            $max = Arr::get($definition, 'max');

            if (is_numeric($min) && $normalized < $min) {
                $normalized = $min;
            }

            if (is_numeric($max) && $normalized > $max) {
                $normalized = $max;
            }
        }

        return $normalized;
    }

    /**
     * Normalize an object-like schema value.
     *
     * Object fields should stay associative. Lists are rejected and fall back to
     * the configured default so templates do not receive unexpected array shapes.
     *
     * @param mixed $value
     * @param mixed $default
     * @return array<string, mixed>
     */
    private function normalizeObjectValue(mixed $value, mixed $default): array
    {
        if (is_array($value) && Arr::isAssoc($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded) && Arr::isAssoc($decoded)) {
                return $decoded;
            }
        }

        if (is_array($default) && Arr::isAssoc($default)) {
            return $default;
        }

        return [];
    }

    /**
     * Build context.
     *
     * @param array|Module $module
     * @param array $config
     * @param array $payload
     * @return array
     */
    private function buildContext(array|Module $module, array $config, array $payload): array
    {
        $moduleContext = $module instanceof Module
            ? [
                'id' => $module->id,
                'name' => $module->name,
                'category' => $module->moduleCategory?->name,
                'category_slug' => $module->moduleCategory?->slug,
                'revision' => $module->revision,
            ]
            : $module;

        return [
            // Keep stable top-level aliases so templates stay readable (`items`/`meta`).
            'module' => $moduleContext,
            'config' => $config,
            'payload' => $payload,
            'items' => Arr::get($payload, 'items', []),
            'meta' => Arr::get($payload, 'meta', []),
        ];
    }

    /**
     * Compose output.
     *
     * @param string $html
     * @param string $css
     * @param string $js
     * @return string
     */
    private function composeOutput(string $html, string $css = '', string $js = ''): string
    {
        $scopeId = 'module-' . Str::lower(Str::random(12));
        $scopeSelector = '[' . self::MODULE_SCOPE_ATTRIBUTE . '="' . $scopeId . '"]';
        $output = '<div class="module-runtime-root" ' . self::MODULE_SCOPE_ATTRIBUTE . '="' . $scopeId . '"><div class="module-runtime-fill">' . $html . "</div></div>";

        $trimmedCss = trim($css);
        if ($trimmedCss !== '') {
            $scopedCss = $this->scopeStylesheet($trimmedCss, $scopeSelector);
            $output = "<style>\n" . $scopedCss . "\n</style>\n" . $output;
        }

        $trimmedJs = trim($js);
        if ($trimmedJs !== '') {
            $output .= "\n<script>\n" . $this->escapeClosingTag($this->wrapScript($trimmedJs, $scopeSelector), 'script') . "\n</script>";
        }

        return $output;
    }

    /**
     * Wrap runtime module output in an iframe-backed document.
     *
     * @param string $html
     * @return string
     */
    private function wrapRuntimeFrame(string $html): string
    {
        $documentHtml = '<!doctype html><html lang="' . e(str_replace('_', '-', app()->getLocale())) . '"><head>'
            . '<meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . $this->runtimeHeadAssetsHtml()
            . '<style>html,body{margin:0;padding:0;width:100%;height:100%;min-width:0;min-height:100%;background:transparent;overflow:hidden}body{display:block}*,*::before,*::after{box-sizing:border-box}</style>'
            . '</head><body class="font-sans antialiased">'
            . $html
            . '</body></html>';

        return '<iframe class="module-runtime-frame block h-full min-h-full w-full min-w-0 border-0 bg-transparent" srcdoc="'
            . e($documentHtml)
            . '"></iframe>';
    }

    /**
     * Resolve shared head assets for isolated runtime documents.
     *
     * The iframe document needs the compiled app stylesheet so module templates
     * can use Tailwind utilities and inherit the same base typography as the app.
     *
     * @return string
     */
    private function runtimeHeadAssetsHtml(): string
    {
        $assets = [
            '<link rel="preconnect" href="https://fonts.bunny.net">',
            '<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />',
        ];

        $stylesheetHref = $this->resolveRuntimeStylesheetHref();
        if ($stylesheetHref !== '') {
            $assets[] = '<link rel="stylesheet" href="' . e($stylesheetHref) . '">';
        }

        $runtimeScriptHref = $this->resolveRuntimeScriptHref();
        if ($runtimeScriptHref !== '') {
            $assets[] = '<script type="module" src="' . e($runtimeScriptHref) . '"></script>';
        }

        return implode('', $assets);
    }

    /**
     * Resolve the compiled app stylesheet for isolated runtime documents.
     *
     * @return string
     */
    private function resolveRuntimeStylesheetHref(): string
    {
        try {
            return (string) app(LaravelVite::class)->asset('resources/css/app.css');
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Resolve the lightweight Alpine runtime script for isolated module documents.
     *
     * @return string
     */
    private function resolveRuntimeScriptHref(): string
    {
        try {
            return (string) app(LaravelVite::class)->asset('resources/js/module-runtime.js');
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Scope free-form module CSS so it cannot style the rest of the page.
     *
     * Notes:
     * - Regular selectors are prefixed with the module instance root selector.
     * - Nested at-rules like @media/@supports are processed recursively.
     * - Import-like global statements are stripped because they bypass local scoping.
     *
     * @param string $css
     * @param string $scopeSelector
     * @return string
     */
    private function scopeStylesheet(string $css, string $scopeSelector): string
    {
        $sanitizedCss = preg_replace('/@(?:charset|import|namespace)\b[^;]*;/i', '', $css) ?? $css;

        return trim($this->scopeCssRules($sanitizedCss, $scopeSelector));
    }

    /**
     * Recursively scope selector rules within a CSS block.
     *
     * @param string $css
     * @param string $scopeSelector
     * @return string
     */
    private function scopeCssRules(string $css, string $scopeSelector): string
    {
        $result = '';
        $cursor = 0;
        $length = strlen($css);

        while ($cursor < $length) {
            $openBrace = $this->findTopLevelOpenBrace($css, $cursor);
            if ($openBrace === null) {
                $result .= substr($css, $cursor);

                break;
            }

            $prelude = substr($css, $cursor, $openBrace - $cursor);
            $closingBrace = $this->findMatchingBrace($css, $openBrace);
            if ($closingBrace === null) {
                $result .= substr($css, $cursor);

                break;
            }

            $leading = preg_match('/^\s*/', $prelude, $matches) === 1 ? $matches[0] : '';
            $trimmedPrelude = trim($prelude);
            $body = substr($css, $openBrace + 1, $closingBrace - $openBrace - 1);

            if ($trimmedPrelude === '') {
                $result .= $leading . '{' . $body . '}';
                $cursor = $closingBrace + 1;
                continue;
            }

            if (str_starts_with($trimmedPrelude, '@')) {
                $scopedBody = $this->shouldScopeNestedRule($trimmedPrelude)
                    ? $this->scopeCssRules($body, $scopeSelector)
                    : $body;

                $result .= $leading . $trimmedPrelude . '{' . $scopedBody . '}';
            } else {
                $result .= $leading . $this->scopeSelectorList($trimmedPrelude, $scopeSelector) . '{' . $body . '}';
            }

            $cursor = $closingBrace + 1;
        }

        return $result;
    }

    /**
     * Prefix each selector in a comma-separated selector list.
     *
     * @param string $selectorList
     * @param string $scopeSelector
     * @return string
     */
    private function scopeSelectorList(string $selectorList, string $scopeSelector): string
    {
        $selectors = $this->splitTopLevelSelectors($selectorList);

        $scopedSelectors = array_map(
            fn (string $selector): string => $this->scopeSingleSelector($selector, $scopeSelector),
            $selectors,
        );

        return implode(', ', array_filter($scopedSelectors, static fn (string $selector): bool => trim($selector) !== ''));
    }

    /**
     * Scope one selector against the module root.
     *
     * @param string $selector
     * @param string $scopeSelector
     * @return string
     */
    private function scopeSingleSelector(string $selector, string $scopeSelector): string
    {
        $selector = trim($selector);
        if ($selector === '') {
            return '';
        }

        $selector = str_replace(':host', $scopeSelector, $selector);

        if (str_starts_with($selector, $scopeSelector)) {
            return $selector;
        }

        $selector = preg_replace('/^(html|body|:root)\b/i', $scopeSelector, $selector, 1) ?? $selector;
        $selector = preg_replace('/^' . preg_quote($scopeSelector, '/') . '\s+(html|body|:root)\b/i', $scopeSelector, $selector, 1) ?? $selector;

        if (str_starts_with($selector, $scopeSelector)) {
            return $selector;
        }

        if (preg_match('/^:+/', $selector) === 1) {
            return $scopeSelector . $selector;
        }

        return $scopeSelector . ' ' . $selector;
    }

    /**
     * Split a selector list by top-level commas only.
     *
     * @param string $selectorList
     * @return array<int, string>
     */
    private function splitTopLevelSelectors(string $selectorList): array
    {
        $selectors = [];
        $current = '';
        $parenDepth = 0;
        $bracketDepth = 0;
        $quote = null;
        $length = strlen($selectorList);

        for ($index = 0; $index < $length; $index++) {
            $char = $selectorList[$index];

            if ($quote !== null) {
                $current .= $char;

                if ($char === '\\' && $index + 1 < $length) {
                    $current .= $selectorList[$index + 1];
                    $index++;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
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

            if ($char === ',' && $parenDepth === 0 && $bracketDepth === 0) {
                $selectors[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $selectors[] = $current;
        }

        return $selectors;
    }

    /**
     * Determine whether an at-rule contains nested selectors that need scoping.
     *
     * @param string $prelude
     * @return bool
     */
    private function shouldScopeNestedRule(string $prelude): bool
    {
        return preg_match('/^@(media|supports|container|layer)\b/i', $prelude) === 1;
    }

    /**
     * Locate the next top-level opening brace outside strings/comments.
     *
     * @param string $css
     * @param int $offset
     * @return int|null
     */
    private function findTopLevelOpenBrace(string $css, int $offset = 0): ?int
    {
        $length = strlen($css);
        $quote = null;
        $inComment = false;

        for ($index = $offset; $index < $length; $index++) {
            $char = $css[$index];
            $next = $index + 1 < $length ? $css[$index + 1] : null;

            if ($inComment) {
                if ($char === '*' && $next === '/') {
                    $inComment = false;
                    $index++;
                }

                continue;
            }

            if ($quote !== null) {
                if ($char === '\\' && $next !== null) {
                    $index++;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '/' && $next === '*') {
                $inComment = true;
                $index++;
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === '{') {
                return $index;
            }
        }

        return null;
    }

    /**
     * Find the matching closing brace for a block.
     *
     * @param string $css
     * @param int $openBrace
     * @return int|null
     */
    private function findMatchingBrace(string $css, int $openBrace): ?int
    {
        $length = strlen($css);
        $depth = 0;
        $quote = null;
        $inComment = false;

        for ($index = $openBrace; $index < $length; $index++) {
            $char = $css[$index];
            $next = $index + 1 < $length ? $css[$index + 1] : null;

            if ($inComment) {
                if ($char === '*' && $next === '/') {
                    $inComment = false;
                    $index++;
                }

                continue;
            }

            if ($quote !== null) {
                if ($char === '\\' && $next !== null) {
                    $index++;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '/' && $next === '*') {
                $inComment = true;
                $index++;
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === '{') {
                $depth++;
                continue;
            }

            if ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Wrap module JavaScript with a scoped DOM facade so it cannot target the whole page by default.
     *
     * @param string $js
     * @param string $scopeSelector
     * @return string
     */
    private function wrapScript(string $js, string $scopeSelector): string
    {
        $selectorLiteral = json_encode($scopeSelector, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<JS
(() => {
    const host = document.querySelector({$selectorLiteral});
    if (!host) {
        return;
    }

    const root = host;
    const scopedDocument = {
        querySelector: root.querySelector.bind(root),
        querySelectorAll: root.querySelectorAll.bind(root),
        getElementById: (id) => root.querySelector(`#\${CSS.escape(String(id))}`),
        getElementsByClassName: root.getElementsByClassName.bind(root),
        getElementsByTagName: root.getElementsByTagName.bind(root),
        addEventListener: document.addEventListener.bind(document),
        removeEventListener: document.removeEventListener.bind(document),
        dispatchEvent: document.dispatchEvent.bind(document),
        createElement: document.createElement.bind(document),
        createTextNode: document.createTextNode.bind(document),
        body: root,
        documentElement: root,
    };

    const scopedWindow = {
        addEventListener: window.addEventListener.bind(window),
        removeEventListener: window.removeEventListener.bind(window),
        requestAnimationFrame: window.requestAnimationFrame.bind(window),
        cancelAnimationFrame: window.cancelAnimationFrame.bind(window),
        setTimeout: window.setTimeout.bind(window),
        clearTimeout: window.clearTimeout.bind(window),
        setInterval: window.setInterval.bind(window),
        clearInterval: window.clearInterval.bind(window),
        getComputedStyle: window.getComputedStyle.bind(window),
        matchMedia: typeof window.matchMedia === 'function' ? window.matchMedia.bind(window) : undefined,
        document: scopedDocument,
        Alpine: window.Alpine,
    };

    scopedWindow.window = scopedWindow;
    scopedWindow.self = scopedWindow;

    try {
        (function (root, host, document, window) {
            'use strict';
{$js}
        })(root, host, scopedDocument, scopedWindow);
    } catch (error) {
        console.error('Module script failed.', error);
    }
})();
JS;
    }

    /**
     * Escape closing tag.
     *
     * @param string $content
     * @param string $tag
     * @return string
     */
    private function escapeClosingTag(string $content, string $tag): string
    {
        return str_ireplace('</' . $tag, '<\\/' . $tag, $content);
    }
}
