<?php

/**
 * Structured Style layer: enums, defaults, sanitisation, presets, JSON I/O.
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

final class StyleSchema
{
    public const VERSION = '1.3.1';

    /**
     * @var list<string>
     */
    public const SIDEBAR_PRESETS = ['default', 'compact', 'pill', 'floating', 'glass'];

    /**
     * @var list<string>
     */
    public const NAV_ITEM_STYLES = ['background', 'accent_bar', 'scale', 'indent'];

    /**
     * @var list<string>
     */
    public const HEADER_STYLES = ['flat', 'gradient', 'transparent'];

    /**
     * @var list<string>
     */
    public const DROPDOWN_STYLES = ['default', 'rounded', 'glass'];

    /**
     * @var list<string>
     */
    public const SHADOW_PRESETS = ['none', 'soft', 'medium', 'strong', 'glow'];

    /**
     * @var list<string>
     */
    public const HOVER_PRESETS = ['none', 'lift', 'grow', 'glow'];

    /**
     * @var list<string>
     */
    public const SPEEDS = ['instant', 'fast', 'normal', 'slow'];

    /**
     * @var list<string>
     */
    public const EASINGS = ['ease', 'ease-in-out', 'linear', 'smooth'];

    /**
     * @var list<string>
     */
    public const DENSITIES = ['compact', 'comfortable', 'spacious'];

    /**
     * @var list<string>
     */
    public const FONTS = [
        'system',
        'inter',
        'poppins',
        'roboto',
        'open_sans',
        'montserrat',
        'lato',
        'nunito',
        'raleway',
        'ibm_plex',
        'source_sans',
        'custom',
    ];

    /**
     * @var list<string>
     */
    public const TAB_PRESETS = ['default', 'pill', 'underline', 'accent_bar', 'card', 'merge'];

    /**
     * @var list<string>
     */
    public const FORM_LAYOUTS = ['default', 'two_column'];

    /**
     * @var list<string>
     */
    public const FORM_SECTIONS = ['default', 'accordion'];

    /**
     * @var list<string>
     */
    public const FILE_INPUT_STYLES = ['default', 'outlined', 'filled'];

    /**
     * @var list<string>
     */
    public const SEARCH_STYLES = ['default', 'inset_dark', 'inset_light'];

    /**
     * @var list<string>
     */
    public const BREADCRUMB_STYLES = ['default', 'ghost', 'pill'];

    /**
     * @var list<string>
     */
    public const PAGE_TONES = ['default', 'grey', 'cream'];

    /**
     * @var list<string>
     */
    public const FITS = ['cover', 'contain', 'repeat'];

    /**
     * @var list<string>
     */
    public const POSITIONS = ['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'];

    /**
     * @var list<string>
     */
    public const WATERMARK_TYPES = ['none', 'image', 'text'];

    /**
     * @var list<string>
     */
    public const WATERMARK_SIZES = ['sm', 'md', 'lg'];

    /**
     * @var list<int>
     */
    public const RADIUS_STEPS = [0, 2, 4, 6, 8, 10, 12, 16, 20, 24, 999];

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'preset'              => '',
            'sidebar_preset'      => 'default',
            'nav_active_style'    => 'background',
            'nav_hover_style'     => 'background',
            'nav_icon_size'       => 18,
            'nav_item_spacing'    => 4,
            'tab_preset'          => 'default',
            'tab_item_spacing'    => 0,
            'form_layout'         => 'default',
            'form_sections'       => 'default',
            'file_input_style'    => 'default',
            'search_style'        => 'default',
            'breadcrumb_style'    => 'default',
            'page_tone'           => 'default',
            'header_user_chip'    => 0,
            'header_style'        => 'flat',
            'dropdown_style'      => 'default',
            'radius_global'       => 6,
            'radius_cards'        => 8,
            'radius_buttons'      => 6,
            'radius_inputs'       => 6,
            'radius_modals'       => 10,
            'shadow_cards'        => 'soft',
            'shadow_dropdowns'    => 'soft',
            'shadow_modals'       => 'medium',
            'shadow_sidebar'      => 'none',
            'hover_cards'         => 'none',
            'hover_buttons'       => 'none',
            'hover_menu'          => 'none',
            'transition_speed'    => 'normal',
            'transition_easing'   => 'ease-in-out',
            'fade_in'             => 0,
            'disable_animations'  => 0,
            'density'             => 'comfortable',
            'font_family'         => 'system',
            'font_cdn'            => 0,
            'font_url'            => '',
            'font_custom_name'    => '',
            'font_size'           => 14,
            'gradient_header'     => self::emptyGradient(),
            'gradient_sidebar'    => self::emptyGradient(),
            'gradient_buttons'    => self::emptyGradient(),
            'gradient_login'      => self::emptyGradient(),
            'gradient_page'       => self::emptyGradient(),
            'page_bg_fit'         => 'cover',
            'page_bg_position'    => 'center',
            'page_bg_overlay'     => 0,
            'watermark_type'      => 'none',
            'watermark_text'      => '',
            'watermark_opacity'   => 8,
            'watermark_size'      => 'md',
            'watermark_position'  => 'center',
            'watermark_tile'      => 0,
            'watermark_app'       => 1,
            'watermark_login'     => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyGradient(): array
    {
        return [
            'enabled' => 0,
            'angle'   => 135,
            'stop1'   => '',
            'stop2'   => '',
            'stop3'   => '',
        ];
    }

    /**
     * Built-in gallery templates (JSON files). Kept for callers that expect
     * id => {label, style}.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function presets(): array
    {
        return TemplateCatalog::asPresets();
    }

    /**
     * @param mixed $raw JSON string or array
     * @return array<string, mixed>
     */
    public static function sanitize(mixed $raw): array
    {
        $data = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $data = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $data = $raw;
        }

        $out = self::defaults();

        $out['preset'] = TemplateCatalog::slug((string) ($data['preset'] ?? ''));
        $out['sidebar_preset'] = self::pick($data['sidebar_preset'] ?? 'default', self::SIDEBAR_PRESETS);
        $out['nav_active_style'] = self::pick($data['nav_active_style'] ?? 'background', self::NAV_ITEM_STYLES);
        $out['nav_hover_style'] = self::pick($data['nav_hover_style'] ?? 'background', self::NAV_ITEM_STYLES);
        $out['nav_icon_size'] = self::clampInt($data['nav_icon_size'] ?? 18, 14, 32);
        $out['nav_item_spacing'] = self::clampInt($data['nav_item_spacing'] ?? 4, 0, 16);
        $out['tab_preset'] = self::pick($data['tab_preset'] ?? 'default', self::TAB_PRESETS);
        $out['tab_item_spacing'] = self::clampInt($data['tab_item_spacing'] ?? 0, 0, 12);
        $out['form_layout'] = self::pick($data['form_layout'] ?? 'default', self::FORM_LAYOUTS);
        $out['form_sections'] = self::pick($data['form_sections'] ?? 'default', self::FORM_SECTIONS);
        $out['file_input_style'] = self::pick($data['file_input_style'] ?? 'default', self::FILE_INPUT_STYLES);
        $out['search_style'] = self::pick($data['search_style'] ?? 'default', self::SEARCH_STYLES);
        $out['breadcrumb_style'] = self::pick($data['breadcrumb_style'] ?? 'default', self::BREADCRUMB_STYLES);
        $out['page_tone'] = self::pick($data['page_tone'] ?? 'default', self::PAGE_TONES);
        $out['header_user_chip'] = self::boolInt($data['header_user_chip'] ?? 0);
        $out['header_style'] = self::pick($data['header_style'] ?? 'flat', self::HEADER_STYLES);
        $out['dropdown_style'] = self::pick($data['dropdown_style'] ?? 'default', self::DROPDOWN_STYLES);
        foreach (['radius_global', 'radius_cards', 'radius_buttons', 'radius_inputs', 'radius_modals'] as $key) {
            $out[$key] = self::pickInt($data[$key] ?? $out[$key], self::RADIUS_STEPS);
        }
        $out['shadow_cards'] = self::pick($data['shadow_cards'] ?? 'soft', self::SHADOW_PRESETS);
        $out['shadow_dropdowns'] = self::pick($data['shadow_dropdowns'] ?? 'soft', self::SHADOW_PRESETS);
        $out['shadow_modals'] = self::pick($data['shadow_modals'] ?? 'medium', self::SHADOW_PRESETS);
        $out['shadow_sidebar'] = self::pick($data['shadow_sidebar'] ?? 'none', self::SHADOW_PRESETS);
        $out['hover_cards'] = self::pick($data['hover_cards'] ?? 'none', self::HOVER_PRESETS);
        $out['hover_buttons'] = self::pick($data['hover_buttons'] ?? 'none', self::HOVER_PRESETS);
        $out['hover_menu'] = self::pick($data['hover_menu'] ?? 'none', self::HOVER_PRESETS);
        $out['transition_speed'] = self::pick($data['transition_speed'] ?? 'normal', self::SPEEDS);
        $out['transition_easing'] = self::pick($data['transition_easing'] ?? 'ease-in-out', self::EASINGS);
        $out['fade_in'] = self::boolInt($data['fade_in'] ?? 0);
        $out['disable_animations'] = self::boolInt($data['disable_animations'] ?? 0);
        $out['density'] = self::pick($data['density'] ?? 'comfortable', self::DENSITIES);
        $out['font_family'] = self::pick($data['font_family'] ?? 'system', self::FONTS);
        $out['font_cdn'] = self::boolInt($data['font_cdn'] ?? 0);
        $out['font_url'] = self::sanitizeFontUrl((string) ($data['font_url'] ?? ''));
        $out['font_custom_name'] = mb_substr(preg_replace('/[^A-Za-z0-9 _-]/', '', (string) ($data['font_custom_name'] ?? '')) ?? '', 0, 64);
        $out['font_size'] = self::clampInt($data['font_size'] ?? 14, 12, 20);
        foreach (['gradient_header', 'gradient_sidebar', 'gradient_buttons', 'gradient_login', 'gradient_page'] as $gkey) {
            $out[$gkey] = self::sanitizeGradient($data[$gkey] ?? []);
        }
        $out['page_bg_fit'] = self::pick($data['page_bg_fit'] ?? 'cover', self::FITS);
        $out['page_bg_position'] = self::pick($data['page_bg_position'] ?? 'center', self::POSITIONS);
        $out['page_bg_overlay'] = self::clampInt($data['page_bg_overlay'] ?? 0, 0, 80);
        $out['watermark_type'] = self::pick($data['watermark_type'] ?? 'none', self::WATERMARK_TYPES);
        $out['watermark_text'] = mb_substr(trim(strip_tags((string) ($data['watermark_text'] ?? ''))), 0, 80);
        $out['watermark_opacity'] = self::clampInt($data['watermark_opacity'] ?? 8, 1, 40);
        $out['watermark_size'] = self::pick($data['watermark_size'] ?? 'md', self::WATERMARK_SIZES);
        $out['watermark_position'] = self::pick($data['watermark_position'] ?? 'center', self::POSITIONS);
        $out['watermark_tile'] = self::boolInt($data['watermark_tile'] ?? 0);
        $out['watermark_app'] = self::boolInt($data['watermark_app'] ?? 1);
        $out['watermark_login'] = self::boolInt($data['watermark_login'] ?? 0);

        return $out;
    }

    /**
     * @param array<string, mixed> $style
     */
    public static function encode(array $style): string
    {
        return json_encode(self::sanitize($style), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function fromRow(array $row): array
    {
        return self::sanitize($row['style_json'] ?? '');
    }

    /**
     * @param array<string, mixed> $branding
     * @return array<string, mixed>
     */
    public static function export(array $branding, array $style): array
    {
        $colors = [];
        foreach (
            [
                'color_primary',
                'color_secondary',
                'color_accent',
                'color_sidebar',
                'color_header',
                'login_background_color',
                'dashboard_card_bg',
                'dashboard_card_text',
            ] as $field
        ) {
            $colors[$field] = $branding[$field] ?? null;
        }

        return [
            'kind'               => 'brandkit-template',
            'brandkit'           => self::VERSION,
            'name'               => (string) ($branding['product_name'] ?? ''),
            'description'        => '',
            'product_name'       => (string) ($branding['product_name'] ?? ''),
            'page_title_prefix'  => (string) ($branding['page_title_prefix'] ?? ''),
            'login_heading'      => (string) ($branding['login_heading'] ?? ''),
            'login_message'      => (string) ($branding['login_message'] ?? ''),
            'login_footer_links' => (string) ($branding['login_footer_links'] ?? ''),
            'custom_css'         => (string) ($branding['custom_css'] ?? ''),
            'dashboard_palette'  => (string) ($branding['dashboard_palette'] ?? ''),
            'email_header_html'  => (string) ($branding['email_header_html'] ?? ''),
            'email_footer_html'  => (string) ($branding['email_footer_html'] ?? ''),
            'colors'             => $colors,
            'style'              => self::sanitize($style),
        ];
    }

    /**
     * @param mixed $payload
     * @return array{ok: true, data: array<string, mixed>}|array{ok: false, error: string}
     */
    public static function import(mixed $payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
        } else {
            $decoded = $payload;
        }
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'Invalid JSON'];
        }

        $style = self::sanitize($decoded['style'] ?? $decoded);
        $out = ['style' => $style];
        if (isset($decoded['name']) && is_string($decoded['name'])) {
            $out['name'] = mb_substr(trim(strip_tags($decoded['name'])), 0, 80);
        }
        if (isset($decoded['description']) && is_string($decoded['description'])) {
            $out['description'] = mb_substr(trim(strip_tags($decoded['description'])), 0, 240);
        }
        $out['kind'] = isset($decoded['kind']) && $decoded['kind'] === 'brandkit-template'
            ? 'brandkit-template'
            : 'brandkit-branding';

        if (isset($decoded['colors']) && is_array($decoded['colors'])) {
            $out['colors'] = $decoded['colors'];
        }
        foreach (
            [
                'product_name',
                'page_title_prefix',
                'login_heading',
                'login_message',
                'login_footer_links',
                'custom_css',
                'dashboard_palette',
                'email_header_html',
                'email_footer_html',
            ] as $key
        ) {
            if (isset($decoded[$key]) && is_string($decoded[$key])) {
                $out[$key] = $decoded[$key];
            }
        }

        return ['ok' => true, 'data' => $out];
    }

    public static function sanitizeFontUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!str_starts_with($url, 'https://fonts.googleapis.com/css2?')
            && !str_starts_with($url, 'https://fonts.googleapis.com/css?')) {
            return '';
        }
        if (preg_match('/[^A-Za-z0-9._~:\\/?@!&*+,;=%\\-]/', $url) === 1) {
            return '';
        }
        if (str_contains(strtolower($url), 'javascript')) {
            return '';
        }

        return mb_substr($url, 0, 512);
    }

    public static function builtInFontUrl(string $family): string
    {
        return match ($family) {
            'inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap',
            'poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap',
            'roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap',
            'open_sans' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap',
            'montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap',
            'lato' => 'https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap',
            'nunito' => 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap',
            'raleway' => 'https://fonts.googleapis.com/css2?family=Raleway:wght@400;700&display=swap',
            'ibm_plex' => 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;700&display=swap',
            'source_sans' => 'https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;700&display=swap',
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private static function sanitizeGradient(mixed $raw): array
    {
        $g = is_array($raw) ? $raw : [];
        $angle = self::clampInt($g['angle'] ?? 135, 0, 360);

        return [
            'enabled' => self::boolInt($g['enabled'] ?? 0),
            'angle'   => $angle,
            'stop1'   => CssSanitizer::normalizeHexColor(isset($g['stop1']) ? (string) $g['stop1'] : null) ?? '',
            'stop2'   => CssSanitizer::normalizeHexColor(isset($g['stop2']) ? (string) $g['stop2'] : null) ?? '',
            'stop3'   => CssSanitizer::normalizeHexColor(isset($g['stop3']) ? (string) $g['stop3'] : null) ?? '',
        ];
    }

    /**
     * @param list<string> $allowed
     */
    private static function pick(mixed $value, array $allowed): string
    {
        $value = is_string($value) ? $value : '';

        return in_array($value, $allowed, true) ? $value : $allowed[0];
    }

    /**
     * @param list<int> $allowed
     */
    private static function pickInt(mixed $value, array $allowed): int
    {
        $n = (int) $value;

        return in_array($n, $allowed, true) ? $n : $allowed[0];
    }

    private static function clampInt(mixed $value, int $min, int $max): int
    {
        $n = (int) $value;

        return max($min, min($max, $n));
    }

    private static function boolInt(mixed $value): int
    {
        return (!empty($value) && $value !== '0') ? 1 : 0;
    }
}
