<?php

/**
 * Export an entity's branding (colours + style JSON, no uploaded files).
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
use GlpiPlugin\Brandkit\Branding;
use GlpiPlugin\Brandkit\StyleSchema;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExportController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_CENTRAL_ACCESS)]
    #[Route("/Export", name: "brandkit_export", methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        if (!Session::haveRight(Branding::$rightname, READ)) {
            throw new AccessDeniedHttpException();
        }

        $entities_id = $request->query->getInt('e', 0);
        if (!Session::haveAccessToEntity($entities_id)) {
            throw new AccessDeniedHttpException();
        }

        $row   = Branding::resolve($entities_id);
        $style = StyleSchema::fromRow($row);
        $json  = json_encode(
            StyleSchema::export($row, $style),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        return new Response($json, 200, [
            'Content-Type'        => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="brandkit-entity-' . $entities_id . '.json"',
        ]);
    }
}
