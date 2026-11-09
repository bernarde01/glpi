<?php

/**
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

use GlpiPlugin\Brandkit\StyleSchema;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/CssSanitizer.php';
require_once dirname(__DIR__, 2) . '/src/StyleSchema.php';

final class StyleSchemaTest extends TestCase
{
    public function testSanitizeDropsUnknownValues(): void
    {
        $clean = StyleSchema::sanitize([
            'sidebar_preset'     => 'not-a-preset',
            'radius_global'      => 3,
            'shadow_cards'       => 'huge',
            'font_url'           => 'javascript:alert(1)',
            'watermark_text'     => '<script>x</script>CONFIDENTIAL',
            'page_bg_overlay'    => 999,
            'disable_animations' => '1',
        ]);

        $this->assertSame('default', $clean['sidebar_preset']);
        $this->assertSame(0, $clean['radius_global']);
        $this->assertSame('none', $clean['shadow_cards']);
        $this->assertSame('', $clean['font_url']);
        $this->assertSame(80, $clean['page_bg_overlay']);
        $this->assertSame(1, $clean['disable_animations']);
        $this->assertStringNotContainsString('<script', $clean['watermark_text']);
    }

    public function testGoogleFontUrlAllowList(): void
    {
        $ok = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap';
        $this->assertSame($ok, StyleSchema::sanitizeFontUrl($ok));
        $this->assertSame('', StyleSchema::sanitizeFontUrl('https://evil.example/css2?family=x'));
        $this->assertSame('', StyleSchema::sanitizeFontUrl('https://fonts.googleapis.com/css2?family="x'));
    }

    public function testPresetsExistAndSanitize(): void
    {
        $presets = StyleSchema::presets();
        foreach (['corporate_clean', 'soft_rounded', 'glass', 'bold_gradient', 'minimal_flat'] as $key) {
            $this->assertArrayHasKey($key, $presets);
            $style = StyleSchema::sanitize($presets[$key]['style']);
            $this->assertSame($key, $style['preset']);
        }
        $this->assertSame(0, StyleSchema::sanitize($presets['minimal_flat']['style'])['radius_global']);
        $this->assertSame('glass', StyleSchema::sanitize($presets['glass']['style'])['sidebar_preset']);
        $this->assertSame(1, StyleSchema::sanitize($presets['bold_gradient']['style'])['gradient_header']['enabled']);
    }

    public function testExportImportRoundTrip(): void
    {
        $style = StyleSchema::sanitize(StyleSchema::presets()['soft_rounded']['style']);
        $payload = StyleSchema::export(
            ['color_primary' => '#0e7490', 'product_name' => 'Distillect GLPI'],
            $style
        );
        $imported = StyleSchema::import(json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertTrue($imported['ok']);
        $this->assertSame('soft_rounded', $imported['data']['style']['preset']);
        $this->assertSame('#0e7490', $imported['data']['colors']['color_primary']);
        $this->assertSame('Distillect GLPI', $imported['data']['product_name']);
    }

    public function testImportRejectsGarbage(): void
    {
        $this->assertFalse(StyleSchema::import('not json')['ok']);
    }
}
