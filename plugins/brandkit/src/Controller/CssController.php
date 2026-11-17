<?php

/**
 * Dynamically generated branding stylesheet.
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Brandkit\AssetStorage;
use GlpiPlugin\Brandkit\Branding;
use GlpiPlugin\Brandkit\CssGenerator;
use Plugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CssController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    #[Route("/Css", name: "brandkit_css", methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        if (!Plugin::isPluginActive('brandkit')) {
            return new Response('/* plugin disabled */', 200, ['Content-Type' => 'text/css; charset=UTF-8']);
        }

        $resolved = Branding::resolveForRequest();
        $urls     = [];
        foreach (AssetStorage::IMAGE_SLOTS as $slot) {
            $urls[$slot] = Branding::assetUrlFromRow($resolved, $slot);
        }

        $css = CssGenerator::generate($resolved, $urls);
        $etag = '"' . sha1(($resolved['date_mod'] ?? '') . "\n" . $css) . '"';
        if ($request->headers->get('If-None-Match') === $etag) {
            return new Response('', 304, [
                'Cache-Control' => 'private, no-cache, must-revalidate',
                'ETag'          => $etag,
            ]);
        }

        return new Response($css, 200, [
            'Content-Type'  => 'text/css; charset=UTF-8',
            'Cache-Control' => 'private, no-cache, must-revalidate',
            'ETag'          => $etag,
        ]);
    }
}
