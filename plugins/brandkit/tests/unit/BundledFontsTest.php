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

use GlpiPlugin\Brandkit\BundledFonts;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/BundledFonts.php';

final class BundledFontsTest extends TestCase
{
    public function testEveryFamilyShipsWoff2AndALicence(): void
    {
        $this->assertNotEmpty(BundledFonts::FAMILIES);
        foreach (array_keys(BundledFonts::FAMILIES) as $id) {
            $this->assertTrue(BundledFonts::filesExist($id), $id . ' woff2 missing');
            $dir = BundledFonts::directory() . '/' . BundledFonts::FAMILIES[$id]['dir'];
            $lic = glob($dir . '/OFL.txt') ?: glob($dir . '/LICENSE.txt');
            $this->assertNotSame([], $lic, $id . ' licence missing');
            $this->assertGreaterThan(1000, filesize($lic[0]));
        }
        $this->assertArrayHasKey('poppins', BundledFonts::cssNames());
        $this->assertSame('Open Sans', BundledFonts::cssNames()['open_sans']);
    }
}
