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

/**
 * Store branding images under GLPI_PLUGIN_DOC_DIR/brandkit/{entity}/.
 *
 * PHP stores uploads as extensionless temp files (e.g. /tmp/phpAbC123).
 * Do not call Document::isImage() on that path: it requires an image
 * extension on the file name itself and rejects every real upload.
 */
final class AssetStorage
{
    public const MAX_BYTES = 2097152; // 2 MiB

    /**
     * Raster types accepted for logos, backgrounds and watermarks.
     * SVG is excluded: a safe sanitiser is out of scope and unsanitised
     * SVG can carry script.
     *
     * @var list<string>
     */
    public const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    /**
     * Favicon may also be a Windows ICO.
     *
     * @var list<string>
     */
    public const FAVICON_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico'];

    public const IMAGE_SLOTS = [
        'logo_main',
        'logo_collapsed',
        'logo_login',
        'logo_login_dark',
        'favicon',
        'login_background_image',
        'app_background_image',
        'watermark_image',
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
        $ext = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
        $checked = $this->validateImageUpload($slot, $tmp, $ext);
        if ($checked['ok'] === false) {
            return $checked;
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

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    private function validateImageUpload(string $slot, string $path, string $ext): array
    {
        if ($ext === 'svg' || $ext === 'svgz') {
            return ['ok' => false, 'error' => 'SVG uploads are not supported'];
        }

        $allowed_ext = $slot === 'favicon' ? self::FAVICON_EXTENSIONS : self::IMAGE_EXTENSIONS;
        if (!in_array($ext, $allowed_ext, true)) {
            return [
                'ok'    => false,
                'error' => $slot === 'favicon'
                    ? 'Favicon must be png, jpg, gif, webp or ico'
                    : 'File is not a supported image (png, jpg, gif, webp)',
            ];
        }

        $kind = $this->detectImageKind($path);
        if ($kind === null) {
            return ['ok' => false, 'error' => 'File is not a supported image (png, jpg, gif, webp)'];
        }

        if ($kind === 'ico' && $slot !== 'favicon') {
            return ['ok' => false, 'error' => 'File is not a supported image (png, jpg, gif, webp)'];
        }

        if (!$this->extensionMatchesKind($ext, $kind)) {
            return ['ok' => false, 'error' => 'File contents do not match the image extension'];
        }

        return ['ok' => true];
    }

    /**
     * Identify a raster/ICO from file contents. Never uses the path extension
     * (PHP upload temps have none).
     *
     * @return 'png'|'jpeg'|'gif'|'webp'|'ico'|null
     */
    private function detectImageKind(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $size = filesize($path);
        if ($size === false || $size < 4) {
            return null;
        }

        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return null;
        }
        $header = (string) fread($fh, 16);
        fclose($fh);

        if (str_starts_with($header, "\x00\x00\x01\x00") && $size >= 6) {
            return 'ico';
        }
        if (str_starts_with($header, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }
        if (str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a')) {
            return 'gif';
        }
        if (str_starts_with($header, "\xff\xd8\xff")) {
            return 'jpeg';
        }
        if (strlen($header) >= 12 && str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP') {
            return 'webp';
        }

        if (function_exists('exif_imagetype') && $size >= 12) {
            $etype = @exif_imagetype($path);
            $from_exif = $this->kindFromExif($etype);
            if ($from_exif !== null) {
                return $from_exif;
            }
        }

        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            $from_mime = $this->kindFromMime(is_string($mime) ? $mime : '');
            if ($from_mime !== null) {
                return $from_mime;
            }
        }

        $info = @getimagesize($path);
        if (is_array($info) && isset($info[2])) {
            return $this->kindFromExif((int) $info[2]);
        }

        return null;
    }

    private function extensionMatchesKind(string $ext, string $kind): bool
    {
        return match ($ext) {
            'png' => $kind === 'png',
            'jpg', 'jpeg' => $kind === 'jpeg',
            'gif' => $kind === 'gif',
            'webp' => $kind === 'webp',
            'ico' => $kind === 'ico',
            default => false,
        };
    }

    /**
     * @return 'png'|'jpeg'|'gif'|'webp'|'ico'|null
     */
    private function kindFromExif(mixed $etype): ?string
    {
        if (!is_int($etype)) {
            return null;
        }

        return match ($etype) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_JPEG => 'jpeg',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_ICO => 'ico',
            default => null,
        };
    }

    /**
     * @return 'png'|'jpeg'|'gif'|'webp'|'ico'|null
     */
    private function kindFromMime(string $mime): ?string
    {
        return match (strtolower($mime)) {
            'image/png' => 'png',
            'image/jpeg' => 'jpeg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/x-icon', 'image/vnd.microsoft.icon', 'image/ico' => 'ico',
            default => null,
        };
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
