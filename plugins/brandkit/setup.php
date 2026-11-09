<?php

/**
 * ---------------------------------------------------------------------
 *
 * BrandKit — GPL branding plugin for GLPI 12.
 * Copyright (C) 2026 Distillect
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of BrandKit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Http\SessionManager;
use Glpi\Plugin\Hooks;
use GlpiPlugin\Brandkit\Branding;
use GlpiPlugin\Brandkit\MailBranding;
use GlpiPlugin\Brandkit\Profile as BrandkitProfile;
use GlpiPlugin\Brandkit\StyleSchema;

define('PLUGIN_BRANDKIT_VERSION', '1.2.1');

/**
 * Plugin metadata consumed by Plugin::getInfo() / Plugin::checkVersions().
 *
 * @return array<string, mixed>
 */
function plugin_version_brandkit(): array
{
    return [
        'name'         => 'BrandKit',
        'version'      => PLUGIN_BRANDKIT_VERSION,
        'author'       => '<a href="https://glpi.distillect.co.za/">Distillect</a>',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://glpi.distillect.co.za/',
        'requirements' => [
            'glpi' => [
                'min' => '12.0.0',
                'max' => '13.0.0',
            ],
            'php' => [
                'min' => '8.3.0',
            ],
        ],
    ];
}

/**
 * Early boot: PSR-4 autoload is already registered. Keep this stateless
 * (no DB, no session). Used for SessionManager::registerPluginStatelessPath().
 */
function plugin_brandkit_boot(): void
{
    SessionManager::registerPluginStatelessPath('brandkit', '#^/Asset/#');
    SessionManager::registerPluginStatelessPath('brandkit', '#^/Css#');
}

/**
 * Runtime registration. Only runs for activated plugins.
 */
function plugin_init_brandkit(): void
{
    global $CFG_GLPI, $DB, $PLUGIN_HOOKS;

    $plugin = new Plugin();
    if (!$plugin->isActivated('brandkit')) {
        return;
    }

    // CSRF is handled by the kernel in GLPI 12; do not send _glpi_csrf_token.

    Plugin::registerClass(Branding::class, [
        'addtabon' => [Entity::class],
    ]);

    Plugin::registerClass(BrandkitProfile::class, [
        'addtabon' => [Profile::class],
    ]);

    $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['brandkit'] = 'Config';
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['brandkit'] = ['js/brandkit.js', 'js/brandkit-style.js'];
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT_ANONYMOUS_PAGE]['brandkit'] = 'js/brandkit.js';
    // Load the dynamic theme stylesheet *after* core Tabler/GLPI CSS (header tags
    // render first and would otherwise lose the --glpi-logo* / --tblr-* overrides).
    $PLUGIN_HOOKS[Hooks::ADD_CSS]['brandkit'] = ['css/brandkit.css', 'Css'];
    $PLUGIN_HOOKS[Hooks::ADD_CSS_ANONYMOUS_PAGE]['brandkit'] = ['css/brandkit.css', 'Css'];
    $PLUGIN_HOOKS[Hooks::DISPLAY_LOGIN]['brandkit'] = [Branding::class, 'displayLoginExtras'];
    $PLUGIN_HOOKS[Hooks::DASHBOARD_PALETTES]['brandkit'] = 'plugin_brandkit_dashboard_palettes';
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['brandkit'] = [
        QueuedNotification::class => [MailBranding::class, 'onQueuedAdd'],
    ];

    if (!($DB instanceof DBmysql) || !$DB->connected || !$DB->tableExists(Branding::getTable())) {
        return;
    }

    try {
        $resolved = Branding::resolveForRequest();
    } catch (Throwable $e) {
        return;
    }

    $header_tags = [];

    $favicon_url = Branding::assetUrlFromRow($resolved, 'favicon');
    if ($favicon_url !== null) {
        $header_tags[] = [
            'tag'        => 'meta',
            'properties' => [
                'name'    => 'brandkit-icon',
                'content' => $favicon_url,
            ],
        ];
        $header_tags[] = [
            'tag'        => 'link',
            'properties' => [
                'rel'   => 'icon',
                'href'  => $favicon_url,
            ],
        ];
    }

    $product = trim((string) ($resolved['product_name'] ?? ''));
    $prefix  = trim((string) ($resolved['page_title_prefix'] ?? ''));
    if ($product !== '') {
        $CFG_GLPI['app_name'] = $product;
    }
    if ($prefix !== '') {
        $header_tags[] = [
            'tag'        => 'meta',
            'properties' => [
                'name'    => 'brandkit-title-prefix',
                'content' => $prefix,
            ],
        ];
    }

    $style = StyleSchema::fromRow($resolved);
    $font_url = (string) $style['font_url'];
    if ($font_url === '') {
        $font_url = StyleSchema::builtInFontUrl((string) $style['font_family']);
    }
    $font_url = StyleSchema::sanitizeFontUrl($font_url);
    if ($font_url !== '') {
        $header_tags[] = [
            'tag'        => 'link',
            'properties' => [
                'rel'  => 'stylesheet',
                'href' => $font_url,
            ],
        ];
    }
    if ((int) $style['disable_animations'] === 1) {
        $header_tags[] = [
            'tag'        => 'meta',
            'properties' => [
                'name'    => 'brandkit-no-motion',
                'content' => '1',
            ],
        ];
    }
    if ((string) $style['watermark_type'] !== 'none') {
        $header_tags[] = [
            'tag'        => 'meta',
            'properties' => [
                'name'    => 'brandkit-watermark',
                'content' => (string) $style['watermark_type'],
            ],
        ];
    }

    $PLUGIN_HOOKS[Hooks::ADD_HEADER_TAG]['brandkit'] = $header_tags;
    $PLUGIN_HOOKS[Hooks::ADD_HEADER_TAG_ANONYMOUS_PAGE]['brandkit'] = $header_tags;
}

