<?php

/**
 * Apply Distillect logos + gallery preset to an entity (local dev / CLI).
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

/**
 * Maps BrandKit image slots to files in the user's Distillect Images folder.
 */
final class DistillectBranding
{
    public const DEFAULT_SLOT_FILES = [
        'logo_main'          => 'logo_D_distillect_playfair_white_transparent.png',
        'logo_collapsed'     => 'logo_D rounded_transparent_bg.png',
        'logo_login'         => 'logo_D_distillect_playfair_transparent.png',
        'logo_login_dark'    => 'logo_D_distillect_playfair_white_transparent.png',
        'favicon'            => 'logo_D rounded_transparent_bg.png',
    ];

    /**
     * @param bool $withPreset When false (default), only logos + product labels — colours and Style tab stay as-is.
     * @return array{ok: true, lines: list<string>}|array{ok: false, error: string}
     */
    public static function apply(
        int $entities_id,
        string $imagesDir,
        bool $replaceLogos = true,
        bool $withPreset = false,
    ): array {
        $imagesDir = rtrim($imagesDir, '/\\');
        if (!is_dir($imagesDir)) {
            return ['ok' => false, 'error' => 'Images directory not found: ' . $imagesDir];
        }

        $storage = new AssetStorage();
        $existing = Branding::getRowForEntity($entities_id);
        $logoFields = [];

        foreach (self::DEFAULT_SLOT_FILES as $slot => $basename) {
            $source = $imagesDir . DIRECTORY_SEPARATOR . $basename;
            if (!is_file($source)) {
                continue;
            }
            if (!$replaceLogos && is_string($existing[$slot] ?? null) && $existing[$slot] !== '') {
                $logoFields[$slot] = $existing[$slot];
                continue;
            }
            $imported = $storage->importFromPath($entities_id, $slot, $source);
            if ($imported['ok'] === false) {
                return ['ok' => false, 'error' => $slot . ': ' . $imported['error']];
            }
            if (is_string($existing[$slot] ?? null) && $existing[$slot] !== '') {
                $storage->delete($entities_id, (string) $existing[$slot]);
            }
            $logoFields[$slot] = $imported['filename'];
        }

        $row = [
            'entities_id'       => $entities_id,
            'inherit'           => 0,
            'product_name'      => 'Distillect',
            'page_title_prefix' => 'Distillect',
            'login_heading'     => 'Welcome to Distillect',
            'email_embed_logo'  => 1,
            'date_mod'          => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
        ];

        if ($withPreset) {
            $preset = TemplateCatalog::get('distillect');
            if ($preset === null) {
                return ['ok' => false, 'error' => 'Gallery preset "distillect" is missing'];
            }
            $row['style_json'] = StyleSchema::encode(StyleSchema::sanitize($preset['style'] ?? []));
            foreach (Branding::COLOR_FIELDS as $field) {
                $hex = $preset['colors'][$field] ?? null;
                $row[$field] = CssSanitizer::normalizeHexColor(is_string($hex) ? $hex : null);
            }
        }

        foreach (Branding::IMAGE_SLOTS as $slot) {
            if (isset($logoFields[$slot])) {
                $row[$slot] = $logoFields[$slot];
            } elseif (isset($existing[$slot])) {
                $row[$slot] = $existing[$slot];
            }
        }

        // Teams slide is a marketing layout, not a login backdrop — keep login on solid colour only.
        if (is_string($existing['login_background_image'] ?? null) && $existing['login_background_image'] !== '') {
            $storage->delete($entities_id, (string) $existing['login_background_image']);
            $row['login_background_image'] = null;
        }

        $branding = new Branding();
        $lines = [];

        if ($existing === []) {
            $row['date_creation'] = $row['date_mod'];
            $id = $branding->add($row);
            if ($id === false) {
                return ['ok' => false, 'error' => 'Failed to create branding row for entity ' . $entities_id];
            }
            $lines[] = 'Created branding for entity ' . $entities_id;
        } else {
            $row['id'] = (int) $existing['id'];
            if (!$branding->update($row)) {
                return ['ok' => false, 'error' => 'Failed to update branding row for entity ' . $entities_id];
            }
            $lines[] = 'Updated branding for entity ' . $entities_id;
        }

        foreach ($logoFields as $slot => $filename) {
            $lines[] = $slot . ' ← ' . self::DEFAULT_SLOT_FILES[$slot] . ' (' . $filename . ')';
        }
        if ($withPreset) {
            $lines[] = 'Colours + style: Distillect gallery preset applied';
        } else {
            $lines[] = 'Colours + style unchanged (use --with-preset to apply Distillect gallery)';
        }

        return ['ok' => true, 'lines' => $lines];
    }

    /**
     * Re-apply the Clean cards palette on an entity (keeps logos and other fields).
     *
     * @return array{ok: true, lines: list<string>}|array{ok: false, error: string}
     */
    public static function applyCleanCardsPreset(int $entities_id): array
    {
        $preset = TemplateCatalog::get('clean_cards');
        if ($preset === null) {
            return ['ok' => false, 'error' => 'Gallery preset "clean_cards" is missing'];
        }

        $existing = Branding::getRowForEntity($entities_id);
        if ($existing === []) {
            return ['ok' => false, 'error' => 'No branding row for entity ' . $entities_id];
        }

        $row = [
            'id'         => (int) $existing['id'],
            'style_json' => StyleSchema::encode(StyleSchema::sanitize($preset['style'] ?? [])),
            'date_mod'   => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
        ];

        foreach (Branding::COLOR_FIELDS as $field) {
            $hex = $preset['colors'][$field] ?? null;
            $row[$field] = CssSanitizer::normalizeHexColor(is_string($hex) ? $hex : null);
        }

        $branding = new Branding();
        if (!$branding->update($row)) {
            return ['ok' => false, 'error' => 'Failed to update branding colours for entity ' . $entities_id];
        }

        return [
            'ok'    => true,
            'lines' => [
                'Restored Clean cards colours + style on entity ' . $entities_id,
                'Sidebar: ' . ($row['color_sidebar'] ?? ''),
            ],
        ];
    }
}