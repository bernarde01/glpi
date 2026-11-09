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

use GlpiPlugin\Brandkit\TemplateCatalog;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/CssSanitizer.php';
require_once dirname(__DIR__, 2) . '/src/TemplateCatalog.php';
require_once dirname(__DIR__, 2) . '/src/StyleSchema.php';

final class TemplateCatalogTest extends TestCase
{
    public function testGalleryJsonFilesLoadAsTenDistinctLooks(): void
    {
        $list = TemplateCatalog::builtins();
        $this->assertGreaterThanOrEqual(8, count($list));
        $this->assertLessThanOrEqual(12, count($list));
        $ids = array_column($list, 'id');
        $this->assertSame($ids, array_unique($ids));
        foreach ($list as $item) {
            $this->assertNotSame('', $item['name']);
            $this->assertNotSame('', $item['description']);
            $this->assertArrayHasKey('color_primary', $item['colors']);
            $this->assertSame($item['id'], $item['style']['preset']);
        }
        $this->assertNotNull(TemplateCatalog::get('neon_tech'));
        $this->assertNull(TemplateCatalog::get('not-a-template'));
        $this->assertSame(TemplateCatalog::builtins(), TemplateCatalog::all());
    }

    public function testNormalizeRejectsScriptInName(): void
    {
        $item = TemplateCatalog::normalize([
            'id'          => 'x',
            'name'        => '<script>alert(1)</script>Nice',
            'description' => '<b>Bold</b> copy',
            'style'       => ['radius_global' => 8],
            'colors'      => ['color_primary' => '#112233'],
        ], false);
        $this->assertNotNull($item);
        $this->assertStringNotContainsString('<script', $item['name']);
        $this->assertStringNotContainsString('<', $item['name']);
        $this->assertStringNotContainsString('<', $item['description']);
        $this->assertSame('#112233', $item['colors']['color_primary']);
    }
}
