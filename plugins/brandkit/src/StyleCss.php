<?php

/**
 * Generate CSS custom properties and stable-selector rules for the Style layer.
 *
 * Selectors (kept shallow so they survive GLPI 12 updates):
 *   :root, html[data-glpi-theme-dark="1"]
 *   body, body.welcome-anonymous, .page, .page-wrapper, #page
 *   .sidebar, .navbar.navbar-vertical, .topbar
 *   #navbar-menu .nav-link, #navbar-menu .nav-item, #navbar-menu .nav-item.active
 *   header.navbar, header.navbar.navbar-light, header.navbar.topbar
 *   .dropdown-menu, .dropdown-item
 *   .card, .modal-content, .modal-dialog
 *   .btn, .btn-primary
 *   .form-control, .form-select, .select2-selection
 *   .table, table.table
 *   .dashboard .grid-stack-item-content .card
 *   .kanban-column, .kanban-item, .kanban-add-form
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

final class StyleCss
{
    /**
     * @param array<string, mixed> $branding Resolved branding row
     * @param array<string, string|null> $urls Slot => public URL
     * @param array<string, mixed> $style Sanitised style
     */
    public static function generate(array $branding, array $urls, array $style): string
    {
        $blocks = [];
        $blocks[] = self::rootVariables($style, $branding);
        $blocks[] = self::shapeAndElevation($style);
        $blocks[] = self::densityAndType($style);
        $blocks[] = self::motion($style);
        $blocks[] = self::sidebar($style, $branding);
        $blocks[] = self::header($style, $branding);
        $blocks[] = self::dropdowns($style);
        $blocks[] = self::buttons($style, $branding);
        $blocks[] = self::pageBackground($style, $urls, $branding);
        $blocks[] = self::watermark($style, $urls);
        $blocks[] = self::surfaces($style);
        $blocks[] = self::reducedMotion();

        return trim(implode("\n\n", array_filter($blocks))) . "\n";
    }

    /**
     * @param array<string, mixed> $style
     * @param array<string, mixed> $branding
     */
    private static function rootVariables(array $style, array $branding): string
    {
        $radius = (int) $style['radius_global'];
        $radius_css = $radius === 999 ? '999px' : $radius . 'px';
        $r_card = self::px($style['radius_cards']);
        $r_btn = self::px($style['radius_buttons']);
        $r_in = self::px($style['radius_inputs']);
        $r_mod = self::px($style['radius_modals']);

        $lines = [':root {'];
        $lines[] = sprintf('  --tblr-border-radius: %s !important;', $radius_css);
        $lines[] = sprintf('  --tblr-border-radius-sm: %s !important;', self::px(max(0, (int) $style['radius_global'] === 999 ? 999 : (int) $style['radius_global'] - 2)));
        $lines[] = sprintf('  --tblr-border-radius-lg: %s !important;', self::px((int) $style['radius_global'] === 999 ? 999 : min(32, (int) $style['radius_global'] + 4)));
        $lines[] = sprintf('  --brandkit-radius: %s;', $radius_css);
        $lines[] = sprintf('  --brandkit-radius-lg: %s;', self::px((int) $style['radius_global'] === 999 ? 999 : min(32, (int) $style['radius_global'] + 4)));
        $lines[] = sprintf('  --brandkit-radius-card: %s;', $r_card);
        $lines[] = sprintf('  --brandkit-radius-btn: %s;', $r_btn);
        $lines[] = sprintf('  --brandkit-radius-input: %s;', $r_in);
        $lines[] = sprintf('  --brandkit-radius-modal: %s;', $r_mod);
        $lines[] = sprintf('  --brandkit-shadow-card: %s;', self::shadow((string) $style['shadow_cards']));
        $lines[] = sprintf('  --brandkit-shadow-dropdown: %s;', self::shadow((string) $style['shadow_dropdowns']));
        $lines[] = sprintf('  --brandkit-shadow-modal: %s;', self::shadow((string) $style['shadow_modals']));
        $lines[] = sprintf('  --brandkit-shadow-sidebar: %s;', self::shadow((string) $style['shadow_sidebar']));
        $lines[] = sprintf('  --tblr-box-shadow: %s !important;', self::shadow((string) $style['shadow_cards']));
        $lines[] = sprintf('  --tblr-box-shadow-sm: %s !important;', self::shadow('soft'));
        $lines[] = sprintf('  --tblr-box-shadow-lg: %s !important;', self::shadow((string) $style['shadow_modals']));
        $lines[] = sprintf('  --brandkit-duration: %s;', self::duration((string) $style['transition_speed'], (int) $style['disable_animations']));
        $lines[] = sprintf('  --brandkit-easing: %s;', self::easing((string) $style['transition_easing']));
        $lines[] = sprintf('  --brandkit-nav-icon: %dpx;', (int) $style['nav_icon_size']);
        $lines[] = sprintf('  --brandkit-nav-gap: %dpx;', (int) $style['nav_item_spacing']);
        $lines[] = sprintf('  --brandkit-font-size: %dpx;', (int) $style['font_size']);
        $lines[] = sprintf('  --tblr-body-font-size: %dpx !important;', (int) $style['font_size']);
        $lines[] = sprintf('  --tblr-font-sans-serif: %s !important;', self::fontStack($style));
        $lines[] = sprintf('  --brandkit-watermark-text: "%s";', self::cssString((string) $style['watermark_text']));
        $lines[] = sprintf('  --brandkit-watermark-opacity: %s;', number_format(((int) $style['watermark_opacity']) / 100, 2, '.', ''));
        $lines[] = sprintf('  --brandkit-page-overlay: %s;', number_format(((int) $style['page_bg_overlay']) / 100, 2, '.', ''));

        $density = self::densityMap((string) $style['density']);
        $lines[] = sprintf('  --brandkit-card-pad: %s;', $density['card']);
        $lines[] = sprintf('  --brandkit-table-pad: %s;', $density['table']);
        $lines[] = sprintf('  --brandkit-form-gap: %s;', $density['form']);
        $lines[] = sprintf('  --tblr-card-spacer-y: %s !important;', $density['card']);
        $lines[] = sprintf('  --tblr-card-spacer-x: %s !important;', $density['card']);

        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $style
     */
    private static function shapeAndElevation(array $style): string
    {
        return <<<CSS
.card,
.dashboard .grid-stack-item-content .card,
.kanban-column,
.kanban-item,
.kanban-add-form {
  border-radius: var(--brandkit-radius-card) !important;
  box-shadow: var(--brandkit-shadow-card);
}

.modal-content {
  border-radius: var(--brandkit-radius-modal) !important;
  box-shadow: var(--brandkit-shadow-modal);
}

.btn {
  border-radius: var(--brandkit-radius-btn) !important;
}

.form-control,
.form-select,
.select2-container .select2-selection {
  border-radius: var(--brandkit-radius-input) !important;
}
CSS;
    }

    /**
     * @param array<string, mixed> $style
     */
    private static function densityAndType(array $style): string
    {
        $family = self::fontStack($style);

        return <<<CSS
body,
.page,
.page-wrapper {
  font-family: {$family};
  font-size: var(--brandkit-font-size);
}

.table > :not(caption) > * > *,
table.table td,
table.table th {
  padding-block: var(--brandkit-table-pad);
}

.card-body,
.asset .card-body {
  padding: var(--brandkit-card-pad);
}

.form-field, .field-container, .row.mb-2, .row.mb-3 {
  --tblr-gutter-y: var(--brandkit-form-gap);
}
CSS;
    }

    /**
     * @param array<string, mixed> $style
     */
    private static function motion(array $style): string
    {
        if ((int) $style['disable_animations'] === 1) {
            return <<<CSS
html.brandkit-no-motion *,
html.brandkit-no-motion *::before,
html.brandkit-no-motion *::after {
  animation: none !important;
  transition: none !important;
  scroll-behavior: auto !important;
}
CSS;
        }

        $parts = [];
        $parts[] = <<<CSS
.card,
.btn,
#navbar-menu .nav-link,
.kanban-item,
.dashboard .grid-stack-item-content .card {
  transition: transform var(--brandkit-duration) var(--brandkit-easing),
              box-shadow var(--brandkit-duration) var(--brandkit-easing),
              background-color var(--brandkit-duration) var(--brandkit-easing),
              padding var(--brandkit-duration) var(--brandkit-easing),
              opacity var(--brandkit-duration) var(--brandkit-easing);
}
CSS;

        $parts[] = self::hoverRules('.card, .dashboard .grid-stack-item-content .card, .kanban-item', (string) $style['hover_cards']);
        $parts[] = self::hoverRules('.btn', (string) $style['hover_buttons']);
        $parts[] = self::hoverRules('#navbar-menu .nav-link', (string) $style['hover_menu']);

        if ((int) $style['fade_in'] === 1) {
            $parts[] = <<<CSS
@keyframes brandkit-fade-in {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: none; }
}
.page-wrapper .page,
.card,
.dashboard .grid-stack-item-content .card {
  animation: brandkit-fade-in var(--brandkit-duration) var(--brandkit-easing);
}
CSS;
        }

        return implode("\n\n", array_filter($parts));
    }

    private static function hoverRules(string $selector, string $kind): string
    {
        return match ($kind) {
            'lift' => "{$selector}:hover {\n  transform: translateY(-3px);\n}",
            'grow' => "{$selector}:hover {\n  transform: scale(1.03);\n}",
            'glow' => "{$selector}:hover {\n  box-shadow: " . self::shadow('glow') . ";\n}",
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $style
     * @param array<string, mixed> $branding
     */
    private static function sidebar(array $style, array $branding): string
    {
        $rules = [];
        $rules[] = <<<CSS
#navbar-menu .nav-link .ti,
#navbar-menu .nav-link i,
#navbar-menu .nav-link .fa {
  font-size: var(--brandkit-nav-icon) !important;
}
#navbar-menu .nav-item {
  margin-block: var(--brandkit-nav-gap);
}
.sidebar {
  box-shadow: var(--brandkit-shadow-sidebar);
}
CSS;

        $preset = (string) $style['sidebar_preset'];
        if ($preset === 'compact') {
            $rules[] = <<<CSS
#navbar-menu .nav-link {
  padding-block: 0.35rem !important;
}
.sidebar .navbar-brand {
  padding-block: 0.35rem;
}
CSS;
        } elseif ($preset === 'pill') {
            $rules[] = <<<CSS
#navbar-menu .nav-link {
  border-radius: 999px !important;
  margin-inline: 0.5rem;
  padding-inline: 0.9rem !important;
}
#navbar-menu .nav-item.active > .nav-link,
#navbar-menu .nav-link.active {
  background-color: color-mix(in srgb, var(--glpi-mainmenu-fg), transparent 88%) !important;
}
CSS;
        } elseif ($preset === 'floating') {
            $rules[] = <<<CSS
@media (min-width: 992px) {
  body.vertical-layout .sidebar {
    margin: 0.75rem;
    height: calc(100vh - 1.5rem);
    top: 0.75rem;
    border-radius: var(--brandkit-radius-lg, var(--brandkit-radius-card));
    overflow: hidden;
  }
}
CSS;
        } elseif ($preset === 'glass') {
            $rules[] = <<<CSS
.sidebar,
.navbar.navbar-vertical {
  background: color-mix(in srgb, var(--glpi-mainmenu-bg), transparent 22%) !important;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}
html[data-glpi-theme-dark="1"] .sidebar,
html[data-glpi-theme-dark="1"] .navbar.navbar-vertical {
  background: color-mix(in srgb, var(--glpi-mainmenu-bg), transparent 12%) !important;
}
CSS;
        }

        $rules[] = self::navItemStyle('#navbar-menu .nav-item.active > .nav-link, #navbar-menu .nav-link.active', (string) $style['nav_active_style']);
        $rules[] = self::navItemStyle('#navbar-menu .nav-item:not(.active) > .nav-link:hover', (string) $style['nav_hover_style']);

        $sidebar_g = $style['gradient_sidebar'];
        if (is_array($sidebar_g) && (int) ($sidebar_g['enabled'] ?? 0) === 1) {
            $grad = self::gradientCss($sidebar_g, $branding, 'sidebar');
            $rules[] = ".sidebar, .navbar.navbar-vertical {\n  background-image: {$grad} !important;\n  background-color: transparent !important;\n}";
        }

        return implode("\n\n", array_filter($rules));
    }

    private static function navItemStyle(string $selector, string $kind): string
    {
        return match ($kind) {
            'accent_bar' => "{$selector} {\n  box-shadow: inset 3px 0 0 var(--tblr-primary);\n  background-color: color-mix(in srgb, var(--glpi-mainmenu-fg), transparent 92%);\n}",
            'scale' => "{$selector} {\n  transform: scale(1.04);\n}",
            'indent' => "{$selector} {\n  padding-inline-start: 1.75rem !important;\n}",
            default => "{$selector} {\n  background-color: color-mix(in srgb, var(--glpi-mainmenu-fg), transparent 88%);\n}",
        };
    }

    /**
     * @param array<string, mixed> $style
     * @param array<string, mixed> $branding
     */
    private static function header(array $style, array $branding): string
    {
        $kind = (string) $style['header_style'];
        $header_g = is_array($style['gradient_header'] ?? null) ? $style['gradient_header'] : [];

        if ($kind === 'transparent') {
            return <<<CSS
header.navbar,
header.navbar.navbar-light,
header.navbar.topbar {
  background-color: color-mix(in srgb, var(--brandkit-header, var(--tblr-bg-surface)), transparent 35%) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: none;
}
CSS;
        }

        if ($kind === 'gradient' || (int) ($header_g['enabled'] ?? 0) === 1) {
            $grad = self::gradientCss($header_g, $branding, 'header');

            return <<<CSS
header.navbar,
header.navbar.navbar-light,
header.navbar.topbar {
  background-image: {$grad} !important;
  background-color: transparent !important;
  color: var(--brandkit-header-fg, var(--glpi-mainmenu-fg));
}
CSS;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $style
     */
    private static function dropdowns(array $style): string
    {
        $kind = (string) $style['dropdown_style'];
        $radius = $kind === 'rounded' ? 'var(--brandkit-radius-card)' : 'var(--brandkit-radius-input)';
        $glass = $kind === 'glass'
            ? "background: color-mix(in srgb, var(--tblr-bg-surface), transparent 12%);\n  backdrop-filter: blur(12px);\n  -webkit-backdrop-filter: blur(12px);"
            : '';

        return <<<CSS
.dropdown-menu,
.kanban-dropdown {
  border-radius: {$radius} !important;
  box-shadow: var(--brandkit-shadow-dropdown);
  {$glass}
}
.dropdown-item {
  border-radius: calc(var(--brandkit-radius-input) - 2px);
}
CSS;
    }

    /**
     * @param array<string, mixed> $style
     * @param array<string, mixed> $branding
     */
    private static function buttons(array $style, array $branding): string
    {
        $g = is_array($style['gradient_buttons'] ?? null) ? $style['gradient_buttons'] : [];
        if ((int) ($g['enabled'] ?? 0) !== 1) {
            return '';
        }
        $grad = self::gradientCss($g, $branding, 'buttons');

        return <<<CSS
.btn-primary {
  background-image: {$grad} !important;
  background-color: transparent !important;
  border-color: transparent !important;
}
CSS;
    }

    /**
     * @param array<string, mixed> $style
     * @param array<string, string|null> $urls
     * @param array<string, mixed> $branding
     */
    private static function pageBackground(array $style, array $urls, array $branding): string
    {
        $rules = [];
        $page_g = is_array($style['gradient_page'] ?? null) ? $style['gradient_page'] : [];
        $login_g = is_array($style['gradient_login'] ?? null) ? $style['gradient_login'] : [];

        if ((int) ($page_g['enabled'] ?? 0) === 1) {
            $grad = self::gradientCss($page_g, $branding, 'page');
            $rules[] = "body, .page, .page-wrapper {\n  background-image: {$grad};\n  background-attachment: fixed;\n}";
        }

        if (!empty($urls['app_background_image'])) {
            $url = self::cssUrl((string) $urls['app_background_image']);
            $fit = (string) $style['page_bg_fit'];
            $pos = str_replace('-', ' ', (string) $style['page_bg_position']);
            $repeat = $fit === 'repeat' ? 'repeat' : 'no-repeat';
            $size = $fit === 'repeat' ? 'auto' : $fit;
            $rules[] = <<<CSS
body, .page-wrapper {
  background-image: url("{$url}");
  background-size: {$size};
  background-position: {$pos};
  background-repeat: {$repeat};
  background-attachment: fixed;
}
CSS;
        }

        if ((int) $style['page_bg_overlay'] > 0) {
            $rules[] = <<<CSS
body.central .page-wrapper::before {
  content: "";
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  background: color-mix(in srgb, var(--tblr-bg-surface), transparent calc(100% - var(--brandkit-page-overlay) * 100%));
}
.page-wrapper > * {
  position: relative;
  z-index: 1;
}
CSS;
        }

        if ((int) ($login_g['enabled'] ?? 0) === 1) {
            $grad = self::gradientCss($login_g, $branding, 'login');
            $rules[] = "body.welcome-anonymous {\n  background-image: {$grad} !important;\n}";
        }

        return implode("\n\n", $rules);
    }

    /**
     * @param array<string, mixed> $style
     * @param array<string, string|null> $urls
     */
    private static function watermark(array $style, array $urls): string
    {
        $type = (string) $style['watermark_type'];
        if ($type === 'none') {
            return '';
        }

        $size = match ((string) $style['watermark_size']) {
            'sm' => '120px',
            'lg' => '420px',
            default => '240px',
        };
        $pos = str_replace('-', ' ', (string) $style['watermark_position']);
        $tile = (int) $style['watermark_tile'] === 1;
        $repeat = $tile ? 'repeat' : 'no-repeat';
        $scopes = [];
        if ((int) $style['watermark_app'] === 1) {
            $scopes[] = 'body.central .brandkit-watermark';
        }
        if ((int) $style['watermark_login'] === 1) {
            $scopes[] = 'body.welcome-anonymous .brandkit-watermark';
        }
        if ($scopes === []) {
            return '';
        }
        $sel = implode(', ', $scopes);

        $base = <<<CSS
.brandkit-watermark {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}
.page-wrapper, .page, #page, .navbar, .sidebar, .modal {
  position: relative;
  z-index: 1;
}
CSS;

        if ($type === 'text') {
            $font = match ((string) $style['watermark_size']) {
                'sm' => '2rem',
                'lg' => '6rem',
                default => '3.5rem',
            };

            return $base . "\n" . <<<CSS
{$sel}::after {
  content: var(--brandkit-watermark-text);
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: {$font};
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--tblr-body-color);
  opacity: var(--brandkit-watermark-opacity);
  transform: rotate(-18deg);
  white-space: pre;
}
CSS;
        }

        if ($type === 'image' && !empty($urls['watermark_image'])) {
            $url = self::cssUrl((string) $urls['watermark_image']);
            $bgsize = $tile ? $size : $size;

            return $base . "\n" . <<<CSS
{$sel} {
  background-image: url("{$url}");
  background-repeat: {$repeat};
  background-position: {$pos};
  background-size: {$bgsize};
  opacity: var(--brandkit-watermark-opacity);
}
CSS;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $style
     */
    private static function surfaces(array $style): string
    {
        return <<<CSS
.table,
.kanban-column {
  border-radius: var(--brandkit-radius-card);
}
.kanban-item {
  transition: transform var(--brandkit-duration) var(--brandkit-easing),
              box-shadow var(--brandkit-duration) var(--brandkit-easing);
}
CSS;
    }

    private static function reducedMotion(): string
    {
        return <<<CSS
@media (prefers-reduced-motion: reduce) {
  :root {
    --brandkit-duration: 0s !important;
  }
  *, *::before, *::after {
    animation: none !important;
    transition: none !important;
    scroll-behavior: auto !important;
  }
}
CSS;
    }

    /**
     * @param array<string, mixed> $gradient
     * @param array<string, mixed> $branding
     */
    private static function gradientCss(array $gradient, array $branding, string $slot): string
    {
        $angle = (int) ($gradient['angle'] ?? 135);
        $s1 = (string) ($gradient['stop1'] ?? '');
        $s2 = (string) ($gradient['stop2'] ?? '');
        $s3 = (string) ($gradient['stop3'] ?? '');
        if ($s1 === '') {
            $s1 = CssSanitizer::normalizeHexColor($branding['color_primary'] ?? null) ?? 'var(--tblr-primary)';
        }
        if ($s2 === '') {
            $s2 = CssSanitizer::normalizeHexColor($branding['color_secondary'] ?? null)
                ?? CssSanitizer::normalizeHexColor($branding['color_sidebar'] ?? null)
                ?? 'var(--tblr-secondary)';
        }
        if ($slot === 'header' && $s2 === $s1) {
            $s2 = CssSanitizer::normalizeHexColor($branding['color_accent'] ?? null) ?? $s2;
        }
        if ($s3 !== '') {
            return sprintf('linear-gradient(%ddeg, %s, %s, %s)', $angle, $s1, $s2, $s3);
        }

        return sprintf('linear-gradient(%ddeg, %s, %s)', $angle, $s1, $s2);
    }

    private static function shadow(string $kind): string
    {
        return match ($kind) {
            'none' => 'none',
            'medium' => '0 8px 24px color-mix(in srgb, var(--tblr-body-color), transparent 88%)',
            'strong' => '0 18px 40px color-mix(in srgb, var(--tblr-body-color), transparent 82%)',
            'glow' => '0 0 0 1px color-mix(in srgb, var(--tblr-primary), transparent 70%), 0 10px 28px color-mix(in srgb, var(--tblr-primary), transparent 55%)',
            default => '0 1px 3px color-mix(in srgb, var(--tblr-body-color), transparent 90%), 0 1px 2px color-mix(in srgb, var(--tblr-body-color), transparent 92%)',
        };
    }

    private static function duration(string $speed, int $disabled): string
    {
        if ($disabled === 1) {
            return '0s';
        }

        return match ($speed) {
            'instant' => '0s',
            'fast' => '120ms',
            'slow' => '400ms',
            default => '220ms',
        };
    }

    private static function easing(string $name): string
    {
        return match ($name) {
            'ease' => 'ease',
            'linear' => 'linear',
            'smooth' => 'cubic-bezier(0.22, 1, 0.36, 1)',
            default => 'ease-in-out',
        };
    }

    /**
     * @param array<string, mixed> $style
     * @return array{card: string, table: string, form: string}
     */
    private static function densityMap(string $density): array
    {
        return match ($density) {
            'compact' => ['card' => '0.6rem', 'table' => '0.35rem', 'form' => '0.4rem'],
            'spacious' => ['card' => '1.5rem', 'table' => '0.9rem', 'form' => '1.1rem'],
            default => ['card' => '1rem', 'table' => '0.65rem', 'form' => '0.75rem'],
        };
    }

    /**
     * @param array<string, mixed> $style
     */
    private static function fontStack(array $style): string
    {
        $custom = trim((string) ($style['font_custom_name'] ?? ''));
        if ((string) $style['font_family'] === 'custom' && $custom !== '') {
            return sprintf('"%s", system-ui, sans-serif', str_replace('"', '', $custom));
        }

        return match ((string) $style['font_family']) {
            'inter' => '"Inter", system-ui, sans-serif',
            'nunito' => '"Nunito", system-ui, sans-serif',
            'ibm_plex' => '"IBM Plex Sans", system-ui, sans-serif',
            'source_sans' => '"Source Sans 3", system-ui, sans-serif',
            default => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        };
    }

    private static function px(int $n): string
    {
        return $n === 999 ? '999px' : $n . 'px';
    }

    private static function cssString(string $text): string
    {
        return str_replace(['\\', '"'], ['', ''], $text);
    }

    private static function cssUrl(string $url): string
    {
        return str_replace(['"', "'", '(', ')', '\\'], '', $url);
    }
}
