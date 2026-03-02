<?php

namespace App\Support\Avatar;

use App\Models\User;

class DefaultAvatar
{
    /**
     * Build a default avatar data URI for a user.
     *
     * @param User $user
     * @return string
     */
    public static function forUser(User $user): string
    {
        // Keep defaults deterministic per user identity so avatar does not
        // "change randomly" between requests.
        $seed = self::seed((string) ($user->username ?: $user->email ?: $user->name));
        $variant = self::selectVariant($seed);

        // Pick a softer background tint and then render darker inner shapes
        // from the same hue family for contrast/readability.
        $background = self::selectBackground($seed);

        // Alpha variant shows a one-letter initial.
        $text = self::initialFrom((string) ($user->name ?: $user->username ?: $user->email));

        // Emo variant replaces initial with seeded ASCII glyph.
        if ($variant === 'emo') {
            $text = self::selectEmoji($seed);
        }

        return self::svgDataUri($variant, $background, $text);
    }

    /**
     * Build a default avatar data URI from explicit parameters.
     *
     * @param string $variant
     * @param string $background
     * @param string $text
     * @return string
     */
    public static function svgDataUri(string $variant, string $background, string $text): string
    {
        // Normalize color input to avoid malformed SVG values.
        $backgroundColor = self::sanitizeHexColor($background);
        // Inner UI/object is intentionally darker than the outer circle.
        $objectColor = self::darken($backgroundColor, 0.38);

        // Template placeholders are replaced at runtime so one SVG file can
        // be reused with many color/text combinations.
        $svg = str_replace(
            ['__BACKGROUND__', '__OBJECT__', '__TEXT__'],
            [$backgroundColor, $objectColor, self::escapeXml(self::normalize($text))],
            self::template($variant)
        );

        // Raw-encode XML before embedding into a data URI.
        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }

    /**
     * Compute a stable integer seed from an input value.
     *
     * @param string $value
     * @return int
     */
    private static function seed(string $value): int
    {
        // Use lowercase + trim to prevent case/spacing creating a different avatar.
        return (int) sprintf('%u', crc32(mb_strtolower(trim($value), 'UTF-8')));
    }

    /**
     * Normalize rendered avatar text.
     *
     * @param string $value
     * @return string
     */
    private static function normalize(string $value): string
    {
        $value = trim($value);

        return $value === '' ? 'N' : $value;
    }

    /**
     * Select avatar template variant.
     *
     * @param int $seed
     * @return string
     */
    private static function selectVariant(int $seed): string
    {
        return (self::seedByte($seed, 0) % 2) === 0 ? 'alpha' : 'emo';
    }

    /**
     * Select base background color.
     *
     * @param int $seed
     * @return string
     */
    private static function selectBackground(int $seed): string
    {
        // Palette intentionally avoids very light colors so text remains legible.
        $palette = [
            '#077CB6', '#0EA5E9', '#14B8A6', '#22C55E', '#F59E0B', '#EF4444', '#8B5CF6', '#64748B',
        ];

        // Blend two seeded colors to increase visual variety without requiring
        // a huge static palette.
        $base = $palette[self::seedByte($seed, 1) % count($palette)];
        $mixTarget = $palette[self::seedByte($seed, 2) % count($palette)];

        return self::mix($base, $mixTarget, 0.25);
    }

    /**
     * Select emoji fallback glyph.
     *
     * @param int $seed
     * @return string
     */
    private static function selectEmoji(int $seed): string
    {
        $glyphs = [
            ':)', 
            ':]', 
            ':}',
            ':0', 
            ':O', 
            ':o',
            ':/',
            ':\\',
            ';*',
            ':*',
            ';^',
            ';*',
            ':#',
            'XD',
            ':D',
            ';)',
            '8)',
            ':P',
            ':3',
            ':|', 
            'B)'
        ];

        return $glyphs[self::seedByte($seed, 3) % count($glyphs)];
    }

    /**
     * Extract one visible initial from a label.
     *
     * @param string $value
     * @return string
     */
    private static function initialFrom(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return 'N';
        }

        $first = mb_substr($normalized, 0, 1, 'UTF-8');

