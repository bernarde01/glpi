<?php

/**
 * Profile tab so the plugin right is editable under Setup > Profiles.
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

use CommonGLPI;
use Profile as CoreProfile;
use Session;

class Profile extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        return Branding::getTypeName($nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!($item instanceof CoreProfile) || $item->fields['interface'] !== 'central') {
            return '';
        }

        return self::createTabEntry(
            Branding::getTypeName(Session::getPluralNumber()),
            0,
            $item::getType(),
            Branding::getIcon()
        );
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!($item instanceof CoreProfile)) {
            return false;
        }

        $canedit = CoreProfile::canUpdate();
        $rights  = [
            [
                'itemtype' => Branding::class,
                'label'    => Branding::getTypeName(Session::getPluralNumber()),
                'field'    => Branding::$rightname,
            ],
        ];

        $item->displayRightsChoiceMatrix($rights, [
            'canedit' => $canedit,
            'title'   => Branding::getTypeName(Session::getPluralNumber()),
        ]);

        return true;
    }
}
