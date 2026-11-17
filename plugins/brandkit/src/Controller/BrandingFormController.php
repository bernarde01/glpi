<?php

/**
 * Save per-entity branding and return to the Entity form tab.
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit\Controller;

use Entity;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Http\RedirectResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Brandkit\Branding;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BrandingFormController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_CENTRAL_ACCESS)]
    #[Route("/Branding", name: "brandkit_branding", methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        if (!Session::haveRight(Branding::$rightname, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $input = $request->request->all();
        $this->hydrateUploadedFiles($request);
        $import = $request->files->get('style_import_file');
        if ($import !== null && is_object($import) && $import->getError() === UPLOAD_ERR_OK) {
            $raw = @file_get_contents($import->getPathname());
            if (is_string($raw) && $raw !== '') {
                $input['style_import_json'] = $raw;
            }
        }

        $branding = new Branding();
        $branding->saveFromForm($input);

        $entities_id = (int) ($input['entities_id'] ?? 0);
        $url = Entity::getFormURLWithID($entities_id)
            . '&forcetab=' . rawurlencode(Branding::class . '$1');

        return new RedirectResponse($url);
    }

    private function hydrateUploadedFiles(Request $request): void
    {
        foreach ($request->files->all() as $key => $file) {
            if ($file === null) {
                continue;
            }
            if (is_array($file)) {
                continue;
            }
            if (!$file->isValid() && $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $_FILES[$key] = [
                'name'     => $file->getClientOriginalName(),
                'type'     => $file->getClientMimeType() ?: '',
                'tmp_name' => $file->getPathname(),
                'error'    => $file->getError(),
                'size'     => $file->getSize(),
            ];
        }
    }
}
