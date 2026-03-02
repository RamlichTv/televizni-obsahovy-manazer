<?php

namespace App\Support\Modules;

use App\Models\Module;
use App\Support\Modules\Contracts\ModuleDataProvider;
use App\Support\Modules\Template\ModuleTemplateEngine;
use App\Support\Modules\Template\ModuleTemplateSanitizer;
use App\Support\Modules\Template\ModuleTemplateStorage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class ModuleRenderer
{
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
            $html = $this->renderHtml($module, $instanceConfig, $viewer);

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

        $config = $this->buildConfig($module->config_schema ?? [], $module->default_config ?? [], $instanceConfig);
        $payload = $this->resolvePayloadFromSource((array) $module->data_source, $config, $viewer);
        $renderedTemplate = $this->engine->render($template, $this->buildContext($module, $config, $payload));

        return $this->composeOutput($renderedTemplate, $stylesheet, $script);
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

        $sanitized = $this->sanitizer->sanitize($templateHtml);
        $renderedTemplate = $this->engine->render($sanitized, $this->buildContext($moduleData, $config, $payload));

        return $this->composeOutput($renderedTemplate, $templateCss, $templateJs);
    }

    /**
     * Sanitize raw template markup before saving or previewing.
     *
     * @param string $templateHtml
     * @return string
     */
    public function sanitizeTemplate(string $templateHtml): string
    {
        return $this->sanitizer->sanitize($templateHtml);
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
        $output = '';

        $trimmedCss = trim($css);
        if ($trimmedCss !== '') {
            $output .= "<style>\n" . $this->escapeClosingTag($trimmedCss, 'style') . "\n</style>\n";
        }

        $output .= $html;

        $trimmedJs = trim($js);
        if ($trimmedJs !== '') {
            $output .= "\n<script>\n" . $this->escapeClosingTag($trimmedJs, 'script') . "\n</script>";
        }

        return $output;
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
