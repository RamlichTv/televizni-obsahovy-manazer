<?php

namespace App\Support\Modules\Template;

class ModuleTemplateSanitizer
{
    private const TOKEN_PREFIX = '__module_token_';

    /**
     * Sanitize.
     *
     * @param string $templateHtml
     * @param bool $allowEmbedded
     * @return string
     */
    public function sanitize(string $templateHtml, bool $allowEmbedded = false): string
    {
        $templateHtml = trim($templateHtml);
        if ($templateHtml === '') {
            return '';
        }

        // Force escaped placeholders only.
        $templateHtml = preg_replace('/\{\{\{\s*([^{}]+?)\s*\}\}\}/', '{{ $1 }}', $templateHtml) ?? $templateHtml;

        [$maskedHtml, $tokenMap] = $this->maskTemplateTokens($templateHtml);

        if (!class_exists(\HTMLPurifier::class)) {
            throw new \RuntimeException('HTMLPurifier is not installed.');
        }

        $purifier = new \HTMLPurifier($this->buildConfig($maskedHtml, $allowEmbedded));
        $sanitized = $purifier->purify($maskedHtml);
        $sanitized = $this->applyEmbeddedUrlPolicy($sanitized, $allowEmbedded);
        $sanitized = $this->ensureSafeBlankTargetLinks($sanitized);

        return str_replace(array_keys($tokenMap), array_values($tokenMap), $sanitized);
    }

