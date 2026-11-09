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

use GlpiPlugin\Brandkit\CssSanitizer;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/CssSanitizer.php';

final class CssSanitizerTest extends TestCase
{
    public function testSanitizeStripsStyleBreakout(): void
    {
        $dirty = 'body{color:red}</style><script>alert(1)</script><style>a{color:blue}';
        $clean = CssSanitizer::sanitize($dirty);

        $this->assertStringNotContainsString('</style>', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert', $clean);
        $this->assertStringContainsString('body{color:red}', $clean);
        $this->assertStringContainsString('a{color:blue}', $clean);
    }

    public function testSanitizeStripsJavascriptUrlAndImport(): void
    {
        $dirty = 'a{background:javascript:alert(1)} @import url("https://evil.example/x.css"); b{color:green}';
        $clean = CssSanitizer::sanitize($dirty);

        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('@import', $clean);
        $this->assertStringContainsString('b{color:green}', $clean);
    }

    public function testNormalizeHexColor(): void
    {
        $this->assertSame('#aabbcc', CssSanitizer::normalizeHexColor('#ABC'));
        $this->assertSame('#fe9c5c', CssSanitizer::normalizeHexColor('#FE9C5C'));
        $this->assertNull(CssSanitizer::normalizeHexColor('red'));
        $this->assertNull(CssSanitizer::normalizeHexColor(''));
        $this->assertFalse(CssSanitizer::isValidHexColor('url(x)'));
    }

    public function testContrastColor(): void
    {
        $this->assertSame('#1e293b', CssSanitizer::contrastColor('#ffffff'));
        $this->assertSame('#ffffff', CssSanitizer::contrastColor('#000000'));
    }

    public function testHexToRgb(): void
    {
        $this->assertSame(['r' => 254, 'g' => 156, 'b' => 92], CssSanitizer::hexToRgb('#fe9c5c'));
    }
}
