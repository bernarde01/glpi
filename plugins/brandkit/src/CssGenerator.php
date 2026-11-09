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
 * Build CSS variables that override GLPI 12's Tabler / Bootstrap palette
 * plus login, favicon-adjacent logo variables, and dashboard card colours.
 */
final class CssGenerator
{
    /**
     * @param array<string, mixed> $branding Resolved branding row
     * @param array<string, string|null> $urls Slot => public URL
     */
    public static function generate(array $branding, array $urls): string
    {
        $lines = [':root {'];

        $primary = CssSanitizer::normalizeHexColor($branding['color_primary'] ?? null);
        if ($primary !== null) {
            $rgb = CssSanitizer::hexToRgb($primary);
            $lines[] = sprintf('  --tblr-primary: %s !important;', $primary);
            if ($rgb !== null) {
                $lines[] = sprintf('  --tblr-primary-rgb: %d, %d, %d !important;', $rgb['r'], $rgb['g'], $rgb['b']);
            }
            $lines[] = sprintf('  --tblr-primary-fg: %s !important;', CssSanitizer::contrastColor($primary));
            $lines[] = sprintf('  --tblr-primary-darken: color-mix(in srgb, %s, black 10%%) !important;', $primary);
        }

        $secondary = CssSanitizer::normalizeHexColor($branding['color_secondary'] ?? null);
        if ($secondary !== null) {
            $lines[] = sprintf('  --tblr-secondary: %s !important;', $secondary);
            $lines[] = sprintf('  --tblr-secondary-fg: %s !important;', CssSanitizer::contrastColor($secondary));
            $lines[] = sprintf('  --tblr-muted: %s !important;', $secondary);
        }

        $accent = CssSanitizer::normalizeHexColor($branding['color_accent'] ?? null);
        if ($accent !== null) {
            $lines[] = sprintf('  --glpi-form-header-fg: %s !important;', $accent);
            $lines[] = sprintf('  --tblr-info: %s !important;', $accent);
            $rgb = CssSanitizer::hexToRgb($accent);
            if ($rgb !== null) {
                $lines[] = sprintf('  --tblr-link-color-rgb: %d, %d, %d !important;', $rgb['r'], $rgb['g'], $rgb['b']);
                $lines[] = sprintf('  --tblr-link-color: rgb(var(--tblr-link-color-rgb)) !important;');
            }
        }

        $sidebar = CssSanitizer::normalizeHexColor($branding['color_sidebar'] ?? null);
        if ($sidebar !== null) {
            $lines[] = sprintf('  --glpi-mainmenu-bg: %s !important;', $sidebar);
            $lines[] = sprintf('  --tblr-navbar-color: %s !important;', $sidebar);
            $lines[] = sprintf('  --glpi-mainmenu-fg: %s !important;', CssSanitizer::contrastColor($sidebar));
            $lines[] = sprintf('  --glpi-mainmenu-fg-muted: color-mix(in srgb, var(--glpi-mainmenu-fg), transparent 40%%) !important;');
        }

        $header = CssSanitizer::normalizeHexColor($branding['color_header'] ?? null);
        if ($header !== null) {
            $lines[] = sprintf('  --brandkit-header: %s !important;', $header);
            $lines[] = sprintf('  --brandkit-header-fg: %s !important;', CssSanitizer::contrastColor($header));
        }

        $card_bg = CssSanitizer::normalizeHexColor($branding['dashboard_card_bg'] ?? null);
        if ($card_bg !== null) {
            $lines[] = sprintf('  --brandkit-dash-card-bg: %s !important;', $card_bg);
        }
        $card_fg = CssSanitizer::normalizeHexColor($branding['dashboard_card_text'] ?? null);
        if ($card_fg !== null) {
            $lines[] = sprintf('  --brandkit-dash-card-text: %s !important;', $card_fg);
        }

        self::pushLogoVars($lines, $urls);

        $login_bg = CssSanitizer::normalizeHexColor($branding['login_background_color'] ?? null);
        if ($login_bg !== null) {
            $lines[] = sprintf('  --brandkit-login-bg: %s !important;', $login_bg);
        }

        $lines[] = '}';

        $extra = [];

        if ($header !== null) {
            $extra[] = <<<CSS
header.navbar.navbar-light,
header.navbar.topbar {
  background-color: var(--brandkit-header) !important;
  color: var(--brandkit-header-fg);
}
CSS;
        }

        if (!empty($urls['logo_login']) || !empty($urls['logo_login_dark'])) {
            $login_url = self::cssUrl((string) ($urls['logo_login'] ?: $urls['logo_login_dark']));
            $extra[] = sprintf(
                ".page-anonymous .glpi-logo, body.welcome-anonymous .glpi-logo {\n  --logo: url(\"%s\") !important;\n  content: url(\"%s\") !important;\n}",
                $login_url,
                $login_url
            );
        }

        if (!empty($urls['logo_main'])) {
            $main = self::cssUrl((string) $urls['logo_main']);
            $extra[] = sprintf(
                ".page .glpi-logo {\n  background-image: url(\"%s\") !important;\n  background-repeat: no-repeat !important;\n  background-size: contain !important;\n}",
                $main
            );
        }

        if (!empty($urls['login_background_image']) || $login_bg !== null) {
            $rules = [];
            if (!empty($urls['login_background_image'])) {
                $rules[] = sprintf(
                    '  background-image: url("%s");',
                    self::cssUrl((string) $urls['login_background_image'])
                );
                $rules[] = '  background-position: center;';
                $rules[] = '  background-size: cover;';
                $rules[] = '  background-repeat: no-repeat;';
                $rules[] = '  background-attachment: fixed;';
            }
            if ($login_bg !== null) {
                $rules[] = sprintf('  background-color: %s;', $login_bg);
            }
            $extra[] = "body.welcome-anonymous {\n" . implode("\n", $rules) . "\n}";
        }

        if ($card_bg !== null || $card_fg !== null) {
            $rules = [];
            if ($card_bg !== null) {
                $rules[] = '  background-color: var(--brandkit-dash-card-bg) !important;';
            }
            if ($card_fg !== null) {
                $rules[] = '  color: var(--brandkit-dash-card-text) !important;';
            }
            $extra[] = ".dashboard .grid-stack-item-content .card {\n" . implode("\n", $rules) . "\n}";
        }

        $custom = CssSanitizer::sanitize((string) ($branding['custom_css'] ?? ''));
        if ($custom !== '') {
            $extra[] = $custom;
        }

        $raw_style = trim((string) ($branding['style_json'] ?? ''));
        $style_css = '';
        if ($raw_style !== '') {
            $style_css = StyleCss::generate($branding, $urls, StyleSchema::fromRow($branding));
        }

        return implode("\n", $lines) . "\n\n" . implode("\n\n", $extra) . "\n\n" . $style_css;
    }

