<?php

/**
 * CSS for gallery try-it mode (not persisted).
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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Brandkit\AssetStorage;
use GlpiPlugin\Brandkit\Branding;
use GlpiPlugin\Brandkit\CssGenerator;
use GlpiPlugin\Brandkit\StyleSchema;
use GlpiPlugin\Brandkit\TemplateCatalog;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PreviewCssController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_CENTRAL_ACCESS)]
    #[Route("/PreviewCss", name: "brandkit_preview_css", methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        if (!Session::haveRight(Branding::$rightname, READ)) {
            throw new AccessDeniedHttpException();
        }

        $template_id = trim((string) $request->query->get('template', $request->request->get('template', '')));
        $payload = null;

        if ($template_id !== '') {
            $payload = TemplateCatalog::get($template_id);
        } else {
            $raw = $request->getContent();
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $payload = TemplateCatalog::normalize($decoded, true);
                }
            }
        }

        if ($payload === null) {
            return new Response('/* unknown template */', 404, ['Content-Type' => 'text/css; charset=UTF-8']);
        }

        $resolved = Branding::resolveForRequest();
        $branding = $resolved;
        foreach ($payload['colors'] as $key => $hex) {
            if ($hex) {
                $branding[$key] = $hex;
            }
        }
        $branding['style_json'] = StyleSchema::encode($payload['style']);

        $urls = [];
        foreach (AssetStorage::IMAGE_SLOTS as $slot) {
            $urls[$slot] = Branding::assetUrlFromRow($resolved, $slot);
        }

        $css = CssGenerator::generate($branding, $urls);

        return new Response($css, 200, [
            'Content-Type'  => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
