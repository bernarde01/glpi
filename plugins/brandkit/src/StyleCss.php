<?php

/**
 * Generate CSS custom properties and stable-selector rules for the Style layer.
 *
 * Selectors (kept shallow so they survive GLPI 12 updates):
 *   :root, html[data-glpi-theme-dark="1"]
 *   body, body.welcome-anonymous, .page, .page-wrapper, #page
 *   .sidebar, .navbar.navbar-vertical, .topbar
 *   #navbar-menu .nav-link, #navbar-menu .nav-item, #navbar-menu .nav-item.active
 *   .sidebar #navbar-menu .dropdown-menu, .sidebar #navbar-menu .dropdown-item
 *   header.navbar, header.navbar.navbar-light, header.navbar.topbar
 *   #navigationheader.navigationheader, #navigationheader .main-header
 *   .dropdown-menu, .dropdown-item
 *   .card, .modal-content, .modal-dialog
 *   .btn, .btn-primary, .btn-sm
 *   .form-control, .form-select, .select2-selection, .input-group
 *   input[type=file], ::file-selector-button
 *   header.navbar .breadcrumb, header.navbar .nav.navbar-nav
 *   .card-tabs #tabspanel.nav-tabs
 *   .badge, .pagination, .dropdown-menu
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
        $blocks[] = BundledFonts::fontFaceCss($style);
        $blocks[] = self::rootVariables($style, $branding);
        $blocks[] = self::shapeAndElevation($style);
        $blocks[] = self::chrome($style);
        $blocks[] = self::tabs($style);
        $blocks[] = self::forms($style);
        $blocks[] = self::densityAndType($style);
        $blocks[] = self::motion($style);
        $blocks[] = self::sidebar($style, $branding);
        $blocks[] = self::header($style, $branding);
        $blocks[] = self::dropdowns($style);
        $blocks[] = self::buttons($style, $branding);
        $blocks[] = self::pageBackground($style, $urls, $branding);
        $blocks[] = self::watermark($style, $urls);
        $blocks[] = self::surfaces($style);
        $blocks[] = self::itil($style);
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
        $r_card = self::px((int) $style['radius_cards']);
        $r_btn = self::px((int) $style['radius_buttons']);
        $r_in = self::resolveInputRadiusPx($style, $radius);
        $r_mod = self::px((int) $style['radius_modals']);
        $r_chrome = self::chromePx($radius);
        $btn_pad = ((int) $style['radius_buttons'] === 999) ? '1.15rem' : '0.85rem';

        $lines = [':root {'];
        // Cap Tabler's global radius so breadcrumbs, badges and grouped
        // controls do not inherit pill / 24px corners.
        $lines[] = sprintf('  --tblr-border-radius: %s !important;', $r_chrome);
        $lines[] = sprintf('  --tblr-border-radius-sm: %s !important;', self::chromePx($radius, 6));
        $lines[] = sprintf('  --tblr-border-radius-lg: %s !important;', self::chromePx((int) $style['radius_cards'], 16));
        $lines[] = sprintf('  --brandkit-radius: %s;', $radius_css);
        $lines[] = sprintf('  --brandkit-radius-lg: %s;', self::px((int) $style['radius_global'] === 999 ? 16 : min(32, max(0, $radius + 4))));
        $lines[] = sprintf('  --brandkit-radius-card: %s;', $r_card);
        $lines[] = sprintf('  --brandkit-radius-btn: %s;', $r_btn);
        $lines[] = sprintf('  --brandkit-radius-input: %s;', $r_in);
        $lines[] = sprintf('  --brandkit-radius-modal: %s;', $r_mod);
        $lines[] = sprintf('  --brandkit-radius-chrome: %s;', $r_chrome);
        $lines[] = sprintf('  --brandkit-btn-pad: %s;', $btn_pad);
        $lines[] = sprintf('  --brandkit-tab-gap: %dpx;', (int) $style['tab_item_spacing']);
        $heading = 'Configuration';
        if (function_exists('__')) {
            $heading = (string) __('Configuration', 'brandkit');
        }
        $lines[] = sprintf('  --brandkit-tab-heading: "%s";', self::cssString($heading));
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

        if (self::densityOverridesActive((string) $style['density'])) {
            $density = self::densityMap((string) $style['density']);
            $lines[] = sprintf('  --brandkit-card-pad: %s;', $density['card']);
            $lines[] = sprintf('  --brandkit-table-pad: %s;', $density['table']);
            $lines[] = sprintf('  --brandkit-form-gap: %s;', $density['form']);
            $lines[] = sprintf('  --tblr-card-spacer-y: %s !important;', $density['card']);
            $lines[] = sprintf('  --tblr-card-spacer-x: %s !important;', $density['card']);
        }

        if (self::itilChromeActive($style)) {
            // Footer chrome only — keep GLPI default --glpi-timeline-* tints (green / grey / amber / blue).
            $lines[] = '  --glpi-itil-secondary-bg: #eef1f6;';
            $lines[] = '  --brandkit-shadow-timeline: 0 2px 4px color-mix(in srgb, var(--tblr-body-color), transparent 80%),'
                . ' 0 6px 18px color-mix(in srgb, var(--tblr-body-color), transparent 88%);';
        }

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
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  line-height: 1.2;
}

.btn:not(.btn-link):not(.btn-icon):not(.btn-sm):not(.btn-close) {
  padding-block: 0.4rem;
  padding-inline: var(--brandkit-btn-pad, 0.85rem);
  min-height: 2.2rem;
}

.btn-sm {
  min-height: 1.75rem;
  padding-block: 0.2rem;
  padding-inline: 0.7rem;
  border-radius: var(--brandkit-radius-chrome) !important;
  gap: 0.25rem;
}

.btn-icon,
.btn.btn-icon {
  padding-inline: 0.5rem;
}

.form-control,
.form-select,
.select2-container .select2-selection {
  border-radius: var(--brandkit-radius-input) !important;
  min-height: 2.2rem;
}

.form-control,
.form-select,
.select2-container .select2-selection--multiple {
  padding-block: 0.4rem;
  padding-inline: 0.75rem;
}

/* Select2 single centers its value with line-height equal to the control
   height. Padding on the selection box shifts that line downward. */
.select2-container .select2-selection--single {
  padding: 0;
  display: flex;
  align-items: center;
}
.select2-container .select2-selection--single .select2-selection__rendered {
  line-height: normal !important;
  margin: 0;
}
.select2-container .select2-selection--single .select2-selection__arrow {
  height: 100% !important;
  top: 0 !important;
}

