# Changelog

All notable changes to BrandKit are documented here.

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