function plugin_brandkit_check_prerequisites(): bool
{
    return true;
}

function plugin_brandkit_check_config(): bool
{
    return true;
}

/**
 * Install and upgrade share this function. Core re-runs it when state is NOTUPDATED.
 *
 * @param array<string, mixed> $params
 */
function plugin_brandkit_install(array $params = []): bool
{
    global $DB;

    $migration = new Migration(PLUGIN_BRANDKIT_VERSION);
    $table     = Branding::getTable();

    if (!$DB->tableExists($table)) {
        $DB->doQuery(
            <<<SQL
CREATE TABLE `$table` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `inherit` tinyint NOT NULL DEFAULT '1',
  `logo_main` varchar(255) DEFAULT NULL,
  `logo_collapsed` varchar(255) DEFAULT NULL,
  `logo_login` varchar(255) DEFAULT NULL,
  `logo_login_dark` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `watermark_image` varchar(255) DEFAULT NULL,
  `color_primary` varchar(16) DEFAULT NULL,
  `color_secondary` varchar(16) DEFAULT NULL,
  `color_accent` varchar(16) DEFAULT NULL,
  `color_sidebar` varchar(16) DEFAULT NULL,
  `color_header` varchar(16) DEFAULT NULL,
  `custom_css` mediumtext,
  `login_background_image` varchar(255) DEFAULT NULL,
  `app_background_image` varchar(255) DEFAULT NULL,
  `login_background_color` varchar(16) DEFAULT NULL,
  `login_heading` varchar(255) DEFAULT NULL,
  `login_message` text,
  `login_footer_links` text,
  `product_name` varchar(255) DEFAULT NULL,
  `page_title_prefix` varchar(255) DEFAULT NULL,
  `email_header_html` text,
  `email_footer_html` text,
  `email_embed_logo` tinyint NOT NULL DEFAULT '1',
  `dashboard_palette` text,
  `dashboard_card_bg` varchar(16) DEFAULT NULL,
  `dashboard_card_text` varchar(16) DEFAULT NULL,
  `style_json` mediumtext,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
SQL
        );
    }

    if ($DB->tableExists($table)) {
        $migration->addField($table, 'style_json', 'mediumtext', ['after' => 'dashboard_card_text']);
        $migration->addField($table, 'app_background_image', 'string', ['after' => 'login_background_image', 'null' => true]);
        $migration->addField($table, 'watermark_image', 'string', ['after' => 'favicon', 'null' => true]);
    }

    $now = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
    $existing = $DB->request([
        'FROM'  => $table,
        'WHERE' => ['entities_id' => 0],
        'LIMIT' => 1,
    ]);
    if (count($existing) === 0) {
        $DB->insert($table, [
            'entities_id'     => 0,
            'inherit'         => 0,
            'email_embed_logo' => 1,
            'date_mod'        => $now,
            'date_creation'   => $now,
        ]);
    }

    $dir = Branding::storageDirectory();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    // Defaults only for keys that do not exist yet. plugin:install is also the
    // upgrade path; overwriting would wipe the hostname map on every update.
    $config_defaults = [
        'host_entity_map' => '',
    ];
    $existing_config = Config::getConfigurationValues('plugin:brandkit', array_keys($config_defaults));
    $missing_config = [];
    foreach ($config_defaults as $name => $value) {
        if (!array_key_exists($name, $existing_config)) {
            $missing_config[$name] = $value;
        }
    }
    if ($missing_config !== []) {
        Config::setConfigurationValues('plugin:brandkit', $missing_config);
    }

    $tpl = \GlpiPlugin\Brandkit\StyleTemplate::getTable();
    if (!$DB->tableExists($tpl)) {
        $DB->doQuery(
            <<<SQL
CREATE TABLE `$tpl` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `template_json` mediumtext,
  `date_mod` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
SQL
        );
    }

    // Additive: only inserts the right on profiles that do not have it yet.
    $migration->addRight(Branding::$rightname, ALLSTANDARDRIGHT);
    $migration->executeMigration();

    return true;
}

function plugin_brandkit_uninstall(): bool
{
    global $DB;

    $table = Branding::getTable();
    if ($DB->tableExists($table)) {
        $DB->dropTable($table, true);
    }
    $tpl = \GlpiPlugin\Brandkit\StyleTemplate::getTable();
    if ($DB->tableExists($tpl)) {
        $DB->dropTable($tpl, true);
    }

    $DB->delete('glpi_profilerights', ['name' => Branding::$rightname]);
    $DB->delete('glpi_displaypreferences', ['itemtype' => Branding::class]);
    Config::deleteConfigurationValues('plugin:brandkit', ['host_entity_map']);

    Branding::purgeStoredFiles();

    return true;
}
