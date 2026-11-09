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

/**
 * Mimic GLPI's Document::isImage(): it rejects paths without an image
 * extension. PHP upload temps look like /tmp/phpAbC123, so calling
 * Document::isImage($tmp) always fails on a real install.
 */
if (!class_exists(\Document::class, false)) {
    final class Document
    {
        public static function isImage($file): bool
        {
            if (!is_string($file) || !is_file($file)) {
                return false;
            }
            $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));

            return in_array($ext, ['bmp', 'gif', 'jpeg', 'jpg', 'png', 'webp'], true);
        }
    }
}

use GlpiPlugin\Brandkit\AssetStorage;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/AssetStorage.php';

final class AssetStorageTest extends TestCase
{
    private string $tmpdir;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        $this->tmpdir = sys_get_temp_dir() . '/brandkit-test-' . bin2hex(random_bytes(4));
        mkdir($this->tmpdir, 0755, true);
    }

    protected function tearDown(): void
    {
        $storage = new AssetStorage($this->tmpdir);
        $storage->purgeAll();
        if (is_dir($this->tmpdir)) {
            @rmdir($this->tmpdir);
        }
    }

    public function testRejectsUnknownSlotAndPathTraversalFilename(): void
    {
        $storage = new AssetStorage($this->tmpdir);
        $this->assertFalse(AssetStorage::isAllowedSlot('not_a_slot'));
        $this->assertTrue(AssetStorage::isAllowedSlot('logo_main'));
        $this->assertFalse($storage->isSafeFilename('../etc/passwd'));
        $this->assertFalse($storage->isSafeFilename('a/b.png'));
        $this->assertTrue($storage->isSafeFilename('logo_main_abcd1234.png'));
        $this->assertNull($storage->absolutePath(0, '../x.png'));
    }

    public function testStoresPngAndResolvesAbsolutePath(): void
    {
        $storage = new AssetStorage($this->tmpdir);
        $png = $this->pngBytes();
        $tmp = $this->tmpdir . '/upload.png';
        file_put_contents($tmp, $png);

        $result = $storage->store(0, 'logo_main', $this->upload('logo.png', $tmp, $png));

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $path = $storage->absolutePath(0, $result['filename']);
        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertSame(strlen($png), filesize($path));
    }

    public function testStoresPngFromExtensionlessTempUpload(): void
    {
        $this->assertFalse(
            \Document::isImage($this->writeBytes($this->tmpdir . '/phpAbC123', $this->pngBytes())),
            'GLPI Document::isImage must reject extensionless temps (the production bug)'
        );

        $storage = new AssetStorage($this->tmpdir);
        $png = $this->pngBytes();
        $tmp = $this->writeBytes($this->tmpdir . '/php' . bin2hex(random_bytes(3)), $png);

        $result = $storage->store(0, 'logo_main', $this->upload('distillect-logo.png', $tmp, $png));

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertMatchesRegularExpression('/^logo_main_[a-f0-9]+\.png$/', $result['filename']);
        $this->assertNotNull($storage->absolutePath(0, $result['filename']));
    }

    public function testStoresFaviconIcoFromExtensionlessTemp(): void
    {
        $storage = new AssetStorage($this->tmpdir);
        $ico = $this->icoBytes();
        $tmp = $this->writeBytes($this->tmpdir . '/phpFavicon', $ico);

        $result = $storage->store(0, 'favicon', $this->upload('favicon.ico', $tmp, $ico, 'image/x-icon'));

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertStringEndsWith('.ico', $result['filename']);
    }

    public function testRejectsIcoForNonFaviconSlot(): void
    {
        $storage = new AssetStorage($this->tmpdir);
        $ico = $this->icoBytes();
        $tmp = $this->writeBytes($this->tmpdir . '/phpIcoLogo', $ico);

        $result = $storage->store(0, 'logo_main', $this->upload('logo.ico', $tmp, $ico, 'image/x-icon'));

        $this->assertFalse($result['ok']);
    }

    public function testRejectsSvgEvenWhenNamedPng(): void
    {
        $storage = new AssetStorage($this->tmpdir);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>x</script></svg>';
        $tmp = $this->writeBytes($this->tmpdir . '/phpSvg', $svg);

        $as_svg = $storage->store(0, 'logo_main', $this->upload('logo.svg', $tmp, $svg, 'image/svg+xml'));
        $this->assertFalse($as_svg['ok']);
        $this->assertSame('SVG uploads are not supported', $as_svg['error']);

        $as_png = $storage->store(0, 'watermark_image', $this->upload('mark.png', $tmp, $svg));
        $this->assertFalse($as_png['ok']);
    }

    public function testRejectsPhpDisguisedAsPng(): void
    {
        $storage = new AssetStorage($this->tmpdir);
        $php = "<?php echo 'pwned';";
        $tmp = $this->writeBytes($this->tmpdir . '/phpShell', $php);

        $result = $storage->store(0, 'login_background_image', $this->upload('bg.png', $tmp, $php));
        $this->assertFalse($result['ok']);
    }

    public function testRejectsExtensionMismatch(): void
    {
        $storage = new AssetStorage($this->tmpdir);
        $png = $this->pngBytes();
        $tmp = $this->writeBytes($this->tmpdir . '/phpMismatch', $png);

        $result = $storage->store(0, 'logo_main', $this->upload('logo.jpg', $tmp, $png, 'image/jpeg'));
        $this->assertFalse($result['ok']);
        $this->assertSame('File contents do not match the image extension', $result['error']);
    }

    public function testRejectsOversizedFile(): void
    {
        $storage = new AssetStorage($this->tmpdir);
        $tmp = $this->tmpdir . '/big.png';
        file_put_contents($tmp, 'not-an-image-but-size-counts');

        $result = $storage->store(0, 'logo_main', [
            'name'     => 'logo.png',
            'type'     => 'image/png',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => AssetStorage::MAX_BYTES + 1,
        ]);

        $this->assertFalse($result['ok']);
    }

    /**
     * @return array<string, mixed>
     */
    private function upload(string $name, string $tmp, string $bytes, string $type = 'image/png'): array
    {
        return [
            'name'     => $name,
            'type'     => $type,
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($bytes),
        ];
    }

    private function pngBytes(): string
    {
        return (string) base64_decode(self::PNG, true);
    }

    private function icoBytes(): string
    {
        $png = $this->pngBytes();
        $header = pack('v3', 0, 1, 1);
        $entry = pack('C4v2V2', 1, 1, 0, 0, 1, 32, strlen($png), 6 + 16);

        return $header . $entry . $png;
    }

    private function writeBytes(string $path, string $bytes): string
    {
        file_put_contents($path, $bytes);

        return $path;
    }
}
