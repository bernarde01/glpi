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

    public function testFormLayoutDoesNotForceFullWidthFieldRows(): void
    {
        foreach (['default', 'two_column'] as $layout) {
            $style = StyleSchema::defaults();
            $style['form_layout'] = $layout;
            $css = StyleCss::generate([], [], StyleSchema::sanitize($style));
            $this->assertStringNotContainsString('flex: 0 0 100%', $css, $layout);
            $this->assertStringNotContainsString('#brandkit-fields .form-field.row.col-sm-6', $css, $layout);
            $this->assertStringNotContainsString('.field-container > .select2-container', $css, $layout);
        }
    }

    public function testComfortableDensityDoesNotOverrideGlpiSpacing(): void
    {
        $style = StyleSchema::defaults();
        $style['density'] = 'comfortable';
        $css = StyleCss::generate([], [], StyleSchema::sanitize($style));
        $this->assertStringNotContainsString('.asset .card-body', $css);
        $this->assertStringNotContainsString('--tblr-card-spacer-y', $css);
    }

    public function testZeroInputRadiusFallsBackToChromeWhenGlobalIsRounded(): void
    {
        $style = StyleSchema::sanitize(StyleSchema::presets()['clean_cards']['style']);
        $style['radius_inputs'] = 0;
        $css = StyleCss::generate([], [], $style);
        $this->assertStringContainsString('--brandkit-radius-input: 10px', $css);
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
        $this->assertStringContainsString('.input-group:not(.input-group-flat)', $css);
        $this->assertStringContainsString('.input-group.input-group-flat > :first-child', $css);
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
            $this->assertStringContainsString('.input-group.input-group-flat > :first-child', $css, $id);
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
        $this->assertStringContainsString('.card-tabs.vertical ul#tabspanel.nav-tabs::before', $css);
        $this->assertStringContainsString('background-color: #eef1f6', $css);
        $this->assertStringNotContainsString('grid-template-columns: minmax(12rem, 34%) 1fr', $css);
        $this->assertStringNotContainsString('.asset .card-body', $css);
        $this->assertStringContainsString('.brandkit-file-ui', $css);
        $this->assertStringContainsString('font-family: "Inter"', $css);
        $this->assertStringContainsString('header.navbar .user-menu-dropdown-toggle', $css);
        $this->assertStringContainsString('.sidebar #navbar-menu .dropdown-item.active', $css);
        $this->assertStringContainsString('body.vertical-layout:not(.navbar-collapsed) .sidebar #navbar-menu .dropdown-menu', $css);
        $this->assertStringContainsString('#navigationheader.navigationheader', $css);
        $this->assertStringContainsString('border-left-width: 0 !important', $css);
        $this->assertStringContainsString('div.card-tabs.vertical > ul#tabspanel.nav.nav-tabs > li.nav-item > a.nav-link.active', $css);
        $this->assertStringContainsString('.d-none.d-xxl-block', $css);
        $this->assertStringContainsString('.text-muted-menu', $css);
        $this->assertStringContainsString('border-radius: 50%', $css);
        $this->assertStringContainsString('#brandkit-fields > .card.border-0 > .card-header', $css);
        $this->assertStringContainsString('body.central .asset .card.border-0 > .card-header', $css);
        $this->assertStringContainsString('body.central table.search-results thead th', $css);
        $this->assertStringContainsString('body.central [data-glpi-tab-content] table.table thead th', $css);
        $this->assertStringContainsString('form[role="search"]', $css);
        $this->assertStringContainsString('body.central #page', $css);
        $this->assertStringContainsString('.select2-container .select2-selection--single', $css);
        $this->assertStringContainsString('.select2-dropdown.select2-dropdown--below', $css);
        $this->assertStringContainsString('border-top-left-radius: var(--brandkit-radius-input)', $css);
        $this->assertStringContainsString('line-height: normal !important', $css);
        $this->assertStringContainsString('.brandkit-accordion {', $css);
        $this->assertStringContainsString('border-radius: var(--brandkit-radius-card)', $css);
        $this->assertStringContainsString('.sp-colorize-container', $css);
        $this->assertStringNotContainsString('--glpi-timeline-itil-content-bg: var(--tblr-bg-surface, #fff)', $css);
        $this->assertStringContainsString('--glpi-itil-secondary-bg: #eef1f6', $css);
        $this->assertStringContainsString('body.central .itil-object .itil-left-side', $css);
        $this->assertStringContainsString('body.central .timeline-item .timeline-content::before', $css);
        $this->assertStringContainsString('#itil-object-container.right-collapsed .itil-right-side .switch-panel-width', $css);
        $this->assertStringContainsString('height: calc(100% + 4px)', $css);
        $this->assertStringNotContainsString('flex: 0 0 90px', $css);
        $this->assertStringContainsString('#itil-object-container.right-expanded .itil-footer .switch-panel-width', $css);
    }

    public function testSidebarDropdownResetIsEmittedForEveryTemplate(): void
    {
        foreach (StyleSchema::presets() as $id => $preset) {
            $css = StyleCss::generate($preset['colors'] ?? [], [], StyleSchema::sanitize($preset['style']));
            $this->assertStringContainsString(
                'body.vertical-layout:not(.navbar-collapsed) .sidebar #navbar-menu .dropdown-menu',
                $css,
                $id
            );
            $this->assertStringContainsString(
                'body.vertical-layout:not(.navbar-collapsed) .sidebar #navbar-menu .dropdown-menu.show',
                $css,
                $id
            );
            $this->assertStringContainsString('.sidebar #navbar-menu .dropdown-item', $css, $id);
            $this->assertStringContainsString('#navigationheader.navigationheader', $css, $id);
        }
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
