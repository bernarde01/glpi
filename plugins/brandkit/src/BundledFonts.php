<?php

/**
 * Bundled UI fonts (OFL / Apache 2.0) served from public/fonts/.
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

final class BundledFonts
{
    /**
     * @var array<string, array{css: string, dir: string}>
     */
    public const FAMILIES = [
        'inter'       => ['css' => 'Inter', 'dir' => 'inter'],
        'poppins'     => ['css' => 'Poppins', 'dir' => 'poppins'],
        'roboto'      => ['css' => 'Roboto', 'dir' => 'roboto'],
        'open_sans'   => ['css' => 'Open Sans', 'dir' => 'open_sans'],
        'montserrat'  => ['css' => 'Montserrat', 'dir' => 'montserrat'],
        'lato'        => ['css' => 'Lato', 'dir' => 'lato'],
        'nunito'      => ['css' => 'Nunito', 'dir' => 'nunito'],
        'raleway'     => ['css' => 'Raleway', 'dir' => 'raleway'],
        'ibm_plex'    => ['css' => 'IBM Plex Sans', 'dir' => 'ibm_plex'],
        'source_sans' => ['css' => 'Source Sans 3', 'dir' => 'source_sans'],
    ];

    /**
     * @var list<int>
     */
    public const WEIGHTS = [400, 700];

    public static function directory(): string
    {
        return dirname(__DIR__) . '/public/fonts';
    }

    public static function publicBase(): string
    {
        $path = '/plugins/brandkit/fonts';
        if (class_exists(\Html::class, false) && method_exists(\Html::class, 'getPrefixedUrl')) {
            return rtrim((string) \Html::getPrefixedUrl($path), '/');
        }

        return $path;
    }

    /**
     * @return array<string, string> id => CSS family name
     */
    public static function cssNames(): array
    {
        $out = [];
        foreach (self::FAMILIES as $id => $meta) {
            $out[$id] = $meta['css'];
        }

        return $out;
    }

    /**
     * @font-face rules for one bundled family, or empty when unknown / using CDN.
     *
     * @param array<string, mixed> $style
     */
    public static function fontFaceCss(array $style): string
    {
        if ((int) ($style['font_cdn'] ?? 0) === 1) {
            return '';
        }
        $id = (string) ($style['font_family'] ?? '');
        if (!isset(self::FAMILIES[$id])) {
            return '';
        }
        $meta = self::FAMILIES[$id];
        $base = self::publicBase() . '/' . $meta['dir'];
        $family = $meta['css'];
        $blocks = [];
        foreach (self::WEIGHTS as $weight) {
            $url = $base . '/' . $meta['dir'] . '-latin-' . $weight . '.woff2';
            $blocks[] = <<<CSS
@font-face {
  font-family: "{$family}";
  font-style: normal;
  font-weight: {$weight};
  font-display: swap;
  src: url("{$url}") format("woff2");
}
CSS;
        }

        return implode("\n", $blocks);
    }

    public static function filesExist(string $id): bool
    {
        if (!isset(self::FAMILIES[$id])) {
            return false;
        }
        $dir = self::directory() . '/' . self::FAMILIES[$id]['dir'];
        foreach (self::WEIGHTS as $weight) {
            $file = $dir . '/' . self::FAMILIES[$id]['dir'] . '-latin-' . $weight . '.woff2';
            if (!is_file($file) || filesize($file) < 1000) {
                return false;
            }
        }

        return true;
    }
}