/* Detach list from the field so all four corners can round (native GLPI flushes top). */
.select2-dropdown {
  border-radius: var(--brandkit-radius-input) !important;
  border: 1px solid color-mix(in srgb, var(--tblr-border-color), transparent 8%) !important;
  background: var(--tblr-bg-surface, #fff);
  box-shadow: var(--brandkit-shadow-dropdown);
  overflow: hidden;
}

.select2-dropdown.select2-dropdown--below {
  margin-top: 0.3rem;
  border-top: 1px solid color-mix(in srgb, var(--tblr-border-color), transparent 8%) !important;
  border-top-left-radius: var(--brandkit-radius-input) !important;
  border-top-right-radius: var(--brandkit-radius-input) !important;
  border-bottom-left-radius: var(--brandkit-radius-input) !important;
  border-bottom-right-radius: var(--brandkit-radius-input) !important;
}

.select2-dropdown.select2-dropdown--above {
  margin-bottom: 0.3rem;
  border-bottom: 1px solid color-mix(in srgb, var(--tblr-border-color), transparent 8%) !important;
  border-top-left-radius: var(--brandkit-radius-input) !important;
  border-top-right-radius: var(--brandkit-radius-input) !important;
  border-bottom-left-radius: var(--brandkit-radius-input) !important;
  border-bottom-right-radius: var(--brandkit-radius-input) !important;
}

.select2-container.select2-container--open .select2-selection {
  border-radius: var(--brandkit-radius-input) !important;
}

/* Search criteria row: value text input must match Select2 / select height. */
.search-form-container .search-form .normalcriteria input.form-control[type="text"],
.search-form-container .sort-container input.form-control[type="text"] {
  height: 2.2rem;
  min-height: 2.2rem;
  padding-block: 0.35rem;
  line-height: 1.25;
  box-sizing: border-box;
}

.search-form-container .search-form .select2-container .select2-selection--single,
.search-form-container .sort-container .select2-container .select2-selection--single {
  height: 2.2rem;
  min-height: 2.2rem;
}
CSS;
    }

    /**
     * Breadcrumb, search, file inputs, input-groups, badges, pagination.
     * Large template radii are scaled down via --brandkit-radius-chrome.
     *
     * @param array<string, mixed> $style
     */
    private static function chrome(array $style): string
    {
        $base = <<<CSS
.page > header,
header.navbar,
.navbar.secondary-bar {
  border-radius: 0 !important;
  overflow: visible;
}
.page > header .header-container,
header.navbar .header-container,
.navbar.secondary-bar .container-fluid {
  align-items: center;
}
.page > header .breadcrumb,
header.navbar .breadcrumb,
.navbar.secondary-bar .breadcrumb {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 0;
  margin: 0;
  padding: 0.15rem 0;
  background: transparent !important;
  font-size: 0.9rem;
}
.page > header .breadcrumb-item,
header.navbar .breadcrumb-item,
.navbar.secondary-bar .breadcrumb-item {
  display: inline-flex;
  align-items: center;
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  border-radius: 0 !important;
  padding: 0;
  max-width: 14rem;
}
.page > header .breadcrumb-item + .breadcrumb-item::before,
header.navbar .breadcrumb-item + .breadcrumb-item::before,
.navbar.secondary-bar .breadcrumb-item + .breadcrumb-item::before {
  display: inline-flex;
  align-items: center;
  float: none !important;
  padding: 0 0.45rem;
  transform: none;
  content: var(--tblr-breadcrumb-divider, "/");
  opacity: 0.7;
  line-height: 1;
}
.page > header .breadcrumb-item a,
header.navbar .breadcrumb-item a,
header.navbar .breadcrumb-item a.d-inline-flex,
.navbar.secondary-bar .breadcrumb-item a {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  line-height: 1.2;
  padding: 0.15rem 0.1rem;
  border: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  border-radius: 0 !important;
  background: transparent !important;
  color: color-mix(in srgb, var(--brandkit-header-fg, #fff), transparent 8%) !important;
}
.page > header .nav.navbar-nav,
header.navbar .nav.navbar-nav,
.navbar.secondary-bar .nav.navbar-nav {
  align-items: center;
  align-self: center;
  margin-inline-start: 0.35rem;
  padding-inline-start: 0.5rem;
}
.page > header .nav.navbar-nav .btn,
header.navbar .nav.navbar-nav .btn,
.navbar.secondary-bar .nav.navbar-nav .btn {
  border-radius: 999px !important;
  min-height: 2rem;
  padding: 0.3rem 0.95rem !important;
  font-weight: 600;
  gap: 0.3rem;
}
header.navbar .nav.navbar-nav .btn.btn-primary .d-none.d-xxl-block {
  display: inline !important;
}

/* Form split rows (e.g. ITIL Items): rounded controls with a gap — not one fused bar. */
.input-group:not(.input-group-flat) {
  align-items: stretch;
  flex-wrap: wrap;
  gap: 0.35rem;
  background: transparent;
  border-radius: 0;
}

.input-group:not(.input-group-flat) > .input-group-text,
.input-group:not(.input-group-flat) > .form-control,
.input-group:not(.input-group-flat) > .form-select,
.input-group:not(.input-group-flat) > .form-control:focus,
.input-group:not(.input-group-flat) > .btn,
.input-group:not(.input-group-flat) > .select2-container,
.input-group:not(.input-group-flat) > .select2-container .select2-selection {
  border-radius: var(--brandkit-radius-input) !important;
}

.input-group:not(.input-group-flat) > .input-group-text,
.input-group:not(.input-group-flat) > .btn {
  flex: 0 0 auto;
  flex-shrink: 0;
  width: auto;
}

.input-group:not(.input-group-flat) > .form-select:not(:last-child),
.input-group:not(.input-group-flat) > .form-select:not(:only-of-type) {
  flex: 0 0 auto !important;
  flex-shrink: 0 !important;
  width: auto !important;
  max-width: 100%;
}

/* Lone select after a label: grow on the row, wrap before squashing. */
.input-group:not(.input-group-flat) > .form-select:only-of-type:last-child {
  flex: 1 1 12rem;
  flex-shrink: 0;
  min-width: min(100%, 12rem);
  max-width: 100%;
}

/* Ajax field (label + select2 only, e.g. “My devices”): grow / wrap, never squash the label. */
.input-group:not(.input-group-flat) > .form-control,
.input-group:not(.input-group-flat) > .select2-container {
  flex: 1 1 0;
  flex-shrink: 0;
  min-width: min(100%, 14rem);
  max-width: 100%;
}

.input-group:not(.input-group-flat) > .select2-container {
  display: block;
  width: auto;
}

.input-group:not(.input-group-flat) > .select2-container + span,
.input-group:not(.input-group-flat) > span.ms-1 {
  flex: 0 0 auto;
  flex-shrink: 0;
}

/* Label + item type + ajax results: keep type on row 1; device search always row 2 (wide panels otherwise share one row and Select2 ellipsizes). */
.input-group:not(.input-group-flat) > .form-select ~ .d-flex {
  flex: 1 1 100%;
  flex-shrink: 0;
  min-width: 0;
  max-width: 100%;
  align-items: stretch;
}

.input-group:not(.input-group-flat) > .form-select ~ .d-flex .select2-container {
  display: block;
  flex: 1 1 0;
  width: 100% !important;
  min-width: 0;
  max-width: 100%;
}

.input-group .input-group-text .btn,
.input-group-flat .input-group-text .btn {
  border-radius: 0 !important;
  min-height: auto;
}

/* Header search: single connected control */
.input-group.input-group-flat {
  align-items: stretch;
  border-radius: var(--brandkit-radius-chrome);
  gap: 0;
}

.input-group.input-group-flat > .form-control,
.input-group.input-group-flat > .form-select,
.input-group.input-group-flat > .form-control:focus,
.input-group.input-group-flat > .btn,
.input-group.input-group-flat > .input-group-text,
.input-group.input-group-flat > .select2-container .select2-selection {
  border-radius: 0 !important;
}

.input-group.input-group-flat > :first-child,
.input-group.input-group-flat > :first-child .select2-selection {
  border-top-left-radius: var(--brandkit-radius-chrome) !important;
  border-bottom-left-radius: var(--brandkit-radius-chrome) !important;
}

.input-group.input-group-flat > :last-child,
.input-group.input-group-flat > :last-child .select2-selection {
  border-top-right-radius: var(--brandkit-radius-chrome) !important;
  border-bottom-right-radius: var(--brandkit-radius-chrome) !important;
}

header.navbar .input-group-flat,
.navbar.secondary-bar .input-group-flat,
.page > header .input-group-flat {
  overflow: hidden;
  border-radius: var(--brandkit-radius-chrome);
  min-height: 2.2rem;
}
header.navbar .input-group-flat > .form-control,
.navbar.secondary-bar .input-group-flat > .form-control,
.page > header .input-group-flat > .form-control {
  min-height: 2.2rem;
  padding-inline: 0.85rem;
}
header.navbar .input-group-flat .btn-link,
.navbar.secondary-bar .input-group-flat .btn-link,
.page > header .input-group-flat .btn-link {
  padding: 0.45rem 0.75rem !important;
  min-height: 2.2rem;
  min-width: 2.4rem;
  border-radius: 0 !important;
}

.btn-group > .btn {
  border-radius: 0 !important;
}
.btn-group > .btn:first-child {
  border-top-left-radius: var(--brandkit-radius-chrome) !important;
  border-bottom-left-radius: var(--brandkit-radius-chrome) !important;
}
.btn-group > .btn:last-child {
  border-top-right-radius: var(--brandkit-radius-chrome) !important;
  border-bottom-right-radius: var(--brandkit-radius-chrome) !important;
}

input[type="file"].form-control,
.form-control[type="file"] {
  padding: 0;
  padding-inline-end: 0.75rem;
  min-height: 2.35rem;
  line-height: 1.4;
  border-radius: var(--brandkit-radius-input) !important;
  overflow: hidden;
  display: inline-flex;
  align-items: stretch;
  align-self: center;
  max-width: 100%;
  box-sizing: border-box;
  vertical-align: middle;
}
input[type="file"]::file-selector-button {
  margin: 0;
  margin-inline-end: 0.65rem;
  padding: 0.45rem 0.9rem;
  min-height: 2.35rem;
  border: 0;
  border-inline-end: 1px solid var(--tblr-border-color);
  border-radius: 0;
  background: color-mix(in srgb, var(--tblr-primary), transparent 82%);
  color: inherit;
  font-weight: 600;
  cursor: pointer;
  box-sizing: border-box;
}
.fileupload:not(.only-uploaded-files) input[type="file"].form-control,
.fileupload:not(.only-uploaded-files) .form-control[type="file"] {
  width: auto;
  max-width: 100%;
  margin-inline: auto;
}

.badge {
  border-radius: var(--brandkit-radius-chrome) !important;
  padding-inline: 0.55em;
}
.pagination .page-link {
  min-width: 2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.pagination .page-item:first-child .page-link {
  border-top-left-radius: var(--brandkit-radius-chrome) !important;
  border-bottom-left-radius: var(--brandkit-radius-chrome) !important;
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}
.pagination .page-item:last-child .page-link {
  border-top-right-radius: var(--brandkit-radius-chrome) !important;
  border-bottom-right-radius: var(--brandkit-radius-chrome) !important;
  border-top-left-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
}
.pagination .page-item:not(:first-child):not(:last-child) .page-link {
  border-radius: 0 !important;
}

header.navbar,
header.navbar.navbar-light,
header.navbar.topbar {
  color: var(--brandkit-header-fg, inherit);
}
header.navbar .breadcrumb,
header.navbar .breadcrumb-item,
header.navbar .breadcrumb-item a,
header.navbar .nav-link,
header.navbar .form-control,
header.navbar .form-control::placeholder {
  color: color-mix(in srgb, var(--brandkit-header-fg, currentColor), transparent 8%);
}
header.navbar .form-control {
  background: transparent;
}

#navigationheader.navigationheader {
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
  position: relative !important;
  z-index: 2;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.35rem 0.75rem;
  min-height: 2.75rem;
  margin: 0 0 0.85rem !important;
  padding: 0.15rem 0.15rem 0.35rem;
  overflow: visible !important;
  color: var(--tblr-body-color) !important;
  background: transparent !important;
}
#navigationheader .main-header,
#navigationheader .card-header,
#navigationheader .mt-n2 {
  margin-top: 0 !important;
  background: transparent !important;
  color: inherit !important;
  overflow: visible !important;
}
#navigationheader .card-title,
#navigationheader .navigationheader-title,
#navigationheader .status,
#navigationheader .badge.entity-name {
  color: var(--tblr-body-color) !important;
}
#navigationheader .pagination-left,
#navigationheader .pagination-right {
  display: flex !important;
  align-items: center;
}
body.central .page-wrapper {
  overflow: visible;
}
body.central .page-body {
  padding-top: 0.85rem;
}
body.central .tab-content.card {
  overflow: visible !important;
}
CSS;
        $extra = '';
        $search = (string) ($style['search_style'] ?? 'default');
        if ($search === 'inset_dark') {
            $extra .= <<<CSS

header.navbar form[role="search"] .input-group-flat,
header.navbar .input-group-flat,
.page > header .input-group-flat,
.navbar.secondary-bar .input-group-flat {
  background: color-mix(in srgb, #000, transparent 42%) !important;
  border: 0 !important;
  box-shadow: inset 0 0 0 1px color-mix(in srgb, #fff, transparent 84%);
  border-radius: 999px !important;
  overflow: hidden;
  min-height: 2.2rem;
}
header.navbar form[role="search"] .input-group-flat > .form-control,
header.navbar .input-group-flat > .form-control,
header.navbar form[role="search"] .input-group-flat .btn,
header.navbar form[role="search"] .input-group-flat .btn-link,
header.navbar .input-group-flat .btn,
header.navbar .input-group-flat .btn-link,
header.navbar .input-group-flat .input-group-text {
  color: #fff !important;
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  --tblr-btn-color: #fff;
  --tblr-link-color: #fff;
}
header.navbar .input-group-flat > .form-control::placeholder {
  color: color-mix(in srgb, #fff, transparent 40%);
}
header.navbar .input-group-flat .btn-link .ti,
header.navbar .input-group-flat .btn .ti {
  color: #fff !important;
}
CSS;
        } elseif ($search === 'inset_light') {
            $extra .= <<<CSS

header.navbar .input-group-flat,
.page > header .input-group-flat,
.navbar.secondary-bar .input-group-flat {
  background: #fff !important;
  border: 0 !important;
  box-shadow: 0 1px 2px color-mix(in srgb, #000, transparent 86%);
  border-radius: 999px;
  overflow: hidden;
}
header.navbar .input-group-flat > .form-control,
header.navbar .input-group-flat .btn-link {
  color: #0f172a !important;
  background: transparent !important;
}
header.navbar .input-group-flat > .form-control::placeholder {
  color: #64748b;
}
CSS;
        }

        $crumb = (string) ($style['breadcrumb_style'] ?? 'default');
        if ($crumb === 'pill') {
            $extra .= <<<CSS

header.navbar .breadcrumb-item a {
  background: color-mix(in srgb, #fff, transparent 82%) !important;
  border-radius: 999px !important;
  padding: 0.2rem 0.7rem !important;
}
header.navbar .nav.navbar-nav .btn {
  border-radius: 999px !important;
}
CSS;
        } elseif ($crumb === 'ghost') {
            $extra .= <<<CSS

header.navbar .breadcrumb,
header.navbar .breadcrumb-item,
header.navbar .breadcrumb-item a,
header.navbar .breadcrumb-item a.d-inline-flex {
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  border-radius: 0 !important;
  font-weight: 500;
}
header.navbar .nav.navbar-nav .btn.btn-primary {
  border-radius: 999px !important;
  background: color-mix(in srgb, var(--tblr-primary), #5eead4 28%) !important;
  color: #fff !important;
  border: 0 !important;
  padding: 0.3rem 0.95rem !important;
  min-height: 2rem !important;
  box-shadow: none !important;
}
header.navbar .nav.navbar-nav .btn.btn-ghost-secondary {
  background: transparent !important;
  color: color-mix(in srgb, var(--brandkit-header-fg, #fff), transparent 8%) !important;
  box-shadow: none !important;
  padding: 0.2rem 0.5rem !important;
  min-height: 1.85rem !important;
  border-radius: var(--brandkit-radius-chrome) !important;
}
header.navbar .nav.navbar-nav .btn.btn-primary .d-none.d-xxl-block {
  display: inline !important;
}
header.navbar form[role="search"] {
  min-width: 11rem;
  flex: 0 1 16rem;
}
CSS;
        }

        if ((int) ($style['header_user_chip'] ?? 0) === 1) {
            $extra .= <<<CSS

header.navbar .user-menu-dropdown-toggle {
  display: inline-flex !important;
  align-items: center;
  gap: 0.55rem;
  background: color-mix(in srgb, #fff, transparent 88%) !important;
  border-radius: 999px !important;
  padding: 0.2rem 0.35rem 0.2rem 0.7rem !important;
  border: 0 !important;
  overflow: visible !important;
  min-height: 2.5rem;
  line-height: 1.15;
}
header.navbar .user-menu-dropdown-toggle .pe-2.d-none.d-xl-block,
header.navbar .user-menu-dropdown-toggle .d-none.d-xl-block {
  display: block !important;
  text-align: end;
}
header.navbar .user-menu-dropdown-toggle .text-muted-menu {
  color: color-mix(in srgb, var(--brandkit-header-fg, #fff), transparent 22%) !important;
  margin-top: 0.15rem !important;
}
header.navbar .user-menu-dropdown-toggle .user-picture,
header.navbar .user-menu-dropdown-toggle .avatar,
header.navbar .user-menu-dropdown-toggle .avatar.rounded,
header.navbar .user-menu-dropdown-toggle img {
  border-radius: 50% !important;
  width: 2rem !important;
  height: 2rem !important;
  min-width: 2rem;
}
CSS;
        }

        return $base . $extra;
    }

    /**
     * In-page Entity tab strip (#tabspanel).
     *
     * @param array<string, mixed> $style
     */
    private static function tabs(array $style): string
    {
        $preset = (string) ($style['tab_preset'] ?? 'default');
        $base = <<<CSS
.card-tabs #tabspanel.nav-tabs .nav-link {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.5rem 0.8rem;
  margin-block: var(--brandkit-tab-gap);
  transition: background-color var(--brandkit-duration) var(--brandkit-easing),
              color var(--brandkit-duration) var(--brandkit-easing),
              box-shadow var(--brandkit-duration) var(--brandkit-easing);
}
.card-tabs #tabspanel.nav-tabs .nav-link:hover {
  background: color-mix(in srgb, var(--tblr-primary), transparent 90%);
  color: inherit;
}
.card-tabs.vertical #tabspanel.nav-tabs .nav-item:first-child .nav-link {
  border-top-left-radius: var(--brandkit-radius-chrome);
}
.card-tabs.vertical #tabspanel.nav-tabs .nav-item:last-child .nav-link {
  border-bottom-left-radius: var(--brandkit-radius-chrome);
}
CSS;

        $extra = match ($preset) {
            'pill' => <<<CSS
.card-tabs #tabspanel.nav-tabs .nav-link {
  border-radius: var(--brandkit-radius-chrome) !important;
  margin-inline: 0.35rem;
  border-width: 0 !important;
}
.card-tabs.vertical #tabspanel.nav-tabs .nav-link {
  border-top-right-radius: var(--brandkit-radius-chrome) !important;
  border-bottom-right-radius: var(--brandkit-radius-chrome) !important;
}
.card-tabs #tabspanel.nav-tabs .nav-link.active {
  background: color-mix(in srgb, var(--tblr-primary), transparent 82%) !important;
  color: inherit !important;
  font-weight: 600;
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--tblr-primary), transparent 55%);
}
CSS,
            'underline' => <<<CSS
.card-tabs #tabspanel.nav-tabs .nav-link {
  border-radius: 0 !important;
  background: transparent !important;
  border-left: 0 !important;
  border-right: 0 !important;
  border-top: 0 !important;
  border-bottom: 2px solid transparent !important;
}
.card-tabs.vertical #tabspanel.nav-tabs .nav-link {
  border-bottom: 0 !important;
  border-left: 2px solid transparent !important;
}
.card-tabs #tabspanel.nav-tabs .nav-link.active {
  background: transparent !important;
  font-weight: 600;
  border-bottom-color: var(--tblr-primary) !important;
  color: var(--tblr-primary) !important;
}
.card-tabs.vertical #tabspanel.nav-tabs .nav-link.active {
  border-bottom-color: transparent !important;
  border-left-color: var(--tblr-primary) !important;
}
CSS,
            'accent_bar' => <<<CSS
