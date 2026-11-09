<?php

/**
 * Save / delete custom gallery templates.
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
use GlpiPlugin\Brandkit\StyleTemplate;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TemplateController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_CENTRAL_ACCESS)]
    #[Route("/Template", name: "brandkit_template", methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        if (!Session::haveRight(Branding::$rightname, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $action = (string) $request->request->get('action', '');
        if ($action === '' && $request->headers->get('Content-Type') !== null
            && str_contains((string) $request->headers->get('Content-Type'), 'json')
        ) {
            $decoded = json_decode((string) $request->getContent(), true);
            if (is_array($decoded)) {
                $action = (string) ($decoded['action'] ?? 'save');
                foreach ($decoded as $k => $v) {
                    $request->request->set($k, $v);
                }
            }
        }

        if ($action === 'delete') {
            $id = $request->request->getInt('id', 0);
            $tpl = new StyleTemplate();
            if ($id <= 0 || !$tpl->getFromDB($id)) {
                return new JsonResponse(['ok' => false, 'error' => 'Not found'], 404);
            }
            $ok = $tpl->delete(['id' => $id]);

            return new JsonResponse(['ok' => (bool) $ok]);
        }

        $raw = $request->getContent();
        $input = $request->request->all();
        if (is_string($raw) && $raw !== '' && str_contains((string) $request->headers->get('Content-Type'), 'json')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }

        $result = StyleTemplate::saveFromInput($input);
        $status = $result['ok'] ? 200 : 400;

        return new JsonResponse($result, $status);
    }
}
