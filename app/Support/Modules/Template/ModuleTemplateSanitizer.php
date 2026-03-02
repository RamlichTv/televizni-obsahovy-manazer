<?php

namespace App\Support\Modules\Template;

use DOMDocument;
use DOMElement;
use DOMNode;

class ModuleTemplateSanitizer
{
    private const TOKEN_PREFIX = '__module_token_';

    /**
     * Sanitize.
     *
     * @param string $templateHtml
     * @return string
     */
    public function sanitize(string $templateHtml): string
    {
        $templateHtml = trim($templateHtml);
        if ($templateHtml === '') {
            return '';
        }

        // Force escaped placeholders only.
        $templateHtml = preg_replace('/\{\{\{\s*([^{}]+?)\s*\}\}\}/', '{{ $1 }}', $templateHtml) ?? $templateHtml;

        [$maskedHtml, $tokenMap] = $this->maskTemplateTokens($templateHtml);

        $document = new DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<div id="__module_root">' . $maskedHtml . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $root = $document->getElementById('__module_root');
        if (!$root instanceof DOMElement) {
            return '';
        }

        $this->sanitizeNode($root);

        $sanitized = '';
        foreach ($root->childNodes as $child) {
            $sanitized .= $document->saveHTML($child);
        }

        return str_replace(array_keys($tokenMap), array_values($tokenMap), $sanitized);
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
     * Sanitize node.
     *
     * @param DOMNode $node
     * @return void
     */
    private function sanitizeNode(DOMNode $node): void
    {
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if ($this->isForbiddenTag($tag)) {
                $child->parentNode?->removeChild($child);
                continue;
            }

            if (!$this->isAllowedTag($tag)) {
                $this->unwrapElement($child);
                continue;
            }

            $this->sanitizeAttributes($child);
            $this->sanitizeNode($child);
        }
    }

    /**
     * Sanitize attributes.
     *
     * @param DOMElement $element
     * @return void
     */
    private function sanitizeAttributes(DOMElement $element): void
    {
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $attributes[] = $attribute;
        }

        foreach ($attributes as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim((string) $attribute->value);

            if (str_starts_with($name, 'on') || $name === 'style') {
                $element->removeAttribute($attribute->name);
                continue;
            }

            if (!$this->isAllowedAttribute($name)) {
                $element->removeAttribute($attribute->name);
                continue;
            }

            if (in_array($name, ['href', 'src'], true)
                && !$this->containsTemplateToken($value)
                && !$this->isSafeUrl($value)) {
                $element->removeAttribute($attribute->name);
                continue;
            }
        }

        if (strtolower($element->tagName) === 'a') {
            $target = strtolower((string) $element->getAttribute('target'));
            if ($target === '_blank') {
                $existing = strtolower((string) $element->getAttribute('rel'));
                $rels = collect(explode(' ', $existing))
                    ->map(fn (string $part) => trim($part))
                    ->filter()
                    ->push('noopener')
                    ->push('noreferrer')
                    ->unique()
                    ->values()
                    ->implode(' ');

                $element->setAttribute('rel', $rels);
            }
        }
    }

    /**
     * Unwrap element.
     *
     * @param DOMElement $element
     * @return void
     */
    private function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * Determine whether forbidden tag.
     *
     * @param string $tag
     * @return bool
     */
    private function isForbiddenTag(string $tag): bool
    {
        return in_array($tag, (array) config('modules.forbidden_tags', []), true);
    }

    /**
     * Determine whether allowed tag.
     *
     * @param string $tag
     * @return bool
     */
    private function isAllowedTag(string $tag): bool
    {
        return in_array($tag, (array) config('modules.allowed_tags', []), true);
    }

    /**
     * Determine whether allowed attribute.
     *
     * @param string $attribute
     * @return bool
     */
    private function isAllowedAttribute(string $attribute): bool
    {
        foreach ((array) config('modules.allowed_attributes', []) as $allowedPattern) {
            if (!is_string($allowedPattern)) {
                continue;
            }

            $allowedPattern = strtolower($allowedPattern);
            if (!str_contains($allowedPattern, '*') && $allowedPattern === $attribute) {
                return true;
            }

            if (str_ends_with($allowedPattern, '*')) {
                $prefix = rtrim($allowedPattern, '*');
                if ($prefix !== '' && str_starts_with($attribute, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine whether safe url.
     *
     * @param string $url
     * @return bool
     */
    private function isSafeUrl(string $url): bool
    {
        if ($url === '' || $url === '#' || str_starts_with($url, '/')) {
            return true;
        }

        return preg_match('/^(https?:|mailto:|tel:)/i', $url) === 1;
    }

    /**
     * Contains template token.
     *
     * @param string $value
     * @return bool
     */
    private function containsTemplateToken(string $value): bool
    {
        return str_contains($value, self::TOKEN_PREFIX);
    }
}

