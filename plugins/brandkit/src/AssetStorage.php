<?php

/**
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

use Document;

/**
 * Store branding images under GLPI_PLUGIN_DOC_DIR/brandkit/{entity}/.
 */
final class AssetStorage
{
    public const MAX_BYTES = 2097152; // 2 MiB

    public const IMAGE_SLOTS = [
        'logo_main',
        'logo_collapsed',
        'logo_login',
        'logo_login_dark',
        'favicon',
        'login_background_image',
    ];

    private string $base_dir;

    public function __construct(?string $base_dir = null)
    {
        if ($base_dir !== null) {
            $this->base_dir = rtrim($base_dir, '/');
            return;
        }

        $root = defined('GLPI_PLUGIN_DOC_DIR') ? GLPI_PLUGIN_DOC_DIR : sys_get_temp_dir();
        $this->base_dir = rtrim($root, '/') . '/brandkit';
    }

    public function getBaseDir(): string
    {
        return $this->base_dir;
    }

    public static function isAllowedSlot(string $slot): bool
    {
        return in_array($slot, self::IMAGE_SLOTS, true);
    }

    /**
     * @param array<string, mixed> $file $_FILES element
     * @return array{ok: true, filename: string}|array{ok: false, error: string}
     */
    public function store(int $entities_id, string $slot, array $file): array
    {
        if (!self::isAllowedSlot($slot)) {
            return ['ok' => false, 'error' => 'Invalid image slot'];
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file uploaded'];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            return ['ok' => false, 'error' => 'Temporary upload missing'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            return ['ok' => false, 'error' => sprintf('Image must be between 1 byte and %d bytes', self::MAX_BYTES)];
        }

        $original = (string) ($file['name'] ?? 'upload');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if ($slot === 'favicon' && $ext === 'ico') {
            if (!$this->isIco($tmp)) {
                return ['ok' => false, 'error' => 'Favicon is not a valid ICO or image file'];
            }
        } elseif (!$this->isImage($tmp, $ext)) {
            return ['ok' => false, 'error' => 'File is not a supported image (png, jpg, gif, webp, bmp)'];
        }

        $safe_ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'png';
        $filename = sprintf('%s_%s.%s', $slot, bin2hex(random_bytes(8)), $safe_ext);

        $dir = $this->entityDirectory($entities_id);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'error' => 'Unable to create storage directory'];
        }

        $dest = $dir . '/' . $filename;
        if (!@move_uploaded_file($tmp, $dest) && !@rename($tmp, $dest) && !@copy($tmp, $dest)) {
            return ['ok' => false, 'error' => 'Unable to store uploaded file'];
        }

        @chmod($dest, 0644);

        return ['ok' => true, 'filename' => $filename];
    }

    public function delete(int $entities_id, string $filename): void
    {
        $path = $this->absolutePath($entities_id, $filename);
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    public function absolutePath(int $entities_id, string $filename): ?string
    {
        if (!$this->isSafeFilename($filename)) {
            return null;
        }

        $dir = realpath($this->entityDirectory($entities_id));
        if ($dir === false) {
            return null;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!is_file($path)) {
            return null;
        }

        $real = realpath($path);
        if ($real === false || !str_starts_with($real, $dir . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $real;
    }

    public function purgeEntity(int $entities_id): void
    {
        $dir = $this->entityDirectory($entities_id);
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    public function purgeAll(): void
    {
        if (!is_dir($this->base_dir)) {
            return;
        }

        $this->rrmdir($this->base_dir);
    }

    public function entityDirectory(int $entities_id): string
    {
        return $this->base_dir . '/' . $entities_id;
    }

    public function isSafeFilename(string $filename): bool
    {
        return $filename !== ''
            && $filename === basename($filename)
            && !str_contains($filename, '..')
            && (bool) preg_match('/^[A-Za-z0-9._-]+$/', $filename);
    }

    private function isImage(string $path, string $ext): bool
    {
        $allowed = ['bmp', 'gif', 'jpeg', 'jpg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return false;
        }

        if (class_exists(Document::class) && method_exists(Document::class, 'isImage')) {
            return Document::isImage($path);
        }

        $info = @getimagesize($path);

        return $info !== false;
    }

    private function isIco(string $path): bool
    {
        if (class_exists(Document::class) && Document::isImage($path)) {
            return true;
        }

        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return false;
        }
        $header = fread($fh, 4);
        fclose($fh);

        // ICO: reserved (0x0000) + type (0x0001)
        return $header === "\x00\x00\x01\x00";
    }

    private function rrmdir(string $dir): void
    {
        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
