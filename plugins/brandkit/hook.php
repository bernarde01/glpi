<?php

/**
 * Automatic and registered hooks for BrandKit.
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

use GlpiPlugin\Brandkit\Branding;

/**
 * Extra dashboard palettes. Core starts with null and merges returned arrays.
 *
 * @param mixed $palettes
 * @return array<string, list<string>>
 */
function plugin_brandkit_dashboard_palettes($palettes = null): array
{
    if (!is_array($palettes)) {
        $palettes = [];
    }

    try {
        $colors = Branding::dashboardPaletteForRequest();
    } catch (Throwable $e) {
        return $palettes;
    }

    if ($colors === []) {
        return $palettes;
    }

    $palettes['brandkit'] = $colors;
    // Existing core widgets default to `tab10`; override it so brand colours apply
    // without editing every dashboard card.
    $palettes['tab10'] = $colors;

    return $palettes;
}