.card-tabs #tabspanel.nav-tabs .nav-link.active {
  box-shadow: inset 4px 0 0 var(--tblr-primary);
  background: color-mix(in srgb, var(--tblr-primary), transparent 88%) !important;
  font-weight: 600;
  border-left-color: var(--tblr-primary) !important;
}
.card-tabs.horizontal #tabspanel.nav-tabs .nav-link.active {
  box-shadow: inset 0 -3px 0 var(--tblr-primary);
}
CSS,
            'card' => <<<CSS
div.card-tabs.vertical,
.card-tabs.vertical {
  gap: 1rem;
  align-items: stretch;
  background: transparent !important;
  margin-top: 0.15rem;
}
div.card-tabs.vertical > ul#tabspanel.nav.nav-tabs,
.card-tabs.vertical ul#tabspanel.nav-tabs {
  display: flex !important;
  flex-direction: column !important;
  background: var(--tblr-bg-surface, #fff) !important;
  border: 0 !important;
  border-right: 0 !important;
  border-radius: var(--brandkit-radius-card) !important;
  box-shadow: var(--brandkit-shadow-card);
  padding: 0.35rem 0.4rem 0.85rem;
  min-width: 220px;
  max-width: 260px;
}
div.card-tabs.vertical > ul#tabspanel.nav.nav-tabs::before,
.card-tabs.vertical ul#tabspanel.nav-tabs::before {
  content: var(--brandkit-tab-heading);
  display: flex;
  align-items: center;
  width: 100%;
  flex: 0 0 auto;
  font-weight: 650;
  font-size: 0.95rem;
  color: var(--tblr-primary);
  padding: 0.75rem 0.95rem 0.5rem;
  margin: 0;
  background: transparent;
  pointer-events: none;
}
div.card-tabs.vertical > ul#tabspanel.nav.nav-tabs > li.nav-item > a.nav-link,
div.card-tabs.vertical > ul#tabspanel.nav.nav-tabs > li.nav-item > a.nav-link.active,
div.card-tabs.vertical > ul#tabspanel.nav.nav-tabs > li.nav-item:first-child > a.nav-link,
div.card-tabs.vertical > ul#tabspanel.nav.nav-tabs > li.nav-item:last-child > a.nav-link,
.card-tabs.vertical ul#tabspanel.nav-tabs .nav-link,
.card-tabs.vertical ul#tabspanel.nav-tabs .nav-link.active {
  border: 0 none !important;
  border-width: 0 !important;
  border-left: 0 none !important;
  border-right: 0 none !important;
  border-top: 0 none !important;
  border-bottom: 0 none !important;
  border-color: transparent !important;
  border-radius: 999px !important;
  background: transparent !important;
  box-shadow: none !important;
  margin: 0.1rem 0.35rem !important;
  margin-right: 0.35rem !important;
  padding: 0.45rem 0.85rem !important;
  color: inherit;
}
div.card-tabs.vertical > ul#tabspanel.nav.nav-tabs > li.nav-item > a.nav-link:hover,
.card-tabs.vertical ul#tabspanel.nav-tabs .nav-link:hover {
  background: color-mix(in srgb, var(--tblr-primary), #fff 88%) !important;
  color: var(--tblr-primary) !important;
  border-radius: 999px !important;
}
div.card-tabs.vertical > ul#tabspanel.nav.nav-tabs > li.nav-item > a.nav-link.active,
.card-tabs.vertical ul#tabspanel.nav-tabs .nav-link.active {
  background: color-mix(in srgb, var(--tblr-primary), #fff 78%) !important;
  color: var(--tblr-primary) !important;
  font-weight: 600;
  box-shadow: none !important;
  border-left-width: 0 !important;
  border-left-color: transparent !important;
}
div.card-tabs.vertical > .tab-content.card,
.card-tabs.vertical .tab-content.card {
  border: 0 !important;
  border-radius: var(--brandkit-radius-card) !important;
  box-shadow: var(--brandkit-shadow-card);
  background: var(--tblr-bg-surface, #fff);
  overflow: visible !important;
}
CSS,
            'merge' => <<<CSS
.card-tabs.vertical {
  gap: 0;
  background: transparent !important;
}
.card-tabs.vertical #tabspanel.nav-tabs {
  background: transparent;
  border-right: 0 !important;
  padding-top: 0.35rem;
}
.card-tabs.vertical #tabspanel.nav-tabs .nav-link {
  border: 0 !important;
  border-top-left-radius: var(--brandkit-radius-chrome) !important;
  border-bottom-left-radius: var(--brandkit-radius-chrome) !important;
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
  background: transparent !important;
  margin-right: 0;
}
.card-tabs.vertical #tabspanel.nav-tabs .nav-link.active {
  background: var(--tblr-bg-surface, #fff) !important;
  color: inherit !important;
  font-weight: 600;
  box-shadow: none;
  border-left-width: 0 !important;
  border-right-color: transparent !important;
}
.card-tabs.vertical .tab-content.card {
  border: 0 !important;
  border-radius: var(--brandkit-radius-card) !important;
  box-shadow: var(--brandkit-shadow-card);
  background: var(--tblr-bg-surface, #fff);
  margin-left: -1px;
}
CSS,
            default => <<<CSS
.card-tabs #tabspanel.nav-tabs .nav-link.active {
  background: color-mix(in srgb, var(--tblr-primary), transparent 88%) !important;
  font-weight: 600;
}
.card-tabs.vertical #tabspanel.nav-tabs .nav-link.active {
  border-left: 4px solid var(--tblr-primary) !important;
}
CSS,
        };

        return $base . "\n" . $extra;
    }

    /**
     * Form layout, section cards, file inputs.
     *
     * @param array<string, mixed> $style
     */
    private static function forms(array $style): string
    {
        $css = <<<CSS
.brandkit-logo-row {
  border-bottom: 1px solid color-mix(in srgb, var(--tblr-border-color), transparent 35%);
}

CSS;

        if ((string) ($style['form_sections'] ?? 'default') === 'accordion') {
            $css .= <<<CSS
.brandkit-accordion {
  display: block;
  width: 100%;
  border: 1px solid color-mix(in srgb, var(--tblr-border-color), transparent 20%);
  border-radius: var(--brandkit-radius-card);
  background: var(--tblr-bg-surface, #fff);
  box-shadow: var(--brandkit-shadow-card);
  margin: 0.85rem 0;
  overflow: hidden;
}
.brandkit-accordion:focus,
.brandkit-accordion:focus-within {
  outline: none;
}
.brandkit-accordion > summary {
  list-style: none;
  display: flex;
  align-items: center;
  gap: 0.55rem;
  width: 100%;
  padding: 0.75rem 1.15rem;
  cursor: pointer;
  font-weight: 600;
  outline: none !important;
  border-radius: 0;
  box-shadow: none;
}
.brandkit-accordion > summary:focus {
  outline: none !important;
  box-shadow: none;
}
.brandkit-accordion > summary:focus-visible {
  outline: none !important;
  box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--tblr-primary), transparent 55%);
}
.brandkit-accordion > summary::-webkit-details-marker {
  display: none;
}
.brandkit-accordion > summary .brandkit-acc-chevron {
  margin-inline-start: auto;
  transition: transform var(--brandkit-duration) var(--brandkit-easing);
}
.brandkit-accordion[open] > summary {
  border-bottom: 1px solid color-mix(in srgb, var(--tblr-border-color), transparent 40%);
}
.brandkit-accordion[open] > summary .brandkit-acc-chevron {
  transform: rotate(90deg);
}
.brandkit-accordion-body {
  padding: 0 0 0.5rem;
  border-top: 0;
}
#brandkit-fields .sp-original-input-container,
#brandkit-fields .sp-colorize-container {
  display: block;
  max-width: 100%;
  border-radius: var(--brandkit-radius-input);
  overflow: hidden;
  background: transparent;
}
#brandkit-fields input.form-control.spectrum.sp-colorize {
  border-radius: var(--brandkit-radius-input) !important;
  overflow: hidden;
}
#brandkit-fields > .card.border-0,
.tab-content .asset .card.border-0.shadow-none {
  background: transparent !important;
  box-shadow: none !important;
  border: 0 !important;
}
#brandkit-fields > .card.border-0 > .card-header,
#brandkit-fields .card > .card-header.mb-3,
body.central .asset .card.border-0 > .card-header,
.tab-content .asset > form .card.border-0 > .card-header,
.tab-content .asset .card.border-0 > .card-header {
  background: color-mix(in srgb, var(--glpi-form-header-fg), var(--tblr-bg-surface, #fff) 86%) !important;
  color: var(--tblr-body-color) !important;
  border: 1px solid var(--glpi-form-header-border-color) !important;
  border-top: 1px solid var(--glpi-form-header-border-color) !important;
  border-radius: var(--brandkit-radius-card) !important;
  box-shadow: var(--brandkit-shadow-card);
  padding: 0.75rem 1.15rem !important;
  margin-bottom: 0.85rem !important;
  overflow: hidden;
}
#brandkit-fields .card.border-0 > .card-header .ribbon,
#brandkit-fields .card > .card-header .ribbon,
body.central .asset .card.border-0 > .card-header .ribbon,
.tab-content .asset .card.border-0 > .card-header .ribbon {
  display: none !important;
}
#brandkit-fields .card.border-0 > .card-header .card-title,
body.central .asset .card.border-0 > .card-header .card-title,
body.central .asset .card.border-0 > .card-header .card-subtitle,
.tab-content .asset .card.border-0 > .card-header .card-title,
.tab-content .asset .card.border-0 > .card-header .card-subtitle {
  margin: 0 !important;
  margin-inline-start: 0 !important;
  padding-inline-start: 0 !important;
  color: var(--tblr-body-color) !important;
  font-weight: 600;
}

CSS;
        }

        $file = (string) ($style['file_input_style'] ?? 'default');
        if ($file === 'outlined') {
            $css .= <<<CSS
input[type="file"]::file-selector-button {
  background: transparent;
  box-shadow: none;
  color: var(--tblr-primary);
  font-weight: 600;
}
.brandkit-file-ui {
  border: 1px solid var(--tblr-border-color);
  background: var(--tblr-bg-surface, #fff);
}
.brandkit-file-btn {
  color: var(--tblr-primary);
  font-weight: 600;
}
.brandkit-logo-preview img {
  box-shadow: none;
}

CSS;
        } elseif ($file === 'filled') {
            $css .= <<<CSS
input[type="file"].form-control,
.form-control[type="file"] {
  background: var(--tblr-bg-surface, #fff);
}
input[type="file"]::file-selector-button {
  background: var(--tblr-primary);
  color: var(--tblr-primary-fg, #fff);
  font-weight: 600;
  border-inline-end-color: color-mix(in srgb, var(--tblr-primary), #000 12%);
}
.brandkit-file-ui {
  border: 1px solid var(--tblr-border-color);
  background: var(--tblr-bg-surface, #fff);
  padding: 0;
  gap: 0;
}
.brandkit-file-btn {
  background: var(--tblr-primary);
  color: var(--tblr-primary-fg, #fff);
  font-weight: 600;
  padding: 0.45rem 0.9rem;
  border-radius: var(--brandkit-radius-chrome) 0 0 var(--brandkit-radius-chrome);
}
.brandkit-file-name {
  padding-inline: 0.75rem;
}
.brandkit-logo-preview img {
  box-shadow: 0 8px 18px color-mix(in srgb, var(--tblr-body-color), transparent 88%);
}

CSS;
        }

        $tone = (string) ($style['page_tone'] ?? 'default');
        if ($tone === 'grey') {
            $css .= <<<CSS
body.central,
body.central .page,
body.central .page-wrapper,
body.central .page-body,
body.central #page {
  background-color: #eef1f6 !important;
}
body.central .card-tabs.vertical {
  background: transparent !important;
}

CSS;
        } elseif ($tone === 'cream') {
            $css .= <<<CSS
body.central,
body.central .page,
body.central .page-wrapper,
body.central .page-body {
  background-color: #f6ead8 !important;
  background-image: linear-gradient(#f3d5b5, #f6ead8 8rem);
  background-repeat: no-repeat;
}
body.central .card-tabs.vertical {
  background: transparent !important;
}

CSS;
        }

        return $css;
    }

    /**
     * @param array<string, mixed> $style
     */
    private static function densityAndType(array $style): string
    {
        $family = self::fontStack($style);

        $css = <<<CSS
body,
.page,
.page-wrapper {
  font-family: {$family};
  font-size: var(--brandkit-font-size);
}

/* List views: search engine + ITIL tabs on user/asset forms (GLPI datatables). */
body.central table.search-results,
body.central [data-glpi-tab-content] table.table {
  --brandkit-list-font: var(--brandkit-font-size);
  font-size: var(--brandkit-list-font) !important;
}

body.central table.table.search-results thead th,
body.central table.search-results thead th,
body.central [data-glpi-tab-content] table.table thead th {
  font-size: var(--brandkit-list-font) !important;
  font-weight: 600 !important;
  text-transform: none !important;
  letter-spacing: normal;
  line-height: 1.35;
  color: color-mix(in srgb, var(--tblr-body-color), transparent 22%);
  background-color: var(--tblr-bg-surface, #fff);
  padding: 0.5rem 0.65rem;
  vertical-align: middle;
  white-space: nowrap;
  border-bottom: 1px solid var(--tblr-border-color);
}

body.central table.search-results thead th.fw-bold,
body.central [data-glpi-tab-content] table.table thead th.fw-bold {
  font-weight: 600 !important;
}

body.central table.search-results thead th .sort-indicator,
body.central [data-glpi-tab-content] table.table thead th .sort-indicator {
  font-size: 0.9em;
  vertical-align: middle;
}

body.central table.search-results tbody td,
body.central [data-glpi-tab-content] table.table tbody td {
  font-size: var(--brandkit-list-font);
  padding: 0.5rem 0.65rem;
  vertical-align: middle;
}

body.central table.search-results a,
body.central [data-glpi-tab-content] table.table a {
  font-weight: 500;
}

body.central table.search-results .brandkit-status-cell,
body.central [data-glpi-tab-content] table.table .brandkit-status-cell {
  white-space: normal;
}

body.central table.search-results .brandkit-status-pill,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill {
  --brandkit-status-accent: var(--tblr-primary);
  display: inline-flex;
  align-items: stretch;
  max-width: 12rem;
  border-radius: var(--brandkit-radius-chrome);
  border: 1px solid color-mix(in srgb, var(--brandkit-status-accent) 35%, transparent);
  background: color-mix(in srgb, var(--brandkit-status-accent) 14%, var(--tblr-bg-surface, #fff));
  line-height: 1.25;
  vertical-align: middle;
}

body.central table.search-results .brandkit-status-pill__tile,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill__tile {
  flex: 0 0 5px;
  width: 5px;
  border-radius: var(--brandkit-radius-chrome) 0 0 var(--brandkit-radius-chrome);
  background: var(--brandkit-status-accent);
}

body.central table.search-results .brandkit-status-pill__content,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill__content {
  display: inline-flex;
  align-items: flex-start;
  gap: 0.35rem;
  padding: 0.35rem 0.55rem 0.35rem 0.4rem;
  min-width: 0;
}

body.central table.search-results .brandkit-status-pill__content .itilstatus,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill__content .itilstatus {
  flex-shrink: 0;
  margin-top: 0.1rem;
}

body.central table.search-results .brandkit-status-pill__body,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill__body {
  display: flex;
  flex-direction: column;
  gap: 0.05rem;
  min-width: 0;
}

body.central table.search-results .brandkit-status-pill__title,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill__title {
  font-weight: 600;
}

body.central table.search-results .brandkit-status-pill__detail,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill__detail {
  font-size: 0.92em;
  font-weight: 500;
  opacity: 0.9;
}

body.central table.search-results td:has(.brandkit-list-pill-host),
body.central [data-glpi-tab-content] table.table td:has(.brandkit-list-pill-host) {
  width: 1%;
  white-space: nowrap;
  vertical-align: middle;
}

body.central table.search-results .badge_block.brandkit-list-pill-host,
body.central [data-glpi-tab-content] table.table .badge_block.brandkit-list-pill-host {
  display: inline-flex;
  border: 0;
  background: transparent;
  padding: 0;
  white-space: nowrap;
  width: max-content;
  max-width: max-content;
}

body.central table.search-results .brandkit-status-pill--single,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill--single {
  max-width: none;
  width: max-content;
  flex-shrink: 0;
}

body.central table.search-results .brandkit-status-pill--single .brandkit-status-pill__content,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill--single .brandkit-status-pill__content {
  align-items: center;
  min-width: max-content;
  flex-shrink: 0;
}

body.central table.search-results .brandkit-status-pill--single .brandkit-status-pill__body,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill--single .brandkit-status-pill__body {
  min-width: max-content;
}

body.central table.search-results .brandkit-status-pill--single .brandkit-status-pill__title,
body.central [data-glpi-tab-content] table.table .brandkit-status-pill--single .brandkit-status-pill__title {
  white-space: nowrap;
}

body.central table.search-results td:has(.brandkit-status-cell),
body.central [data-glpi-tab-content] table.table td:has(.brandkit-status-cell) {
  vertical-align: middle;
}

CSS;

        if (self::densityOverridesActive((string) $style['density'])) {
            $css .= <<<CSS
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

        return $css;
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
body.vertical-layout:not(.navbar-collapsed) .sidebar #navbar-menu .dropdown-menu {
  position: static !important;
  transform: none !important;
  inset: auto !important;
  float: none !important;
  min-width: 0 !important;
  width: 100%;
  margin: 0 !important;
  padding: 0.1rem 0 0.35rem !important;
  background: transparent !important;
  background-color: transparent !important;
  border: 0 !important;
  border-radius: 0 !important;
  box-shadow: none !important;
}
body.vertical-layout:not(.navbar-collapsed) .sidebar #navbar-menu .dropdown-menu.show {
  display: block !important;
}
body.vertical-layout:not(.navbar-collapsed) .sidebar #navbar-menu .dropdown-menu:not(.show) {
  display: none !important;
}
body.vertical-layout:not(.navbar-collapsed) .sidebar #navbar-menu .dropdown-header {
  display: none !important;
}
.sidebar #navbar-menu .dropdown-item {
  color: color-mix(in srgb, var(--glpi-mainmenu-fg), transparent 12%) !important;
  background: transparent !important;
  border-left: 0 !important;
  margin: 0.12rem 0.65rem;
  padding: 0.4rem 0.85rem 0.4rem 1.55rem !important;
  border-radius: 999px !important;
}
.sidebar #navbar-menu .dropdown-item:hover {
  background: color-mix(in srgb, #fff, transparent 88%) !important;
  color: #fff !important;
  border-left: 0 !important;
}
.sidebar #navbar-menu .dropdown-item.active {
  background: color-mix(in srgb, var(--tblr-primary), transparent 42%) !important;
  color: #fff !important;
  font-weight: 650;
  border-left: 0 !important;
}
body.navbar-collapsed .sidebar #navbar-menu .dropdown-menu {
  position: absolute !important;
}
body.navbar-collapsed .sidebar #navbar-menu .dropdown-item {
  margin-inline: 0.25rem;
  padding-inline: 0.75rem !important;
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

        if ((string) $style['nav_active_style'] === 'accent_bar') {
            $rules[] = <<<CSS
.sidebar #navbar-menu > .navbar-nav > .nav-item > .nav-link,
.sidebar #navbar-menu > ul.navbar-nav > li.nav-item > button.nav-link {
  background: transparent !important;
  border-left: 3px solid transparent !important;
  border-radius: 0 !important;
  box-shadow: none;
}
.sidebar #navbar-menu > .navbar-nav > .nav-item.active > .nav-link,
.sidebar #navbar-menu > .navbar-nav > .nav-item > .nav-link.active,
.sidebar #navbar-menu > .navbar-nav > .nav-item > .nav-link.show,
.sidebar #navbar-menu > ul.navbar-nav > li.nav-item.active > button.nav-link {
  color: var(--tblr-primary) !important;
  background: transparent !important;
  border-left-color: var(--tblr-primary) !important;
  font-weight: 650;
  box-shadow: inset 3px 0 0 var(--tblr-primary);
}
.sidebar #navbar-menu > .navbar-nav > .nav-item:not(.active) > .nav-link:hover {
  color: var(--tblr-primary) !important;
  border-left-color: var(--tblr-primary) !important;
  background: color-mix(in srgb, #000, transparent 88%) !important;
}
CSS;
        }

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
            'accent_bar' => "{$selector} {\n  box-shadow: inset 3px 0 0 var(--tblr-primary);\n  background-color: transparent !important;\n  color: var(--tblr-primary) !important;\n}",
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
        $radius = 'var(--brandkit-radius-chrome)';
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
  border-radius: var(--brandkit-radius-chrome);
}
body.vertical-layout:not(.navbar-collapsed) .sidebar .dropdown-menu,
body.vertical-layout:not(.navbar-collapsed) .sidebar #navbar-menu .dropdown-menu {
  border-radius: 0 !important;
  box-shadow: none !important;
  background: transparent !important;
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
  background-size: 100% 100%;
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

    /**
     * Ticket / Change / Problem split view: card timeline + properties panel.
     *
     * @param array<string, mixed> $style
     */
    private static function itil(array $style): string
    {
        if (!self::itilChromeActive($style)) {
            return '';
        }

        return <<<CSS
body.central #itil-object-container {
  padding-bottom: 0.5rem;
}

body.central .itil-object .itil-left-side {
  border-top: 0 !important;
  background: var(--tblr-bg-surface, #fff);
  border-radius: var(--brandkit-radius-card);
  box-shadow: var(--brandkit-shadow-card);
}

body.central .itil-timeline {
  width: 100%;
}

body.central .timeline-item .timeline-content {
  border-radius: var(--brandkit-radius-card) !important;
  box-shadow: var(--brandkit-shadow-timeline);
  padding: 0.85rem 1rem !important;
}

body.central .timeline-item .timeline-content::before,
body.central .timeline-item .timeline-content::after {
  display: none !important;
}

body.central .timeline-item:not(.private-item) .timeline-content {
  padding: 0.85rem 1rem !important;
}

body.central .timeline-item .timeline-content.card {
  box-shadow: var(--brandkit-shadow-timeline);
}

body.central .timeline-item.Document_Item .timeline-content,
body.central .timeline-item.Document_Item .timeline-content.card {
  border: 0;
  box-shadow: none;
  background-color: transparent;
  padding: 0.35rem 0 !important;
}

body.central .timeline-item .timeline-content .card-body {
  padding: 0.25rem 0.5rem !important;
}

body.central .timeline-item .timeline-header {
  margin-top: 0;
  margin-bottom: 0.65rem;
  font-size: 0.88rem;
}

body.central .timeline-item .timeline-header .badge {
  border-radius: var(--brandkit-radius-chrome);
  font-weight: 500;
}

/* User initials / photos — rounded tiles, softer tints, light shadow. */
body.central #itil-object-container .avatar.rounded,
body.central table.search-results .avatar.rounded,
body.central [data-glpi-tab-content] table.table .avatar.rounded,
body.central .asset .avatar.rounded {
  border-radius: var(--brandkit-radius-chrome) !important;
  box-shadow: 0 1px 2px color-mix(in srgb, var(--tblr-body-color), transparent 80%),
    0 3px 8px color-mix(in srgb, var(--tblr-body-color), transparent 90%);
  font-weight: 700;
  letter-spacing: 0.02em;
  border: 1px solid color-mix(in srgb, #fff, var(--tblr-body-color) 10%);
}

body.central #itil-object-container .avatar-md:not(.avatar-xl) {
  width: 2.35rem;
  height: 2.35rem;
  font-size: 0.72rem;
}

body.central .asset .avatar-xl.rounded {
  font-size: 1.35rem;
}

body.central header.navbar .avatar.rounded {
  border-radius: 50% !important;
  box-shadow: none;
  border: 0;
  font-weight: inherit;
  letter-spacing: inherit;
}

@media (min-width: 992px) {
  body.central .itil-object .itil-left-side {
    padding: 1rem 1.25rem 1.25rem !important;
    margin-inline-end: 0.5rem;
  }

  body.central .itil-object .itil-right-side {
    background: var(--tblr-bg-surface, #fff);
    border-radius: var(--brandkit-radius-card);
    box-shadow: var(--brandkit-shadow-card);
    border: 0 !important;
    border-left: 0 !important;
    padding: 0.35rem 0.5rem 0.75rem;
    margin-top: 0 !important;
  }
}

body.central .itil-object-fields#itil-data {
  border-radius: var(--brandkit-radius-card);
}

body.central .itil-object-fields .accordion-item {
  border: 0;
  background: transparent;
}

body.central .itil-object-fields .accordion-header {
  margin-bottom: 0.15rem;
}

body.central .itil-object-fields .accordion-button {
  border-radius: var(--brandkit-radius-chrome);
  font-weight: 600;
  box-shadow: none;
  padding: 0.55rem 0.75rem;
  background: color-mix(in srgb, var(--tblr-body-color), transparent 96%);
}

body.central .itil-object-fields .accordion-button:not(.collapsed) {
  background: color-mix(in srgb, var(--tblr-primary), transparent 90%);
  color: var(--tblr-primary);
}

body.central .itil-object-fields .accordion-body {
  padding-top: 0.75rem;
  padding-bottom: 0.5rem;
}

body.central .itil-object-fields .accordion-body > .input-group:first-child,
body.central .itil-object-fields .accordion-body > .row:first-child,
body.central .itil-object-fields .accordion-body > form:first-child {
  margin-top: 0.1rem;
}

body.central .itil-footer {
  background: var(--tblr-bg-surface, #fff);
  border: 1px solid color-mix(in srgb, var(--tblr-border-color), transparent 25%);
  border-radius: var(--brandkit-radius-card) var(--brandkit-radius-card) 0 0;
  box-shadow: 0 -6px 24px color-mix(in srgb, var(--tblr-body-color), transparent 92%);
  margin-top: 0.35rem;
}

body.central .itil-footer .main-actions .btn {
  border-radius: var(--brandkit-radius-btn);
}

body.central .itil-footer .main-actions.btn-group > .btn:first-child {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
  border-top-left-radius: var(--brandkit-radius-btn) !important;
  border-bottom-left-radius: var(--brandkit-radius-btn) !important;
}

body.central .itil-footer .main-actions.btn-group > .btn.dropdown-toggle-split {
  border-top-left-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
  border-top-right-radius: var(--brandkit-radius-btn) !important;
  border-bottom-right-radius: var(--brandkit-radius-btn) !important;
  margin-inline-start: 0;
  border-inline-start: 1px solid color-mix(in srgb, #fff, transparent 35%);
}

body.central .itil-footer .main-actions .btn.action-task,
body.central .itil-footer .main-actions .btn.action-solution,
body.central .itil-footer .main-actions .btn.action-validation,
body.central .itil-footer .main-actions .btn.action-document {
  box-shadow: var(--brandkit-shadow-card);
}

body.central .itil-footer .main-actions .dropdown-menu {
  min-width: 13rem;
  border: 1px solid color-mix(in srgb, var(--tblr-border-color), transparent 15%);
  padding: 0.35rem;
}

body.central .itil-footer .main-actions .dropdown-menu.show {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

body.central .itil-footer .main-actions .dropdown-menu > li {
  margin: 0;
  list-style: none;
}

body.central .itil-footer .main-actions .dropdown-menu .dropdown-item {
  border-radius: var(--brandkit-radius-chrome) !important;
  box-shadow: none !important;
  margin: 0;
  padding: 0.5rem 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-weight: 500;
  white-space: normal;
}

body.central .itil-footer .main-actions .dropdown-menu .dropdown-item:hover,
body.central .itil-footer .main-actions .dropdown-menu .dropdown-item:focus {
  filter: brightness(0.97);
}

body.central .itil-footer {
  overflow: visible;
}

body.central .itil-footer .form-buttons > span:first-child {
  flex: 0 0 auto;
  z-index: 2;
}

body.central .itil-right-side #itil-data {
  position: relative;
}

@media (min-width: 992px) {
  body.central #itil-object-container.right-collapsed .itil-left-side {
    width: calc(100% - 90px);
  }

  /* GLPI .itil-object is flex-column: never use flex-basis 90px here — that sets height, not width. */
  body.central #itil-object-container.right-collapsed .itil-right-side {
    overflow: hidden;
    width: 90px !important;
    max-width: 90px;
    height: calc(100% + 4px);
    flex: 0 0 auto !important;
    padding: 0 !important;
    cursor: pointer;
    border-radius: 0 !important;
    box-shadow: none !important;
    border: 0 !important;
    border-left: 1px solid var(--tblr-border-color) !important;
    background: var(--tblr-bg-surface, #fff) !important;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side .itil-object-fields#itil-data {
    border-radius: 0;
    box-shadow: none;
    position: static;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side .itil-object-fields .accordion-header {
    margin-bottom: 0;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side .itil-object-fields .accordion-item {
    background: transparent;
  }

  /* Native collapsed rail: icon-only headers (BrandKit card tabs must not apply here). */
  body.central #itil-object-container.right-collapsed .itil-right-side #itil-data .item-title {
    display: none !important;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side #itil-data .accordion-collapse {
    display: none !important;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side #itil-data .accordion-button::after {
    display: none !important;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side #itil-data .accordion-button .btn {
    display: none !important;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side .itil-object-fields .accordion-button {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.15rem;
    width: 100%;
    min-height: 2.35rem;
    padding: 0.4rem 0.2rem !important;
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    background: transparent !important;
    color: var(--tblr-body-color);
  }

  body.central #itil-object-container.right-collapsed .itil-right-side .itil-object-fields .accordion-button .badge {
    margin: 0 !important;
    max-width: 100%;
    padding: 0.1rem 0.3rem;
    font-size: 0.65rem;
    line-height: 1.1;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side #heading-main-item .accordion-button.collapsed .status-recall {
    display: inline-flex;
    justify-content: center;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side #heading-main-item .accordion-button.collapsed .item-icon {
    display: none !important;
  }

  body.central #itil-object-container.right-collapsed .itil-footer .form-buttons > span:first-child {
    display: none !important;
    pointer-events: none !important;
  }

  body.central #itil-object-container.right-collapsed .itil-footer .switch-panel-width,
  body.central #itil-object-container.right-collapsed .itil-footer .collapse-panel {
    display: none !important;
  }

  body.central #itil-object-container.right-expanded .itil-footer .collapse-panel {
    display: none !important;
  }

  body.central #itil-object-container.right-expanded .itil-footer .switch-panel-width {
    display: inline-flex !important;
    visibility: visible !important;
  }

  body.central #itil-object-container:not(.right-collapsed) .itil-right-side .switch-panel-width {
    display: none !important;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side #itil-data {
    padding-bottom: 3.75rem;
  }

  /* Sticky .itil-footer overlays the rail; sit expand control above it (native start-0 alignment). */
  body.central #itil-object-container.right-collapsed .itil-right-side #itil-data > .d-none.d-md-block {
    position: absolute;
    bottom: 3.65rem;
    left: 0;
    right: auto;
    width: auto;
    z-index: 2;
    margin: 0;
    padding: 0 0 0 0.15rem;
    text-align: start;
    pointer-events: none;
  }

  body.central #itil-object-container.right-collapsed .itil-right-side .switch-panel-width {
    display: inline-block !important;
    visibility: visible !important;
    position: static !important;
    pointer-events: auto;
    margin: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    border: 0 !important;
    color: var(--tblr-muted);
  }

  body.central #itil-object-container.right-collapsed .itil-right-side .switch-panel-width:hover {
    color: var(--tblr-primary);
  }
}

