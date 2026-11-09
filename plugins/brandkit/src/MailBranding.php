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

use QueuedNotification;

/**
 * Wrap queued HTML notification bodies with per-entity branding.
 */
final class MailBranding
{
    public static function onQueuedAdd(QueuedNotification $item): void
    {
        if (!is_array($item->input)) {
            return;
        }

        $html = (string) ($item->input['body_html'] ?? '');
        if (trim($html) === '') {
            return;
        }

        $entities_id = (int) ($item->input['entities_id'] ?? 0);
        $branding    = Branding::resolve($entities_id);
        $wrapped     = self::wrapHtml($html, $branding);
        if ($wrapped !== $html) {
            $item->input['body_html'] = $wrapped;
        }
    }

    /**
     * @param array<string, mixed> $branding
     */
    public static function wrapHtml(string $html, array $branding): string
    {
        $primary   = CssSanitizer::normalizeHexColor($branding['color_primary'] ?? null) ?? '#fe9c5c';
        $secondary = CssSanitizer::normalizeHexColor($branding['color_secondary'] ?? null) ?? '#2f3f64';
        $header_html = trim((string) ($branding['email_header_html'] ?? ''));
        $footer_html = trim((string) ($branding['email_footer_html'] ?? ''));
        $logo_url    = Branding::absoluteAssetUrlFromRow($branding, 'logo_main')
            ?? Branding::absoluteAssetUrlFromRow($branding, 'logo_login');

        $has_logo = $logo_url !== null && (int) ($branding['email_embed_logo'] ?? 1) === 1;
        if ($header_html === '' && $footer_html === '' && !$has_logo) {
            if (
                CssSanitizer::normalizeHexColor($branding['color_primary'] ?? null) === null
                && CssSanitizer::normalizeHexColor($branding['color_secondary'] ?? null) === null
            ) {
                return $html;
            }
        }

        $logo_block = '';
        if ($has_logo) {
            $safe = htmlescape($logo_url);
            $logo_block = sprintf(
                '<div style="padding:16px 24px;background:%s;text-align:left;"><img src="%s" alt="" style="max-height:48px;max-width:240px;height:auto;" /></div>',
                htmlescape($secondary),
                $safe
            );
        }

        $header_block = $header_html !== ''
            ? '<div style="padding:12px 24px;border-bottom:3px solid ' . htmlescape($primary) . ';">' . $header_html . '</div>'
            : '';

        $footer_block = $footer_html !== ''
            ? '<div style="padding:16px 24px;margin-top:16px;border-top:1px solid #e5e7eb;color:#4b5563;font-size:12px;">' . $footer_html . '</div>'
            : '';

        return '<div class="brandkit-mail" style="font-family:sans-serif;max-width:720px;margin:0 auto;">'
            . $logo_block
            . $header_block
            . '<div style="padding:16px 24px;">' . $html . '</div>'
            . $footer_block
            . '</div>';
    }
}