    /**
     * @param list<string> $lines
     * @param array<string, string|null> $urls
     */
    private static function pushLogoVars(array &$lines, array $urls): void
    {
        if (!empty($urls['logo_main'])) {
            $url = self::cssUrl((string) $urls['logo_main']);
            $lines[] = sprintf('  --glpi-logo: url("%s") !important;', $url);
            $lines[] = sprintf('  --glpi-logo-light: url("%s") !important;', $url);
            $lines[] = sprintf('  --glpi-logo-dark: url("%s") !important;', $url);
        }

        if (!empty($urls['logo_collapsed'])) {
            $url = self::cssUrl((string) $urls['logo_collapsed']);
            $lines[] = sprintf('  --glpi-logo-reduced: url("%s") !important;', $url);
            $lines[] = sprintf('  --glpi-logo-light-reduced: url("%s") !important;', $url);
            $lines[] = sprintf('  --glpi-logo-dark-reduced: url("%s") !important;', $url);
        } elseif (!empty($urls['logo_main'])) {
            $url = self::cssUrl((string) $urls['logo_main']);
            $lines[] = sprintf('  --glpi-logo-reduced: url("%s") !important;', $url);
        }

        if (!empty($urls['logo_login'])) {
            $url = self::cssUrl((string) $urls['logo_login']);
            $lines[] = sprintf('  --glpi-logo-dark-login: url("%s") !important;', $url);
            $lines[] = sprintf('  --glpi-logo-light-login: url("%s") !important;', $url);
        }

        if (!empty($urls['logo_login_dark'])) {
            $url = self::cssUrl((string) $urls['logo_login_dark']);
            $lines[] = sprintf('  --glpi-logo-light-login: url("%s") !important;', $url);
        }
    }

    private static function cssUrl(string $url): string
    {
        return str_replace(['"', "'", '(', ')', '\\'], '', $url);
    }
}
