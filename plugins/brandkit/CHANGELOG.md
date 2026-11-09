# Changelog

All notable changes to BrandKit are documented here.

## 1.2.0 — 2026-11-09

### Fixed

- Style preset pills on the Entity Branding tab did nothing. GLPI 12 loads that tab over AJAX *after* plugin footer scripts run, and `brandkit-style.js` exited when `#brandkit-live-preview` was missing. Handlers are now delegated on `document` and re-init on `glpi.tab.loaded`.
- Apply now toggles GLPI’s hidden+checkbox pairs (so a later template can turn gradients and flags off) and clears colour fields the template leaves empty. Generated `/Css` is no longer cached for 5 minutes, so a newly saved look shows up immediately.

### Added

- **Template gallery** — 10 built-in looks as JSON files in `templates/gallery/`. Cards show a CSS mini-preview. Click/Try it injects CSS on the current page without saving; Apply copies values into the form; Save on the entity persists.
- Custom templates stored in `glpi_plugin_brandkit_templates` (name, description, JSON). Saving one adds a gallery card immediately (no page reload, so unsaved form edits stay). Deletable from the gallery. JSON import/export uses `kind=brandkit-template`; import can also be saved as a gallery template. Filling “Save current look as a template” and clicking entity Save is a no-JS fallback.

## 1.1.1 — 2026-11-09

### Fixed

- Logo (and other image) uploads no longer fail with “File is not a supported image”. PHP’s upload temp files have no extension (`/tmp/php…`), and `Document::isImage()` rejects those paths. BrandKit now checks the original filename extension plus file contents (`exif_imagetype` / magic bytes / `finfo` / `getimagesize`). SVG is not accepted (no safe sanitiser). Favicon may also be `.ico`.

## 1.1.0 — 2026-11-09

### Added

- **Style layer** — structured, per-entity visual restyling (inherited like the rest of BrandKit) that generates CSS variables and shallow GLPI/Tabler selectors. Not just a free-form CSS box.
- Sidebar presets: default, compact, pill, floating, glass/blur. Active/hover item styles (background, accent bar, scale, indent), icon size, item spacing, header (flat / gradient / transparent) and dropdown styling.
- Shape: global radius plus separate card, button, input and modal radii.
- Elevation: none / soft / medium / strong / coloured-glow shadows for cards, dropdowns, modals and the sidebar.
- Motion: hover lift / grow / glow, transition speed and easing, page/card fade-in, a global disable-animations switch, and `prefers-reduced-motion`.
- Density (compact / comfortable / spacious), safe web fonts (system, Inter, Source Sans 3, IBM Plex Sans, Nunito, or a Google Fonts URL) and base font size.
- Gradient builders (two or three stops + angle) for header, sidebar, buttons, login and page; app background image with fit, position and overlay.
- Image or text watermarks (opacity, size, position, tiling) on the app and/or login, with `pointer-events: none`.
- One-click presets: Corporate clean, Soft rounded, Glass, Bold gradient, Minimal flat.
- JSON export / import of an entity’s branding (colours + style + copy; not uploaded files).
- Live preview on the Entity Branding tab (sample menu, header, card, button, chart) and a simple contrast warning.

## 1.0.0 — 2026-11-09

### Added

- Per-entity branding tab on **Administration → Entities**, with parent inheritance.
- Logo uploads for the main sidebar, collapsed sidebar, login page, optional dark-mode login logo, and favicon.
- Theme colours (primary, secondary, accent, sidebar, header) that override GLPI 12 Tabler/Bootstrap CSS variables.
- Sanitised custom CSS field.
- Login heading, message, background image/colour, and footer links. Login branding uses the root entity, or a hostname → entity map.
- Product name (replaces “GLPI” in `app_name` / the browser title) and an optional title prefix.
- HTML notification wrapping: logo, colours, optional header/footer (per entity).
- Dashboard chart palette (`brandkit`, and override of core `tab10`) plus optional card colours.
- Plugin configuration page and a `plugin_brandkit_config` profile right (granted to Super-Admin at install).
- GPL-3.0-or-later licensing, English and Afrikaans translations.