CSS;
    }

    /**
     * @param array<string, mixed> $style
     */
    private static function itilChromeActive(array $style): bool
    {
        $preset = (string) ($style['preset'] ?? '');

        return $preset === 'clean_cards'
            || (string) ($style['page_tone'] ?? '') === 'grey'
            || (string) ($style['tab_preset'] ?? '') === 'card';
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
     * Default / comfortable keep GLPI spacing; only compact and spacious override paddings.
     */
    private static function densityOverridesActive(string $density): bool
    {
        return in_array($density, ['compact', 'spacious'], true);
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

        $names = BundledFonts::cssNames();
        $id = (string) $style['font_family'];
        if (isset($names[$id])) {
            return sprintf('"%s", system-ui, sans-serif', $names[$id]);
        }

        return 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
    }

    private static function px(int $n): string
    {
        return $n === 999 ? '999px' : $n . 'px';
    }

    /**
     * Radius used on small / grouped chrome. Never a full pill.
     */
    private static function chromePx(int $n, int $cap = 10): string
    {
        if ($n <= 0) {
            return '0px';
        }
        if ($n === 999) {
            $n = $cap;
        }

        return min($n, $cap) . 'px';
    }

    /**
     * Input / select radius. Explicit 0 stays square only when the theme is globally square;
     * otherwise inherit capped chrome so selects match cards (e.g. Clean cards).
     *
     * @param array<string, mixed> $style
     */
    private static function resolveInputRadiusPx(array $style, int $globalRadius): string
    {
        $inputRadius = (int) $style['radius_inputs'];
        if ($inputRadius > 0) {
            return self::px($inputRadius);
        }
        if ($globalRadius > 0) {
            return self::chromePx($globalRadius);
        }

        return '0px';
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
