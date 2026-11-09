<?php

/**
 * Serve stored branding images with path-traversal protection.
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
use Plugin;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AssetController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    #[Route("/Asset/{slot}", name: "brandkit_asset", methods: ['GET'])]
    public function __invoke(Request $request, string $slot): Response
    {
        if (!Plugin::isPluginActive('brandkit')) {
            return new Response('Plugin disabled', 403);
        }

        if (!AssetStorage::isAllowedSlot($slot)) {
            return new Response('Unknown asset', 404);
        }

        $entities_id = $request->query->getInt('e', -1);
        if ($entities_id < 0) {
            $resolved    = Branding::resolveForRequest();
            $entities_id = (int) ($resolved['entities_id'] ?? 0);
        }

        $row = Branding::getRowForEntity($entities_id);
        if ($row === []) {
            $row = Branding::resolve($entities_id);
        }

        $filename = (string) ($row[$slot] ?? '');
        $storage  = new AssetStorage();
        $path     = $storage->absolutePath((int) ($row['entities_id'] ?? $entities_id), $filename);

        if ($path === null) {
            return new Response('Not found', 404);
        }

        $mime = @mime_content_type($path) ?: 'application/octet-stream';
        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->headers->set('Content-Disposition', 'inline; filename="' . basename($path) . '"');

        return $response;
    }
}
