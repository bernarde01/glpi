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

use GlpiPlugin\Brandkit\CssGenerator;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/CssSanitizer.php';
require_once dirname(__DIR__, 2) . '/src/TemplateCatalog.php';
require_once dirname(__DIR__, 2) . '/src/StyleSchema.php';
require_once dirname(__DIR__, 2) . '/src/StyleCss.php';
require_once dirname(__DIR__, 2) . '/src/CssGenerator.php';

final class CssGeneratorTest extends TestCase
{
    public function testGeneratesTablerVariablesAndLoginBackground(): void
    {
        $css = CssGenerator::generate(
            [
                'color_primary'           => '#fe9c5c',
                'color_secondary'         => '#123456',
                'color_accent'            => '#055cad',
                'color_sidebar'           => '#2f3f64',
                'color_header'            => '#ffffff',
                'login_background_color'  => '#0a0a0a',
                'custom_css'              => 'h1{color:navy}</style><script>x</script>',
                'dashboard_card_bg'       => '#111111',
                'dashboard_card_text'     => '#eeeeee',
            ],
            [
                'logo_main'               => '/plugins/brandkit/Asset/logo_main?e=0',
                'logo_login'              => '/plugins/brandkit/Asset/logo_login?e=0',
                'login_background_image'  => '/plugins/brandkit/Asset/login_background_image?e=0',
            ]
        );

        $this->assertStringContainsString('--tblr-primary: #fe9c5c !important;', $css);
        $this->assertStringContainsString('--tblr-primary-rgb: 254, 156, 92 !important;', $css);
        $this->assertStringContainsString('--glpi-mainmenu-bg: #2f3f64 !important;', $css);
        $this->assertStringContainsString('--glpi-logo: url("/plugins/brandkit/Asset/logo_main?e=0") !important;', $css);
        $this->assertStringContainsString('--glpi-logo-dark-login:', $css);
        $this->assertStringContainsString('body.welcome-anonymous', $css);
        $this->assertStringContainsString('background-image: url("/plugins/brandkit/Asset/login_background_image?e=0");', $css);
        $this->assertStringContainsString('background-color: #0a0a0a;', $css);
        $this->assertStringContainsString('--brandkit-dash-card-bg: #111111 !important;', $css);
        $this->assertStringContainsString('h1{color:navy}', $css);
        $this->assertStringNotContainsString('</style>', $css);
        $this->assertStringNotContainsString('<script', $css);
        $this->assertStringNotContainsString('--brandkit-radius', $css);
    }

    public function testStyleJsonIsEmittedWhenPresent(): void
    {
        $css = CssGenerator::generate(
            [
                'style_json' => \GlpiPlugin\Brandkit\StyleSchema::encode(
                    \GlpiPlugin\Brandkit\StyleSchema::presets()['minimal_flat']['style']
                ),
            ],
            []
        );
        $this->assertStringContainsString('--brandkit-radius', $css);
        $this->assertStringContainsString('--tblr-border-radius: 0px', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
    }

    public function testEmptyBrandingProducesMinimalCss(): void
    {
        $css = CssGenerator::generate([], []);
        $this->assertStringContainsString(':root {', $css);
        $this->assertStringNotContainsString('--tblr-primary:', $css);
    }
}
