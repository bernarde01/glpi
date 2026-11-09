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

use GlpiPlugin\Brandkit\StyleCss;
use GlpiPlugin\Brandkit\StyleSchema;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/CssSanitizer.php';
require_once dirname(__DIR__, 2) . '/src/StyleSchema.php';
require_once dirname(__DIR__, 2) . '/src/StyleCss.php';

final class StyleCssTest extends TestCase
{
    public function testMinimalFlatEmitsZeroRadiusAndNoMotion(): void
    {
        $style = StyleSchema::sanitize(StyleSchema::presets()['minimal_flat']['style']);
        $css = StyleCss::generate(['color_primary' => '#111111'], [], $style);

        $this->assertStringContainsString('--tblr-border-radius: 0px', $css);
        $this->assertStringContainsString('--brandkit-duration: 0s', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('#navbar-menu .nav-link', $css);
        $this->assertStringContainsString('.modal-content', $css);
        $this->assertStringContainsString('.kanban-item', $css);
        $this->assertStringContainsString('.form-control', $css);
        $this->assertStringContainsString('.table', $css);
    }

    public function testGlassEmitsBlurAndFloatingRulesForGlassPreset(): void
    {
        $style = StyleSchema::sanitize(StyleSchema::presets()['glass']['style']);
        $css = StyleCss::generate([], [], $style);
        $this->assertStringContainsString('backdrop-filter: blur', $css);
        $this->assertStringContainsString('box-shadow: inset 3px 0 0 var(--tblr-primary)', $css);
    }

    public function testBoldGradientEmitsLinearGradient(): void
    {
        $style = StyleSchema::sanitize(StyleSchema::presets()['bold_gradient']['style']);
        $css = StyleCss::generate([
            'color_primary'   => '#0e7490',
            'color_secondary' => '#0f172a',
        ], [], $style);
        $this->assertStringContainsString('linear-gradient(', $css);
        $this->assertStringContainsString('.btn-primary', $css);
        $this->assertStringContainsString('header.navbar', $css);
    }

    public function testFloatingSidebarEmitsMarginAndRadius(): void
    {
        $style = StyleSchema::defaults();
        $style['sidebar_preset'] = 'floating';
        $css = StyleCss::generate([], [], StyleSchema::sanitize($style));
        $this->assertStringContainsString('body.vertical-layout .sidebar', $css);
        $this->assertStringContainsString('--brandkit-radius-lg', $css);
    }

    public function testWatermarkTextIsQuotedAndNonInteractive(): void
    {
        $style = StyleSchema::defaults();
        $style['watermark_type'] = 'text';
        $style['watermark_text'] = 'Confidential';
        $style['watermark_app'] = 1;
        $css = StyleCss::generate([], [], StyleSchema::sanitize($style));
        $this->assertStringContainsString('pointer-events: none', $css);
        $this->assertStringContainsString('--brandkit-watermark-text: "Confidential"', $css);
        $this->assertStringContainsString('body.central .brandkit-watermark', $css);
    }
}
