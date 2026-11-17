<?php

/**
 * Plugin configuration page (Setup > Plugins > BrandKit).
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit\Controller;

use Config;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Http\RedirectResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Brandkit\Branding;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConfigController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_CENTRAL_ACCESS)]
    #[Route("/Config", name: "brandkit_config", methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        Session::checkRight(Branding::$rightname, READ);

        if ($request->isMethod('POST')) {
            if (!Session::haveRight(Branding::$rightname, UPDATE)) {
                throw new AccessDeniedHttpException();
            }

            $map = (string) $request->request->get('host_entity_map', '');
            Config::setConfigurationValues(Branding::CONFIG_CONTEXT, [
                'host_entity_map' => $map,
            ]);
            Session::addMessageAfterRedirect(__('Configuration saved.', 'brandkit'));

            return new RedirectResponse(Html::getPrefixedUrl('/plugins/brandkit/Config'));
        }

        $raw = (string) (Config::getConfigurationValue(Branding::CONFIG_CONTEXT, 'host_entity_map') ?? '');

        ob_start();
        Html::header(
            Branding::getTypeName(1),
            Html::getPrefixedUrl('/plugins/brandkit/Config'),
            'config',
            'plugins'
        );
        TemplateRenderer::getInstance()->display('@brandkit/config.html.twig', [
            'host_entity_map' => $raw,
            'can_edit'        => Session::haveRight(Branding::$rightname, UPDATE),
            'form_url'        => Html::getPrefixedUrl('/plugins/brandkit/Config'),
            'root_entity_url' => Entity::getFormURLWithID(0) . '&forcetab=' . rawurlencode(Branding::class . '$1'),
        ]);
        Html::footer();

        return new Response((string) ob_get_clean());
    }
}
