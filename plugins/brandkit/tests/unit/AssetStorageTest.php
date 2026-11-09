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

use GlpiPlugin\Brandkit\AssetStorage;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/AssetStorage.php';

final class AssetStorageTest extends TestCase
{
    private string $tmpdir;

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
        $tmp = $this->tmpdir . '/upload.png';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        file_put_contents($tmp, $png);

        $result = $storage->store(0, 'logo_main', [
            'name'     => 'logo.png',
            'type'     => 'image/png',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($png),
        ]);

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $path = $storage->absolutePath(0, $result['filename']);
        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertSame(strlen($png), filesize($path));
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
}
