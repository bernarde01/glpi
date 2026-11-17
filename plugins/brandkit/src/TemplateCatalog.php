<?php

/**
 * Built-in Style gallery templates loaded from templates/gallery/*.json.
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

final class TemplateCatalog
{
    /**
     * @var list<string>
     */
    public const COLOR_KEYS = [
        'color_primary',
        'color_secondary',
        'color_accent',
        'color_sidebar',
        'color_header',
        'login_background_color',
        'dashboard_card_bg',
        'dashboard_card_text',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function builtins(): array
    {
        $dir = self::galleryDir();
        if (!is_dir($dir)) {
            return [];
        }

        $out = [];
        $files = glob($dir . '/*.json') ?: [];
        sort($files);
        foreach ($files as $file) {
            $raw = @file_get_contents($file);
            if (!is_string($raw) || $raw === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }
            $item = self::normalize($decoded, false);
            if ($item !== null) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * Built-ins plus custom DB templates.
     *
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        $list = self::builtins();
        foreach (self::customs() as $custom) {
            $list[] = $custom;
        }

        return $list;
    }

    /**
     * @return array<string, array{label: string, style: array<string, mixed>, colors?: array<string, mixed>}>
     */
    public static function asPresets(): array
    {
        $out = [];
        foreach (self::builtins() as $item) {
            $out[$item['id']] = [
                'label'  => $item['name'],
                'style'  => $item['style'],
                'colors' => $item['colors'],
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $id): ?array
    {
        $id = self::slug($id);
        if ($id === '') {
            return null;
        }
        if (str_starts_with($id, 'custom_')) {
            $db_id = (int) substr($id, 7);
            foreach (self::customs() as $custom) {
                if ((int) ($custom['db_id'] ?? 0) === $db_id) {
                    return $custom;
                }
            }

            return null;
        }
        foreach (self::builtins() as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>|null
     */
    public static function normalize(array $raw, bool $custom): ?array
    {
        $id = self::slug((string) ($raw['id'] ?? ''));
        $name = mb_substr(trim(strip_tags((string) ($raw['name'] ?? ''))), 0, 80);
        if ($name === '') {
            return null;
        }
        if ($id === '' && !$custom) {
            return null;
        }

        $style = StyleSchema::sanitize($raw['style'] ?? []);
        if ($id !== '') {
            $style['preset'] = $id;
        }

        $colors = [];
        $raw_colors = is_array($raw['colors'] ?? null) ? $raw['colors'] : [];
        foreach (self::COLOR_KEYS as $key) {
            $hex = CssSanitizer::normalizeHexColor(isset($raw_colors[$key]) ? (string) $raw_colors[$key] : null);
            $colors[$key] = $hex;
        }

        return [
            'id'          => $id,
            'name'        => $name,
            'description' => mb_substr(trim(strip_tags((string) ($raw['description'] ?? ''))), 0, 240),
            'colors'      => $colors,
            'style'       => $style,
            'custom'      => $custom ? 1 : 0,
            'db_id'       => isset($raw['db_id']) ? (int) $raw['db_id'] : 0,
        ];
    }

    public static function galleryDir(): string
    {
        $fallback = dirname(__DIR__) . '/templates/gallery';
        if (!defined('GLPI_PLUGINS_DIRECTORIES')) {
            return $fallback;
        }
        $php_dir = \Plugin::getPhpDir('brandkit');
        if (is_string($php_dir) && $php_dir !== '' && is_dir($php_dir . '/templates/gallery')) {
            return $php_dir . '/templates/gallery';
        }

        return $fallback;
    }

    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? '';

        return mb_substr(trim($value, '_'), 0, 64);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function customs(): array
    {
        // Unit tests never load CommonDBTM; skip the DB catalog there.
        if (!class_exists(\CommonDBTM::class, false)) {
            return [];
        }
        try {
            return StyleTemplate::listForGallery();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
