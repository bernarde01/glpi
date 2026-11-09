<?php

/**
 * Functional smoke tests. Run from the GLPI tree:
 *
 *   php vendor/bin/phpunit -c phpunit.xml.dist plugins/brandkit/tests/functional
 *
 * Requires a testing database (`php bin/console database:install --env=testing ...`).
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use Entity;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Brandkit\AssetStorage;
use GlpiPlugin\Brandkit\Branding;
use GlpiPlugin\Brandkit\CssGenerator;
use GlpiPlugin\Brandkit\MailBranding;
use Plugin;

final class BrandingTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        global $DB;
        if (!$DB->tableExists(Branding::getTable())) {
            $this->markTestSkipped('BrandKit is not installed in the testing database.');
        }
    }

    public function testPluginIsPresentAndTableExists(): void
    {
        global $DB;

        $this->assertTrue($DB->tableExists(Branding::getTable()));
        $plugin = new Plugin();
        $this->assertTrue(
            $plugin->isInstalled('brandkit') || is_dir(GLPI_ROOT . '/plugins/brandkit'),
            'brandkit plugin directory must exist'
        );
    }

    public function testRootEntityResolvesWithoutInherit(): void
    {
        $row = Branding::resolve(0);
        $this->assertSame(0, (int) $row['entities_id']);
        $this->assertSame(0, (int) $row['inherit']);
    }

    public function testChildInheritsUntilOverride(): void
    {
        $this->login();
        $this->addRightToProfile('Super-Admin', Branding::$rightname, ALLSTANDARDRIGHT);
        $this->login(); // reload rights

        $child_id = $this->createItem(Entity::class, [
            'name'        => 'BrandKit child ' . random_int(1, 99999),
            'entities_id' => 0,
        ])->getID();

        $child_row = Branding::resolve($child_id);
        $this->assertSame(0, (int) $child_row['entities_id']);

        $branding = new Branding();
        $ok = $branding->saveFromForm([
            'entities_id'    => $child_id,
            'inherit'        => 0,
            'color_primary'  => '#112233',
            'product_name'   => 'ChildBrand',
        ]);
        $this->assertTrue($ok);

        $resolved = Branding::resolve($child_id);
        $this->assertSame($child_id, (int) $resolved['entities_id']);
        $this->assertSame('#112233', $resolved['color_primary']);
        $this->assertSame('ChildBrand', $resolved['product_name']);
    }

    public function testHostMapPicksLoginEntity(): void
    {
        $map = Branding::parseHostMap("client.example.com=42\n# comment\nbad line\nroot.local=0");
        $this->assertSame(['client.example.com' => 42, 'root.local' => 0], $map);
    }

    public function testMailWrapperInjectsLogoAndColours(): void
    {
        $html = MailBranding::wrapHtml('<p>Hello</p>', [
            'entities_id'       => 0,
            'color_primary'     => '#fe9c5c',
            'color_secondary'   => '#123456',
            'email_header_html' => '<p>Header</p>',
            'email_footer_html' => '<p>Footer</p>',
            'email_embed_logo'  => 0,
            'logo_main'         => null,
        ]);

        $this->assertStringContainsString('Hello', $html);
        $this->assertStringContainsString('Header', $html);
        $this->assertStringContainsString('Footer', $html);
        $this->assertStringContainsString('#fe9c5c', $html);
        $this->assertStringContainsString('brandkit-mail', $html);
    }

    public function testCssGeneratorFallsBackToCoreWhenEmpty(): void
    {
        $css = CssGenerator::generate([], []);
        $this->assertStringContainsString(':root {', $css);
        $this->assertStringNotContainsString('--tblr-primary:', $css);
    }

    public function testStyleJsonIsStoredAndResolved(): void
    {
        $this->login();
        $this->addRightToProfile('Super-Admin', Branding::$rightname, ALLSTANDARDRIGHT);
        $this->login();

        $child_id = $this->createItem(Entity::class, [
            'name'        => 'BrandKit style ' . random_int(1, 99999),
            'entities_id' => 0,
        ])->getID();

        $ok = (new Branding())->saveFromForm([
            'entities_id' => $child_id,
            'inherit'     => 0,
            'style'       => [
                'preset'           => 'glass',
                'sidebar_preset'   => 'glass',
                'radius_global'    => 12,
                'disable_animations' => 0,
            ],
        ]);
        $this->assertTrue($ok);

        $resolved = Branding::resolve($child_id);
        $style = \GlpiPlugin\Brandkit\StyleSchema::fromRow($resolved);
        $this->assertSame('glass', $style['sidebar_preset']);
        $this->assertSame(12, $style['radius_global']);
    }

    public function testExtensionlessTempUploadIsAcceptedAgainstDocumentIsImage(): void
    {
        $png = (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
        $tmp = tempnam(sys_get_temp_dir(), 'php');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, $png);

        $this->assertFalse(
            \Document::isImage($tmp),
            'Document::isImage() rejects PHP upload temps (no image extension)'
        );

        $dir = sys_get_temp_dir() . '/brandkit-func-' . bin2hex(random_bytes(3));
        mkdir($dir, 0755, true);
        $storage = new AssetStorage($dir);
        $result = $storage->store(0, 'logo_main', [
            'name'     => 'logo.png',
            'type'     => 'image/png',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($png),
        ]);
        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertNotNull($storage->absolutePath(0, $result['filename']));
        $storage->purgeAll();
        @rmdir($dir);
    }
}
