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
require_once dirname(__DIR__, 2) . '/src/TemplateCatalog.php';
require_once dirname(__DIR__, 2) . '/src/BundledFonts.php';
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
        $style = StyleSchema::sanitize(StyleSchema::presets()['glass_aurora']['style']);
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

    public function testChromeCapsGlobalRadiusAndJoinsInputGroups(): void
    {
        $style = StyleSchema::defaults();
        $style['radius_global'] = 999;
        $style['radius_buttons'] = 999;
        $style['radius_inputs'] = 16;
        $css = StyleCss::generate([], [], StyleSchema::sanitize($style));
        $this->assertStringContainsString('--brandkit-radius-chrome: 10px', $css);
        $this->assertStringContainsString('--tblr-border-radius: 10px', $css);
        $this->assertStringNotContainsString('--tblr-border-radius: 999px', $css);
        $this->assertStringContainsString('.input-group > :first-child', $css);
        $this->assertStringContainsString('input[type="file"]::file-selector-button', $css);
        $this->assertStringContainsString('header.navbar .breadcrumb', $css);
        $this->assertStringContainsString('.btn-sm', $css);
    }

    public function testTabPresetAccentBarTargetsTabspanel(): void
    {
        $style = StyleSchema::defaults();
        $style['tab_preset'] = 'accent_bar';
        $style['tab_item_spacing'] = 4;
        $css = StyleCss::generate([], [], StyleSchema::sanitize($style));
        $this->assertStringContainsString('.card-tabs #tabspanel.nav-tabs .nav-link', $css);
        $this->assertStringContainsString('inset 4px 0 0 var(--tblr-primary)', $css);
        $this->assertStringContainsString('--brandkit-tab-gap: 4px', $css);
    }

    public function testBundledPoppinsEmitsFontFace(): void
    {
        $style = StyleSchema::defaults();
        $style['font_family'] = 'poppins';
        $css = StyleCss::generate([], [], StyleSchema::sanitize($style));
        $this->assertStringContainsString('@font-face', $css);
        $this->assertStringContainsString('font-family: "Poppins"', $css);
        $this->assertStringContainsString('/plugins/brandkit/fonts/poppins/poppins-latin-400.woff2', $css);
        $this->assertStringContainsString('"Poppins", system-ui, sans-serif', $css);
        $style['font_cdn'] = 1;
        $cdn = StyleCss::generate([], [], StyleSchema::sanitize($style));
        $this->assertStringNotContainsString('@font-face', $cdn);
    }

    public function testEveryGalleryTemplateEmitsChromeAndTabs(): void
    {
        foreach (StyleSchema::presets() as $id => $preset) {
            $css = StyleCss::generate($preset['colors'] ?? [], [], StyleSchema::sanitize($preset['style']));
            $this->assertStringContainsString('.input-group > :first-child', $css, $id);
            $this->assertStringContainsString('.card-tabs #tabspanel.nav-tabs .nav-link', $css, $id);
            $this->assertStringContainsString('--brandkit-radius-chrome:', $css, $id);
            $this->assertStringContainsString('input[type="file"]::file-selector-button', $css, $id);
            $font = (string) ($preset['style']['font_family'] ?? 'system');
            if ($font !== 'system') {
                $this->assertStringContainsString('@font-face', $css, $id);
            }
        }
    }

    public function testCleanCardsEmitsCardTabsAndGreyTone(): void
    {
        $preset = StyleSchema::presets()['clean_cards'];
        $css = StyleCss::generate($preset['colors'] ?? [], [], StyleSchema::sanitize($preset['style']));
        $this->assertStringContainsString('--brandkit-tab-heading:', $css);
        $this->assertStringContainsString('.card-tabs.vertical #tabspanel.nav-tabs::before', $css);
        $this->assertStringContainsString('background-color: #eef1f6', $css);
        $this->assertStringContainsString('#brandkit-fields .form-field.row', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(12rem, 34%) 1fr', $css);
        $this->assertStringContainsString('.brandkit-file-ui', $css);
        $this->assertStringContainsString('font-family: "Inter"', $css);
        $this->assertStringContainsString('header.navbar .user-menu-dropdown-toggle', $css);
    }

    public function testWarmTealEmitsMergeTabsAndFilledFileButtons(): void
    {
        $preset = StyleSchema::presets()['warm_teal'];
        $css = StyleCss::generate($preset['colors'] ?? [], [], StyleSchema::sanitize($preset['style']));
        $this->assertStringContainsString('background-color: #f6ead8', $css);
        $this->assertStringContainsString('input[type="file"]::file-selector-button', $css);
        $this->assertStringContainsString('background: var(--tblr-primary)', $css);
        $this->assertStringContainsString('font-family: "Poppins"', $css);
        $this->assertStringContainsString('.card-tabs.vertical #tabspanel.nav-tabs .nav-link.active', $css);
        $this->assertStringContainsString('border-radius: 999px', $css);
    }
}