    /**
     * Build purifier config tailored to the current module HTML.
     *
     * Purifier handles the heavy HTML normalization and URI sanitization.
     * We register the exact wildcard attributes (`data-*`, `aria-*`, etc.)
     * that appear in the current template so they survive purification.
     *
     * @param string $templateHtml
     * @param bool $allowEmbedded
     * @return \HTMLPurifier_Config
     */
    private function buildConfig(string $templateHtml, bool $allowEmbedded): \HTMLPurifier_Config
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Cache.DefinitionImpl', null);
        $config->set('Attr.EnableID', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'tel' => true,
        ]);
        if ($allowEmbedded) {
            // Restrict iframe sources to http(s); URL allow/deny lists are applied after purification.
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^https?://%i');
        }
        $config->set('HTML.AllowedElements', array_fill_keys($this->allowedTags($allowEmbedded), true));
        $config->set('HTML.ForbiddenElements', array_fill_keys($this->forbiddenTags($allowEmbedded), true));
        $config->set('HTML.AllowedAttributes', array_fill_keys($this->allowedAttributeLookups($templateHtml), true));
        $config->set('HTML.DefinitionID', 'module_templates');
        $config->set('HTML.DefinitionRev', 1);

        $definition = $config->maybeGetRawHTMLDefinition();
        if ($definition instanceof \HTMLPurifier_HTMLDefinition) {
            foreach ($this->exactAllowedAttributes($templateHtml) as $attribute) {
                $definition->info_global_attr[$attribute] = $this->attributeDefinition($attribute);
            }

            $this->registerCustomElements($definition);
        }

        return $config;
    }

    /**
     * Mask template tokens.
     *
     * @param string $html
     * @return array
     */
    private function maskTemplateTokens(string $html): array
    {
        $tokenMap = [];
        $counter = 0;

        $masked = preg_replace_callback(
            '/\{\{\{\s*[^{}]+?\s*\}\}\}|\{\{\s*[^{}]+?\s*\}\}/',
            function (array $matches) use (&$tokenMap, &$counter): string {
                $token = self::TOKEN_PREFIX . $counter . '__';
                $counter++;
                $tokenMap[$token] = $matches[0];

                return $token;
            },
            $html
        );

        return [$masked ?? $html, $tokenMap];
    }

    /**
     * Allowed tag names after forbidden tag removal.
     *
     * @return array<int, string>
     */
    private function allowedTags(bool $allowEmbedded = false): array
    {
        $allowed = array_map('strtolower', (array) config('modules.allowed_tags', []));
        if ($allowEmbedded) {
            $allowed[] = 'iframe';
        }
        $forbidden = $this->forbiddenTags($allowEmbedded);

        return array_values(array_unique(array_filter(
            $allowed,
            static fn (string $tag): bool => $tag !== '' && !in_array($tag, $forbidden, true),
        )));
    }

    /**
     * Forbidden tag names.
     *
     * @return array<int, string>
     */
    private function forbiddenTags(bool $allowEmbedded = false): array
    {
        $forbidden = array_values(array_unique(array_filter(array_map(
            static fn ($tag): string => is_string($tag) ? strtolower(trim($tag)) : '',
            (array) config('modules.forbidden_tags', []),
        ))));

        if (!$allowEmbedded) {
            return $forbidden;
        }

        // Category-level embedding opt-in removes iframe from the forbidden list.
        return array_values(array_filter(
            $forbidden,
            static fn (string $tag): bool => $tag !== 'iframe',
        ));
    }

    /**
     * Build HTML.AllowedAttributes lookup values.
     *
     * @param string $templateHtml
     * @return array<int, string>
     */
    private function allowedAttributeLookups(string $templateHtml): array
    {
        return array_map(
            static fn (string $attribute): string => '*.' . $attribute,
            $this->exactAllowedAttributes($templateHtml),
        );
    }

    /**
     * Expand wildcard attribute patterns into the exact attribute names present in the template.
     *
     * @param string $templateHtml
     * @return array<int, string>
     */
    private function exactAllowedAttributes(string $templateHtml): array
    {
        $patterns = array_values(array_filter(array_map(
            static fn ($pattern): string => is_string($pattern) ? strtolower(trim($pattern)) : '',
            (array) config('modules.allowed_attributes', []),
        )));

        $exact = [];
        $wildcards = [];

        foreach ($patterns as $pattern) {
            if ($pattern === '') {
                continue;
            }

            if (str_contains($pattern, '*')) {
                $wildcards[] = $pattern;
                continue;
            }

            $exact[$pattern] = true;
        }

        foreach ($this->attributeNamesFromHtml($templateHtml) as $attribute) {
            foreach ($wildcards as $pattern) {
                if ($this->matchesAttributePattern($attribute, $pattern)) {
                    $exact[$attribute] = true;
                    break;
                }
            }
        }

        return array_keys($exact);
    }

    /**
     * Collect attribute names from raw template HTML.
     *
     * @param string $templateHtml
     * @return array<int, string>
     */
    private function attributeNamesFromHtml(string $templateHtml): array
    {
        preg_match_all('/\s([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=/u', $templateHtml, $matches);

        return array_values(array_unique(array_filter(array_map(
            static fn ($attribute): string => is_string($attribute) ? strtolower(trim($attribute)) : '',
            $matches[1] ?? [],
        ))));
    }

    /**
     * Determine whether an attribute name matches a configured wildcard pattern.
     *
     * @param string $attribute
     * @param string $pattern
     * @return bool
     */
    private function matchesAttributePattern(string $attribute, string $pattern): bool
    {
        if ($pattern === $attribute) {
            return true;
        }

        if (!str_contains($pattern, '*')) {
            return false;
        }

        $prefix = rtrim($pattern, '*');

        return $prefix !== '' && str_starts_with($attribute, $prefix);
    }

    /**
     * Resolve an attribute definition understood by HTMLPurifier.
     *
     * @param string $attribute
     * @return \HTMLPurifier_AttrDef|string
     */
    private function attributeDefinition(string $attribute): \HTMLPurifier_AttrDef|string
    {
        return match ($attribute) {
            'class' => new \HTMLPurifier_AttrDef_HTML_Class(),
            'controls' => new \HTMLPurifier_AttrDef_HTML_Bool(),
            'href', 'src' => 'URI',
            'id' => new \HTMLPurifier_AttrDef_HTML_ID(),
            default => new \HTMLPurifier_AttrDef_Text(),
        };
    }

    /**
     * Register the HTML5/SVG elements that the module editor allows but Purifier does not define by default.
     *
     * @param \HTMLPurifier_HTMLDefinition $definition
     * @return void
     */
    private function registerCustomElements(\HTMLPurifier_HTMLDefinition $definition): void
    {
        $definition->addElement('article', 'Block', 'Flow', 'Common');
        $definition->addElement('aside', 'Block', 'Flow', 'Common');
        $definition->addElement('button', 'Inline', 'Flow', 'Common');
        $definition->addElement('figcaption', 'Block', 'Flow', 'Common');
        $definition->addElement('figure', 'Block', 'Flow', 'Common');
        $definition->addElement('footer', 'Block', 'Flow', 'Common');
        $definition->addElement('header', 'Block', 'Flow', 'Common');
        $definition->addElement('label', 'Inline', 'Inline', 'Common');
        $definition->addElement('main', 'Block', 'Flow', 'Common');
        $definition->addElement('nav', 'Block', 'Flow', 'Common');
        $definition->addElement('section', 'Block', 'Flow', 'Common');
        $definition->addElement('video', 'Inline', 'Optional: #PCDATA', 'Common');
        $definition->addElement('svg', 'Inline', 'Optional: circle | line | path | rectangle | #PCDATA', 'Common');
        $definition->addElement('iframe', 'Inline', 'Empty', 'Common');
        $definition->addElement('circle', 'Inline', 'Empty', 'Common');
        $definition->addElement('line', 'Inline', 'Empty', 'Common');
        $definition->addElement('path', 'Inline', 'Empty', 'Common');
        $definition->addElement('rectangle', 'Inline', 'Empty', 'Common');
    }

    /**
     * Apply iframe source allow/deny policy from config.
     *
     * @param string $html
     * @param bool $allowEmbedded
     * @return string
     */
    private function applyEmbeddedUrlPolicy(string $html, bool $allowEmbedded): string
    {
        if (!$allowEmbedded) {
            return preg_replace('/<iframe\b[^>]*>(?:<\/iframe>)?/i', '', $html) ?? $html;
        }

        return preg_replace_callback('/<iframe\b[^>]*>(?:<\/iframe>)?/i', function (array $matches): string {
            $iframeTag = $matches[0];
            if (preg_match('/\bsrc\s*=\s*([\'"])(.*?)\1/i', $iframeTag, $srcMatch) !== 1) {
                return '';
            }

            $src = trim((string) $srcMatch[2]);
            if (!$this->isAllowedEmbeddedUrl($src)) {
                return '';
            }

            return $iframeTag;
        }, $html) ?? $html;
    }

    /**
     * Validate one iframe URL against config whitelist and blacklist.
     *
     * @param string $url
     * @return bool
     */
    private function isAllowedEmbeddedUrl(string $url): bool
    {
        if (!$this->isHttpUrl($url)) {
            return false;
        }

        $whitelist = $this->normalizeUrlRules((array) config('modules.embedded_urls.whitelist', []));
        $blacklist = $this->normalizeUrlRules((array) config('modules.embedded_urls.blacklist', []));

        foreach ($blacklist as $rule) {
            if ($this->matchesUrlRule($url, $rule)) {
                return false;
            }
        }

        if ($whitelist === []) {
            return true;
        }

        foreach ($whitelist as $rule) {
            if ($this->matchesUrlRule($url, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize URL policy rules from config.
     *
     * @param array<int, mixed> $rules
     * @return array<int, string>
     */
    private function normalizeUrlRules(array $rules): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $rule): string => is_string($rule) ? trim($rule) : '',
            $rules,
        ))));
    }

    /**
     * Match URL against one policy rule.
     *
     * Supported forms:
     * - "regex:/.../"
     * - full URL prefix ("https://example.com/embed/")
     * - host or wildcard host ("example.com", "*.example.com")
     *
     * @param string $url
     * @param string $rule
     * @return bool
     */
    private function matchesUrlRule(string $url, string $rule): bool
    {
        $urlLower = strtolower($url);
        $ruleLower = strtolower($rule);
        if (str_starts_with($ruleLower, 'regex:')) {
            $pattern = substr($rule, 6);
            if ($pattern === '') {
                return false;
            }

            return @preg_match($pattern, $url) === 1;
        }

        if (str_contains($ruleLower, '://')) {
            return str_starts_with($urlLower, $ruleLower);
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        if (str_starts_with($ruleLower, '*.')) {
            $suffix = substr($ruleLower, 2);
            if ($suffix === '') {
                return false;
            }

            return str_ends_with($host, '.' . $suffix);
        }

        return $host === $ruleLower || str_ends_with($host, '.' . $ruleLower);
    }

    /**
     * Check for a valid http(s) URL.
     *
     * @param string $url
     * @return bool
     */
    private function isHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * Ensure links opened in a new tab cannot keep a dangerous opener reference.
     *
     * @param string $html
     * @return string
     */
    private function ensureSafeBlankTargetLinks(string $html): string
    {
        return preg_replace_callback('/<a\b[^>]*>/i', function (array $matches): string {
            $tag = $matches[0];

            if (preg_match('/\btarget\s*=\s*([\'"]?)([^\'"\s>]+)\1/i', $tag, $targetMatch) !== 1) {
                return $tag;
            }

            if (strtolower($targetMatch[2]) !== '_blank') {
                return $tag;
            }

            $relValues = ['noopener', 'noreferrer'];
            if (preg_match('/\brel\s*=\s*([\'"])(.*?)\1/i', $tag, $relMatch) === 1) {
                $existing = preg_split('/\s+/', trim($relMatch[2])) ?: [];
                foreach ($existing as $value) {
                    $normalized = strtolower(trim((string) $value));
                    if ($normalized !== '') {
                        $relValues[] = $normalized;
                    }
                }

                $replacement = 'rel="' . implode(' ', array_values(array_unique($relValues))) . '"';

                return preg_replace('/\brel\s*=\s*([\'"]).*?\1/i', $replacement, $tag, 1) ?? $tag;
            }

            return rtrim($tag, '>') . ' rel="noopener noreferrer">';
        }, $html) ?? $html;
    }
}