        return mb_strtoupper($first, 'UTF-8');
    }

    /**
     * Pick one pseudo-random byte from a seed.
     *
     * @param int $seed
     * @param int $offset
     * @return int
     */
    private static function seedByte(int $seed, int $offset): int
    {
        // Extract an 8-bit segment from the CRC value (offset 0..3).
        return ($seed >> ($offset * 8)) & 0xFF;
    }

    /**
     * Derive a matching light and dark color pair.
     *
     * @param string $base
     * @return array
     */
    private static function colorPair(string $base): array
    {
        $lightBackground = self::lighten($base, 0.70);
        $darkObject = self::darken($base, 0.38);

        return [$lightBackground, $darkObject];
    }

    /**
     * Normalize an input color and fall back to a safe default when invalid.
     *
     * @param string $hex
     * @return string
     */
    private static function sanitizeHexColor(string $hex): string
    {
        $value = strtoupper(ltrim(trim($hex), '#'));

        if (!preg_match('/^[0-9A-F]{6}$/', $value)) {
            return '#334155';
        }

        return '#' . $value;
    }

    /**
     * Lighten a hex color by ratio.
     *
     * @param string $hex
     * @param float $amount
     * @return string
     */
    private static function lighten(string $hex, float $amount): string
    {
        return self::mix($hex, '#FFFFFF', $amount);
    }

    /**
     * Darken a hex color by ratio.
     *
     * @param string $hex
     * @param float $amount
     * @return string
     */
    private static function darken(string $hex, float $amount): string
    {
        return self::mix($hex, '#000000', $amount);
    }

    /**
     * Mix two hex colors.
     *
     * @param string $from
     * @param string $to
     * @param float $ratio
     * @return string
     */
    private static function mix(string $from, string $to, float $ratio): string
    {
        $ratio = max(0.0, min(1.0, $ratio));

        $a = self::hexToRgb($from);
        $b = self::hexToRgb($to);

        return self::rgbToHex([
            (int) round($a[0] + (($b[0] - $a[0]) * $ratio)),
            (int) round($a[1] + (($b[1] - $a[1]) * $ratio)),
            (int) round($a[2] + (($b[2] - $a[2]) * $ratio)),
        ]);
    }

    /**
     * Convert hex color to RGB tuple.
     *
     * @param string $hex
     * @return array
     */
    private static function hexToRgb(string $hex): array
    {
        $sanitized = ltrim($hex, '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $sanitized)) {
            return [51, 65, 85];
        }

        return [
            hexdec(substr($sanitized, 0, 2)),
            hexdec(substr($sanitized, 2, 2)),
            hexdec(substr($sanitized, 4, 2)),
        ];
    }

    /**
     * Convert RGB tuple to hex color.
     *
     * @param array $rgb
     * @return string
     */
    private static function rgbToHex(array $rgb): string
    {
        $r = max(0, min(255, (int) $rgb[0]));
        $g = max(0, min(255, (int) $rgb[1]));
        $b = max(0, min(255, (int) $rgb[2]));

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /**
     * Escape XML text nodes safely.
     *
     * @param string $value
     * @return string
     */
    private static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Load SVG template by variant.
     *
     * @param string $variant
     * @return string
     */
    private static function template(string $variant): string
    {
        // Keep loaded SVG templates in-memory for the request lifecycle.
        static $templates = [];

        if (!isset($templates[$variant])) {
            $path = resource_path('svg/default-' . $variant . '.svg');
            $contents = is_file($path) ? file_get_contents($path) : false;
            $templates[$variant] = is_string($contents) && $contents !== ''
                ? $contents
                : self::fallbackTemplate();
        }

        return $templates[$variant];
    }

    /**
     * Return a minimal SVG fallback template.
     *
     * @return string
     */
    private static function fallbackTemplate(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 210 297"><ellipse cx="105" cy="150.22" rx="84.542" ry="84.542" fill="__BACKGROUND__"/><circle cx="105" cy="150.22" r="48" fill="__OBJECT__"/><text x="105" y="170" fill="#F8FAFC" font-family="sans-serif" font-size="44" text-anchor="middle">__TEXT__</text></svg>';
    }
}
