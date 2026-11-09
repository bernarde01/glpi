<?php

/**
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

/**
 * Sanitise free-form CSS and validate colour values without depending on GLPI.
 */
final class CssSanitizer
{
    /**
     * Strip constructs that could break out of a <style> block or inject script.
     */
    public static function sanitize(string $css): string
    {
        $css = str_replace("\0", '', $css);
        $css = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $css) ?? $css;
        $css = preg_replace('/<\/?style\b[^>]*>/i', '', $css) ?? $css;
        $css = preg_replace('/<\/?script\b[^>]*>/i', '', $css) ?? $css;
        $css = strip_tags($css);
        $css = preg_replace('/javascript\s*:/i', '', $css) ?? $css;
        $css = preg_replace('/expression\s*\(/i', '', $css) ?? $css;
        $css = preg_replace('/-moz-binding/i', '', $css) ?? $css;
        $css = preg_replace('/behavior\s*:/i', '', $css) ?? $css;
        $css = preg_replace('/@import\b[^;{]*;?/i', '', $css) ?? $css;

        return trim($css);
    }

    public static function isValidHexColor(?string $color): bool
    {
        if ($color === null || $color === '') {
            return false;
        }

        return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color);
    }

    /**
     * Normalise #rgb / #rrggbb to lowercase #rrggbb, or null if invalid.
     */
    public static function normalizeHexColor(?string $color): ?string
    {
        if (!self::isValidHexColor($color)) {
            return null;
        }

        $color = strtolower((string) $color);
        if (strlen($color) === 4) {
            $color = sprintf(
                '#%s%s%s%s%s%s',
                $color[1],
                $color[1],
                $color[2],
                $color[2],
                $color[3],
                $color[3]
            );
        }

        return $color;
    }

    /**
     * @return array{r: int, g: int, b: int}|null
     */
    public static function hexToRgb(string $color): ?array
    {
        $color = self::normalizeHexColor($color);
        if ($color === null) {
            return null;
        }

        return [
            'r' => hexdec(substr($color, 1, 2)),
            'g' => hexdec(substr($color, 3, 2)),
            'b' => hexdec(substr($color, 5, 2)),
        ];
    }

    /**
     * Pick black or white for readable text on the given background.
     */
    public static function contrastColor(string $background): string
    {
        $rgb = self::hexToRgb($background);
        if ($rgb === null) {
            return '#1e293b';
        }

        $luminance = (0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b']) / 255;

        return $luminance > 0.55 ? '#1e293b' : '#ffffff';
    }
}
