<?php

/**
 * Admin-saved gallery templates (one row per look).
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

use CommonDBTM;
use Session;

class StyleTemplate extends CommonDBTM
{
    public static string $rightname = 'plugin_brandkit_config';

    public static function getTypeName($nb = 0)
    {
        return _n('Style template', 'Style templates', $nb, 'brandkit');
    }

    public static function getTable($classname = null): string
    {
        if ($classname !== null && $classname !== self::class) {
            return parent::getTable($classname);
        }

        return 'glpi_plugin_brandkit_templates';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listForGallery(): array
    {
        global $DB;
        if (!$DB instanceof \DBmysql || !$DB->connected || !$DB->tableExists(self::getTable())) {
            return [];
        }

        $out = [];
        foreach ($DB->request(['FROM' => self::getTable(), 'ORDER' => 'name']) as $row) {
            $decoded = json_decode((string) ($row['template_json'] ?? ''), true);
            if (!is_array($decoded)) {
                continue;
            }
            $decoded['id'] = 'custom_' . (int) $row['id'];
            $decoded['name'] = (string) ($row['name'] ?? $decoded['name'] ?? 'Custom');
            $decoded['description'] = (string) ($row['description'] ?? $decoded['description'] ?? '');
            $decoded['db_id'] = (int) $row['id'];
            $item = TemplateCatalog::normalize($decoded, true);
            if ($item !== null) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: true, id: int, item: array<string, mixed>}|array{ok: false, error: string}
     */
    public static function saveFromInput(array $input): array
    {
        if (!Session::haveRight(self::$rightname, UPDATE)) {
            return ['ok' => false, 'error' => 'Access denied'];
        }

        $name = mb_substr(trim(strip_tags((string) ($input['name'] ?? ''))), 0, 80);
        if ($name === '') {
            return ['ok' => false, 'error' => 'Name is required'];
        }

        $payload = TemplateCatalog::normalize([
            'id'          => 'custom',
            'name'        => $name,
            'description' => (string) ($input['description'] ?? ''),
            'style'       => $input['style'] ?? [],
            'colors'      => $input['colors'] ?? [],
        ], true);
        if ($payload === null) {
            return ['ok' => false, 'error' => 'Invalid template'];
        }

        $now = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
        $row = [
            'name'          => $name,
            'description'   => $payload['description'],
            'entities_id'   => (int) ($input['entities_id'] ?? 0),
            'template_json' => json_encode([
                'kind'        => 'brandkit-template',
                'brandkit'    => StyleSchema::VERSION,
                'name'        => $name,
                'description' => $payload['description'],
                'colors'      => $payload['colors'],
                'style'       => $payload['style'],
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'date_mod'      => $now,
        ];

        $obj = new self();
        $row['date_creation'] = $now;
        $id = $obj->add($row);
        if ($id === false) {
            return ['ok' => false, 'error' => 'Unable to save template'];
        }

        $payload['id'] = 'custom_' . (int) $id;
        $payload['db_id'] = (int) $id;
        $payload['custom'] = 1;

        return ['ok' => true, 'id' => (int) $id, 'item' => $payload];
    }
}
