<?php

/**
 * Per-entity branding settings.
 * Table: glpi_plugin_brandkit_brandings
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

use CommonDBTM;
use CommonGLPI;
use Config;
use DBmysql;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use Html;
use Session;

class Branding extends CommonDBTM
{
    public static string $rightname = 'plugin_brandkit_config';

    public bool $dohistory = true;

    public const CONFIG_CONTEXT = 'plugin:brandkit';

    /**
     * @var list<string>
     */
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

    /**
     * @var list<string>
     */
    public const COLOR_FIELDS = [
        'color_primary',
        'color_secondary',
        'color_accent',
        'color_sidebar',
        'color_header',
        'login_background_color',
        'dashboard_card_bg',
        'dashboard_card_text',
    ];

    public static function getTypeName($nb = 0)
    {
        return _n('Branding', 'Branding', $nb, 'brandkit');
    }

    public static function getIcon(): string
    {
        return 'ti ti-palette';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!($item instanceof Entity) || $withtemplate) {
            return '';
        }

        if (!self::canView()) {
            return '';
        }

        return self::createTabEntry(self::getTypeName(1), 0, $item::getType(), self::getIcon());
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!($item instanceof Entity)) {
            return false;
        }

        self::showForEntity($item);

        return true;
    }

    public static function showForEntity(Entity $entity): void
    {
        $entities_id = (int) $entity->getID();
        $row         = self::getRowForEntity($entities_id);
        $inherited   = self::resolve($entities_id);
        $is_root     = $entities_id === 0;
        $inherit     = $is_root ? 0 : (int) ($row['inherit'] ?? 1);

        $item = new self();
        if (isset($row['id'])) {
            $item->getFromDB((int) $row['id']);
        } else {
            $item->getEmpty();
            $item->fields['entities_id'] = $entities_id;
            $item->fields['inherit'] = $inherit;
        }

        $previews = [];
        foreach (self::IMAGE_SLOTS as $slot) {
            $source = ($inherit && !$is_root) ? $inherited : $item->fields;
            $previews[$slot] = self::assetUrlFromRow($source, $slot);
        }

        $source = ($inherit && !$is_root) ? $inherited : $item->fields;
        $style  = StyleSchema::fromRow($source);

        TemplateRenderer::getInstance()->display('@brandkit/entity_tab.html.twig', [
            'item'             => $item,
            'entity'           => $entity,
            'is_root'          => $is_root,
            'inherit'          => $inherit,
            'inherited'        => $inherited,
            'previews'         => $previews,
            'can_edit'         => self::canUpdate() && Session::haveAccessToEntity($entities_id),
            'form_url'         => Html::getPrefixedUrl('/plugins/brandkit/Branding'),
            'export_url'       => Html::getPrefixedUrl('/plugins/brandkit/Export?e=' . $entities_id),
            'dashboard_colors' => self::paletteList($source),
            'footer_links'     => self::footerLinksToText(
                (string) ($source['login_footer_links'] ?? '')
            ),
            'style'            => $style,
            'style_presets'    => StyleSchema::presets(),
            'style_defaults'   => StyleSchema::defaults(),
        ]);
    }

    public static function displayLoginExtras(): void
    {
        $resolved = self::resolveForLogin();
        $heading  = trim((string) ($resolved['login_heading'] ?? ''));
        $message  = trim((string) ($resolved['login_message'] ?? ''));
        $links    = self::parseFooterLinks((string) ($resolved['login_footer_links'] ?? ''));

        if ($heading === '' && $message === '' && $links === []) {
            return;
        }

        TemplateRenderer::getInstance()->display('@brandkit/login_extra.html.twig', [
            'heading' => $heading,
            'message' => $message !== '' ? RichText::getSafeHtml($message) : '',
            'links'   => $links,
        ]);
    }

    /**
     * Walk the entity tree until an override is found (inherit = 0).
     *
     * @return array<string, mixed>
     */
    public static function resolve(int $entities_id): array
    {
        $defaults = self::emptyFields();
        $defaults['entities_id'] = 0;
        $defaults['inherit'] = 0;

        $current = $entities_id;
        $guard   = 0;

        while ($current >= 0 && $guard < 64) {
            $guard++;
            $row = self::getRowForEntity($current);
            if ($row !== [] && (int) ($row['inherit'] ?? 1) === 0) {
                return $row + $defaults;
            }

            if ($current === 0) {
                return ($row !== [] ? $row : $defaults) + $defaults;
            }

            $entity = new Entity();
            if (!$entity->getFromDB($current)) {
                break;
            }

            $parent = $entity->fields['entities_id'];
            if ($parent === null || $parent === '' || (int) $parent < 0) {
                $current = 0;
                continue;
            }
            $current = (int) $parent;
        }

        return $defaults;
    }

    /**
     * Login page: hostname mapping, otherwise the root entity.
     *
     * @return array<string, mixed>
     */
    public static function resolveForLogin(?string $host = null): array
    {
        $host = $host ?? self::requestHost();
        $map  = self::getHostEntityMap();
        $id   = 0;

        if ($host !== '' && isset($map[$host])) {
            $id = (int) $map[$host];
        }

        return self::resolve($id);
    }

    /**
     * Active session entity after login; host/root before login.
     *
     * @return array<string, mixed>
     */
    public static function resolveForRequest(): array
    {
        if (isset($_SESSION['glpiactive_entity'])) {
            return self::resolve((int) $_SESSION['glpiactive_entity']);
        }

        return self::resolveForLogin();
    }

    /**
     * @return list<string>
     */
    public static function dashboardPaletteForRequest(): array
    {
        $resolved = self::resolveForRequest();

        return self::paletteList($resolved);
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string>
     */
    public static function paletteList(array $row): array
    {
        $raw = trim((string) ($row['dashboard_palette'] ?? ''));
        if ($raw === '') {
            $colors = [];
            foreach (['color_primary', 'color_secondary', 'color_accent', 'color_sidebar', 'color_header'] as $field) {
                $hex = CssSanitizer::normalizeHexColor($row[$field] ?? null);
                if ($hex !== null) {
                    $colors[] = $hex;
                }
            }

            return array_values(array_unique($colors));
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $color) {
            $hex = CssSanitizer::normalizeHexColor(is_string($color) ? $color : null);
            if ($hex !== null) {
                $out[] = $hex;
            }
        }

        return $out;
    }

    public static function storageDirectory(): string
    {
        return (new AssetStorage())->getBaseDir();
    }

    public static function purgeStoredFiles(): void
    {
        (new AssetStorage())->purgeAll();
    }

    /**
     * Public URL for an image slot on a resolved branding row.
     *
     * @param array<string, mixed> $row
     */
    public static function assetUrlFromRow(array $row, string $slot): ?string
    {
        $filename = trim((string) ($row[$slot] ?? ''));
        if ($filename === '' || !AssetStorage::isAllowedSlot($slot)) {
            return null;
        }

        $entities_id = (int) ($row['entities_id'] ?? 0);
        $storage     = new AssetStorage();
        if ($storage->absolutePath($entities_id, $filename) === null) {
            return null;
        }

        return Html::getPrefixedUrl('/plugins/brandkit/Asset/' . $slot)
            . '?e=' . $entities_id
            . '&v=' . rawurlencode((string) ($row['date_mod'] ?? PLUGIN_BRANDKIT_VERSION));
    }

    /**
     * Absolute URL (scheme + host) for emails.
     *
     * @param array<string, mixed> $row
     */
    public static function absoluteAssetUrlFromRow(array $row, string $slot): ?string
    {
        global $CFG_GLPI;

        $relative = self::assetUrlFromRow($row, $slot);
        if ($relative === null) {
            return null;
        }

        if (preg_match('#^https?://#i', $relative) === 1) {
            return $relative;
        }

        $base = rtrim((string) ($CFG_GLPI['url_base'] ?? ''), '/');
        if ($base === '') {
            return $relative;
        }

        return $base . $relative;
    }

    /**
     * @return array<string, int> hostname => entities_id
     */
    public static function getHostEntityMap(): array
    {
        $raw = (string) (Config::getConfigurationValue(self::CONFIG_CONTEXT, 'host_entity_map') ?? '');

        return self::parseHostMap($raw);
    }

    public static function parseHostMap(string $raw): array
    {
        $map = [];
        foreach (preg_split("/\r\n|\n|\r/", $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$host, $id] = array_map('trim', explode('=', $line, 2));
            $host = strtolower($host);
            if ($host === '' || !preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
                continue;
            }
            $map[$host] = (int) $id;
        }

        return $map;
    }

    public static function requestHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $host = strtolower(preg_replace('/:\d+$/', '', (string) $host) ?? '');

        return $host;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getRowForEntity(int $entities_id): array
    {
        global $DB;

        if (!($DB instanceof DBmysql) || !$DB->tableExists(self::getTable())) {
            return [];
        }

        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['entities_id' => $entities_id],
            'LIMIT' => 1,
        ]);

        foreach ($iterator as $row) {
            return $row;
        }

        return [];
    }

    /**
     * Persist (insert or update) branding for an entity from a POST payload + $_FILES.
     *
     * @param array<string, mixed> $input
     */
    public function saveFromForm(array $input): bool
    {
        $entities_id = (int) ($input['entities_id'] ?? -1);
        if ($entities_id < 0 || !Session::haveAccessToEntity($entities_id)) {
            Session::addMessageAfterRedirect(
                __('You do not have permission to update this entity.', 'brandkit'),
                true,
                ERROR
            );
            return false;
        }

        if (!self::canUpdate()) {
            Session::addMessageAfterRedirect(
                __('You do not have permission to update branding.', 'brandkit'),
                true,
                ERROR
            );
            return false;
        }

        $prepared = $this->prepareBrandingInput($input, $entities_id);
        $existing = self::getRowForEntity($entities_id);

        if ($existing === []) {
            $prepared['entities_id'] = $entities_id;
            $prepared['date_creation'] = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
            $id = $this->add($prepared);
            return $id !== false;
        }

        $prepared['id'] = (int) $existing['id'];
        $this->getFromDB($prepared['id']);

        return $this->update($prepared);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function prepareBrandingInput(array $input, int $entities_id): array
    {
        $is_root = $entities_id === 0;
        $inherit = $is_root ? 0 : (isset($input['inherit']) ? (int) (bool) $input['inherit'] : 0);

        $out = [
            'entities_id'      => $entities_id,
            'inherit'          => $inherit,
            'email_embed_logo' => isset($input['email_embed_logo']) ? (int) (bool) $input['email_embed_logo'] : 0,
            'date_mod'         => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
        ];

        foreach (self::COLOR_FIELDS as $field) {
            $out[$field] = CssSanitizer::normalizeHexColor(isset($input[$field]) ? (string) $input[$field] : null);
        }

        if (!empty($input['style_import_json']) && is_string($input['style_import_json'])) {
            $imported = StyleSchema::import($input['style_import_json']);
            if ($imported['ok']) {
                $input = self::mergeImported($input, $imported['data']);
            } else {
                Session::addMessageAfterRedirect(htmlescape($imported['error']), false, ERROR);
            }
        }

        $preset_key = (string) ($input['apply_preset'] ?? '');
        if ($preset_key !== '' && isset(StyleSchema::presets()[$preset_key])) {
            $input['style'] = StyleSchema::presets()[$preset_key]['style'];
        }

        $out['style_json'] = StyleSchema::encode($input['style'] ?? []);

        $out['custom_css'] = CssSanitizer::sanitize((string) ($input['custom_css'] ?? ''));
        $out['login_heading'] = mb_substr(trim((string) ($input['login_heading'] ?? '')), 0, 255);
        $out['login_message'] = RichText::getSafeHtml((string) ($input['login_message'] ?? ''));
        $out['login_footer_links'] = self::normaliseFooterLinksInput((string) ($input['login_footer_links'] ?? ''));
        $out['product_name'] = mb_substr(trim((string) ($input['product_name'] ?? '')), 0, 255);
        $out['page_title_prefix'] = mb_substr(trim((string) ($input['page_title_prefix'] ?? '')), 0, 255);
        $out['email_header_html'] = RichText::getSafeHtml((string) ($input['email_header_html'] ?? ''));
        $out['email_footer_html'] = RichText::getSafeHtml((string) ($input['email_footer_html'] ?? ''));
        $out['dashboard_palette'] = self::paletteFromInput($input);

        $existing = self::getRowForEntity($entities_id);
        $storage  = new AssetStorage();

        foreach (self::IMAGE_SLOTS as $slot) {
            $current = $existing[$slot] ?? null;
            if (!empty($input['_blank_' . $slot])) {
                if (is_string($current) && $current !== '') {
                    $storage->delete($entities_id, $current);
                }
                $out[$slot] = null;
                continue;
            }

            $file = $_FILES[$slot] ?? null;
            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $stored = $storage->store($entities_id, $slot, $file);
                if ($stored['ok']) {
                    if (is_string($current) && $current !== '') {
                        $storage->delete($entities_id, $current);
                    }
                    $out[$slot] = $stored['filename'];
                } else {
                    Session::addMessageAfterRedirect(htmlescape($stored['error']), false, ERROR);
                    $out[$slot] = $current;
                }
                continue;
            }

            $out[$slot] = $current;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $input
     */
    private static function paletteFromInput(array $input): string
    {
        $colors = [];
        if (isset($input['dashboard_palette']) && is_array($input['dashboard_palette'])) {
            foreach ($input['dashboard_palette'] as $color) {
                $hex = CssSanitizer::normalizeHexColor(is_string($color) ? $color : null);
                if ($hex !== null) {
                    $colors[] = $hex;
                }
            }
        }

        return $colors === [] ? '' : json_encode($colors, JSON_THROW_ON_ERROR);
    }

    public static function parseFooterLinks(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $links = [];
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $label = trim((string) ($row['label'] ?? ''));
                $url   = trim((string) ($row['url'] ?? ''));
                if ($label === '' || $url === '' || !preg_match('#^https?://#i', $url)) {
                    continue;
                }
                $links[] = ['label' => $label, 'url' => $url];
            }

            return $links;
        }

        $links = [];
        foreach (preg_split("/\r\n|\n|\r/", $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, '|')) {
                continue;
            }
            [$label, $url] = array_map('trim', explode('|', $line, 2));
            if ($label === '' || !preg_match('#^https?://#i', $url)) {
                continue;
            }
            $links[] = ['label' => $label, 'url' => $url];
        }

        return $links;
    }

    public static function footerLinksToText(string $raw): string
    {
        $lines = [];
        foreach (self::parseFooterLinks($raw) as $link) {
            $lines[] = $link['label'] . '|' . $link['url'];
        }

        return implode("\n", $lines);
    }

    private static function normaliseFooterLinksInput(string $raw): string
    {
        $links = [];
        foreach (preg_split("/\r\n|\n|\r/", $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, '|')) {
                continue;
            }
            [$label, $url] = array_map('trim', explode('|', $line, 2));
            $label = mb_substr($label, 0, 128);
            if ($label === '' || !preg_match('#^https?://#i', $url)) {
                continue;
            }
            $links[] = ['label' => $label, 'url' => $url];
        }

        return $links === [] ? '' : json_encode($links, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $imported
     * @return array<string, mixed>
     */
    private static function mergeImported(array $input, array $imported): array
    {
        if (isset($imported['style']) && is_array($imported['style'])) {
            $input['style'] = $imported['style'];
        }
        if (isset($imported['colors']) && is_array($imported['colors'])) {
            foreach (self::COLOR_FIELDS as $field) {
                if (array_key_exists($field, $imported['colors'])) {
                    $input[$field] = $imported['colors'][$field];
                }
            }
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
            if (isset($imported[$key])) {
                $input[$key] = $imported[$key];
            }
        }
        if (isset($imported['dashboard_palette']) && is_string($imported['dashboard_palette'])) {
            $decoded = json_decode($imported['dashboard_palette'], true);
            if (is_array($decoded)) {
                $input['dashboard_palette'] = $decoded;
            }
        }

        return $input;
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyFields(): array
    {
        $fields = [
            'id'                      => 0,
            'entities_id'             => 0,
            'inherit'                 => 1,
            'custom_css'              => '',
            'login_heading'           => '',
            'login_message'           => '',
            'login_footer_links'      => '',
            'product_name'            => '',
            'page_title_prefix'       => '',
            'email_header_html'       => '',
            'email_footer_html'       => '',
            'email_embed_logo'        => 1,
            'dashboard_palette'       => '',
            'style_json'              => StyleSchema::encode(StyleSchema::defaults()),
            'date_mod'                => null,
            'date_creation'           => null,
        ];

        foreach (self::IMAGE_SLOTS as $slot) {
            $fields[$slot] = null;
        }
        foreach (self::COLOR_FIELDS as $field) {
            $fields[$field] = null;
        }

        return $fields;
    }
}
