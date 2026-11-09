# BrandKit

**BrandKit** is an independent GPL-3.0-or-later branding plugin for [GLPI](https://glpi-project.org/) 12.

It is developed by [Distillect](https://glpi.distillect.co.za/), a GLPI partner in South Africa. The official GLPI Network branding plugin is only available to Network subscribers; BrandKit is a self-hosted alternative that lives under the plugin key `brandkit` (namespace `GlpiPlugin\Brandkit`) so it cannot clash with the official `branding` plugin.

Requires **GLPI 12.0.x** and **PHP 8.3+**.

## Features

1. **Logos** — main sidebar, collapsed sidebar, login page, optional dark-mode login logo. Images are validated (type and 2 MiB size), stored under `GLPI_PLUGIN_DOC_DIR/brandkit/`, and served by `/plugins/brandkit/Asset/{slot}` with path-traversal checks. Missing logos fall back to GLPI defaults.
2. **Theme colours and custom CSS** — primary, secondary, accent, sidebar and header colour pickers. BrandKit emits CSS variables that override GLPI 12’s Tabler/Bootstrap palette (`--tblr-primary`, `--glpi-mainmenu-bg`, …). A free-form CSS field is sanitised (no `</style>` breakouts, no `javascript:` / `<script>`).
3. **Login page** — background image or colour, heading and message (coexists with core `text_login`), optional footer links (`Label|https://…` per line).
4. **Per-entity branding** — settings attach to entities. Child entities inherit from the parent until they override (GLPI’s usual inheritance pattern). After login, the **active entity** applies. The login page uses the **root entity**, or a hostname → entity map (plugin config page).
5. **Branded notification emails** — HTML mail is wrapped with the entity’s logo (absolute URL), colours, and optional header/footer HTML via `pre_item_add` on `QueuedNotification`.
6. **Favicon and page title** — favicon upload; product name replaces “GLPI” in `app_name` / the browser title; optional title prefix. Core still emits a hardcoded shortcut icon, so BrandKit rewrites it in JavaScript after load.
7. **Dashboard styling** — registers a `brandkit` chart palette (and overrides core `tab10` so existing widgets pick it up) plus optional card background/text colours.
8. **Style layer** — structured restyling (menus, radius, shadows, motion, density, fonts, gradients, watermarks) generated as CSS variables + shallow selectors. One-click presets, JSON export/import, and a live preview on the Entity tab.

### Style presets

| Preset | Look |
| --- | --- |
| Corporate clean | Flat header, modest radius, soft card shadow, system font |
| Soft rounded | Pill nav, large radii, pill buttons, Nunito, header gradient, lift on hover |
| Glass | Blurred sidebar, transparent header, glow hover, Inter |
| Bold gradient | Header/sidebar/button/login gradients, glow elevation, IBM Plex, spacious |
| Minimal flat | Zero radius, no shadows, compact density, animations off |

### CSS selectors the Style layer targets

Kept shallow so they survive GLPI 12 updates. Prefer Tabler/GLPI variables (`--tblr-border-radius`, `--tblr-box-shadow`, `--tblr-font-sans-serif`, `--tblr-body-font-size`, `--tblr-card-spacer-*`) plus:

- Navigation: `.sidebar`, `.navbar.navbar-vertical`, `.topbar`, `#navbar-menu .nav-link`, `#navbar-menu .nav-item`
- Header: `header.navbar`, `header.navbar.navbar-light`, `header.navbar.topbar`
- Surfaces: `.card`, `.modal-content`, `.dropdown-menu`, `.kanban-column`, `.kanban-item`
- Controls: `.btn`, `.btn-primary`, `.form-control`, `.form-select`, `.select2-selection`
- Data: `.table`, `table.table`, `.dashboard .grid-stack-item-content .card`
- Chrome: `body`, `.page`, `.page-wrapper`, `body.welcome-anonymous`

Dark mode uses `html[data-glpi-theme-dark="1"]` and `color-mix` against `--tblr-body-color` / `--tblr-bg-surface`. Motion respects `prefers-reduced-motion` and a per-entity disable switch.

## Screenshots

Place captures in `docs/screenshots/` when packaging a release:

| | |
| --- | --- |
| Login page | `docs/screenshots/login.png` |
| Sidebar + colours | `docs/screenshots/sidebar.png` |
| Entity Branding tab | `docs/screenshots/entity-tab.png` |
| Dashboard charts | `docs/screenshots/dashboard.png` |
| Notification HTML | `docs/screenshots/email.png` |
| Style: Corporate clean | `docs/screenshots/style-corporate-dashboard.png` |
| Style: Glass (dashboard + ticket) | `docs/screenshots/style-glass-dashboard.png`, `docs/screenshots/style-glass-ticket.png` |
| Style: Bold gradient login | `docs/screenshots/style-bold-login.png` |
| Style: Soft rounded | `docs/screenshots/style-soft-dashboard.png` |
| Style presets + live preview | `docs/screenshots/style-presets.png`, `docs/screenshots/style-preview.png` |

## Install

### From this GLPI tree (development)

```bash
# plugin already lives at plugins/brandkit/
php bin/console plugin:install brandkit -u glpi
php bin/console plugin:activate brandkit
php bin/console plugin:list
```

`make install` / `make enable` work from `plugins/brandkit` when GLPI’s `PluginsMakefile.mk` is in use.

### On a GLPI 12.0.1 server (tarball)

1. Build a tarball of **this directory only** (not the whole GLPI core):

   ```bash
   cd plugins
   tar czf brandkit-1.1.0.tar.gz \
     --exclude='tests' \
     --transform 's,^brandkit,brandkit,' \
     brandkit
   ```

   The archive **must** unpack to a folder named `brandkit` (the plugin key).

2. Copy it onto the server and extract into GLPI’s plugins directory (or upload via **Setup → Plugins** / the marketplace drop-in folder):

   ```bash
   tar xzf brandkit-1.1.0.tar.gz -C /var/www/glpi/plugins/
   cd /var/www/glpi
   php bin/console plugin:install brandkit -u glpi
   php bin/console plugin:activate brandkit
   ```

3. Log in as Super-Admin. Other profiles get `plugin_brandkit_config` at 0 until you enable it on **Setup → Profiles → Branding**.

4. Open **Administration → Entities → Root entity → Branding** (or **Setup → Plugins → BrandKit** for the hostname map).

### After install

1. Set logos, colours and login copy on the **root entity**.
2. Optionally override a **child entity**.
3. Optionally map hostnames on the plugin config page so `client.example.com` shows that entity’s login branding.
4. Create or edit a dashboard: chart widgets use the BrandKit palette (core `tab10` is replaced when brand colours are set).

## Rights

| Right | Meaning |
| --- | --- |
| `plugin_brandkit_config` | View / update BrandKit settings (entity tab and plugin config page). Granted to profiles that already have Setup → General `config` UPDATE (typically Super-Admin) at install. |

## Tests

```bash
# Unit tests (no GLPI database)
php vendor/bin/phpunit -c plugins/brandkit/phpunit.xml

# Functional smoke tests (GLPI testing env + plugin installed)
php vendor/bin/phpunit -c phpunit.xml.dist plugins/brandkit/tests/functional
```

## Known limitations

- The browser title and favicon are hardcoded in GLPI’s `templates/layout/parts/head.html.twig`. BrandKit sets `$CFG_GLPI['app_name']`, injects a second `<link rel="icon">`, and uses JavaScript to replace the core shortcut icon. A core hook (or making those Twig values overridable) would be cleaner.
- Theme CSS is registered with `ADD_CSS` / `ADD_CSS_ANONYMOUS_PAGE` (after core Tabler/GLPI styles) and uses `!important` on CSS variables so `--glpi-logo*` and `--tblr-primary` win. Header-tag stylesheets would load too early and be overwritten.
- Email logos are linked by **absolute URL** (`url_base` + asset controller). CID embedding of plugin files is not hooked into `NotificationEventMailing` (that path only inlines `document.send.php` images).
- Login extras render in core’s `DISPLAY_LOGIN` right-hand column, not inside the copyright footer.
- No hook exists to replace GLPI’s default logos without CSS variables; BrandKit overrides `--glpi-logo*` which is the supported approach.
- Style CSS is generated only after an entity saves `style_json` (picking a preset and saving). Until then, GLPI’s Tabler defaults remain.
- Glass/blur is subtle on GLPI’s solid sidebar colour; it is more visible with a page background image.
- Watermarks are injected by JavaScript (`div.brandkit-watermark`) because there is no core template hook for a background layer.
- Dropdown `select2` skins and some TinyMCE/kanban internals use their own radii; BrandKit covers `.select2-selection` and `.kanban-item` but not every nested widget.

## Licence

GNU General Public License v3.0 or later. See [LICENSE](LICENSE).
