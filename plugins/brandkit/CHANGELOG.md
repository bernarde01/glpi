# Changelog

All notable changes to BrandKit are documented here.

## 1.3.1 — 2026-11-09

### Fixed

- **Clean cards vs GLPI 12 sidebar DOM.** Section entries are `button.nav-link.dropdown-toggle` and sub-items are `a.dropdown-item` inside an in-flow `.dropdown-menu`. Generic popup `.dropdown-menu` radius/shadow was painting a light card over **Administration**, so the label looked empty. Expanded sidebar menus are now static and transparent; the active section uses teal text/icon plus a left accent (no wash); the active sub-item (**Entities**) is an indented rounded tinted pill. Collapsed flyouts keep the popup look.
- **Header chrome.** Breadcrumb items no longer render as bordered boxes. **+ Add** is a padded teal pill with its label visible below the xxl breakpoint. Inset-dark search is a dark rounded field with a white icon (the search control is `.btn.btn-link`, which was inheriting the orange accent). The user chip shows the entity line (`d-none d-xl-block` is forced on) and a circular avatar.
- **Entity header at scroll 0.** `#navigationheader` (entity title + Actions + list/pagination) was sitting under the sticky navbar or collapsing into the tab column (the list-search icon looked like a tab). It is a visible bar above the cards; `.mt-n2` on `.main-header` is reset; tab content no longer clips the first logo title.
- **Card tab menu.** GLPI `_tabs.scss` `!important` left/right/bottom borders beat the 1.3.0 card preset. Higher-specificity resets remove those lines. The list is a white rounded card with a **Configuration** heading (`ul#tabspanel::before`). Active/hover items are rounded teal pills.
- **Section cards.** Colours is a full-width rounded row with the chevron on the right. Style / Login / other `fields.largeTitle` ribbons (accent-orange `--glpi-form-header-*`) are restyled as the same card rows when the accordion section preset is on. The page tone greys `#page` as well as `.page-body`.

## 1.3.0 — 2026-11-09

### Fixed

- Breadcrumb (Home / Administration / Entities) and the neighbouring **Add** button no longer inherit pill / 24px corners. Separators, alignment and backgrounds are restored; chrome radius is capped at 10px so large gallery templates do not break the header.
- Rounded buttons and the global search box get real padding. Input-groups and button-groups round **only the outer corners**; file inputs (`Choose File`) use the same chrome radius on `::file-selector-button`.
- Badges, dropdowns and pagination scale to the same chrome radius so Soft pastel / Midnight violet / Glass aurora no longer look broken on small controls.

### Added

- **In-page tab menu** (`#tabspanel` on Entity, Ticket, … forms): tab style (default / pill / underline / accent bar), item spacing, hover and active states. Gallery templates set a preset (Bold gradient uses accent bar).
- **Bundled fonts** — Inter, Poppins, Roboto, Open Sans, Montserrat, Lato, Nunito, Raleway, IBM Plex Sans and Source Sans 3 (latin 400/700 woff2 + OFL/Apache licence files) served from `/plugins/brandkit/fonts/`. Works offline and with a strict CSP. Google Fonts CDN is an **opt-in** (`Load this font from Google Fonts`). Custom `font-family` name remains available. Templates pick a font (Bold gradient / Warm teal → Poppins, Clean cards → Inter).
- **Form and chrome options** so gallery looks can match a design mockup without custom CSS: two-column form layout, accordion section cards, outlined or filled file inputs, dark/light inset search, ghost or pill breadcrumbs, grey or cream page tone, user-menu chip. In-page tabs gain **card** (separate rounded panel + “Configuration” heading) and **merge** (active tab joins the content) presets.
- **Branding tab layout** — logo rows are always two-column (label + helper | Choose file + recommended size | preview + Clear). Colours is a collapsible card. Native `Choose File` on other GLPI forms is restyled by the template.
- **Clean cards** and **Warm teal** gallery templates, matching Bernard’s two mockups (grey/Inter/card tabs vs cream/Poppins/filled file buttons).

## 1.2.1 — 2026-11-09

### Fixed

- `plugin:install` on an update no longer wipes the hostname-to-entity map (or other plugin config). Defaults are written only for keys that do not exist yet. Branding rows, custom templates, uploaded files and profile rights were already additive and stay that way.

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
