<?php

/**
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit\Command;

use Glpi\Application\Environment;
use Glpi\Console\AbstractCommand;
use GlpiPlugin\Brandkit\DistillectBranding;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class SeedDistillectBrandingCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('plugins:brandkit:seed-distillect');
        $this->setDescription('Import Distillect logo_D assets (colours unchanged unless --with-preset)');
        $this->addOption(
            'username',
            'u',
            InputOption::VALUE_REQUIRED,
            'GLPI user for the session (default: glpi)',
            'glpi'
        );
        $this->addOption(
            'entity',
            null,
            InputOption::VALUE_REQUIRED,
            'Entity id (default: 0 = root)',
            '0'
        );
        $this->addOption(
            'images-dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Folder containing logo_D*.png (default: DISTILLECT_IMAGES env or /var/distillect-images)'
        );
        $this->addOption(
            'keep-logos',
            null,
            InputOption::VALUE_NONE,
            'Do not replace logos that are already set'
        );
        $this->addOption(
            'with-preset',
            null,
            InputOption::VALUE_NONE,
            'Also apply Distillect gallery colours and Style options (overwrites current look)'
        );
        $this->addOption(
            'restore-clean-cards',
            null,
            InputOption::VALUE_NONE,
            'Only restore Clean cards colours/style; do not import images'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (Environment::get() === Environment::PRODUCTION) {
            $output->writeln('<error>Refusing to run in production. Use a development stack.</error>');

            return 1;
        }

        $this->loadUserSession((string) $input->getOption('username'));

        $imagesDir = $input->getOption('images-dir');
        if (!is_string($imagesDir) || $imagesDir === '') {
            $imagesDir = getenv('DISTILLECT_IMAGES') ?: '';
        }
        if ($imagesDir === '' && is_dir('/var/distillect-images')) {
            $imagesDir = '/var/distillect-images';
        }
        if ($imagesDir === '') {
            $output->writeln('<error>Pass --images-dir or set DISTILLECT_IMAGES to your Distillect Images folder.</error>');

            return 1;
        }

        $entities_id = (int) $input->getOption('entity');

        if ($input->getOption('restore-clean-cards')) {
            $result = DistillectBranding::applyCleanCardsPreset($entities_id);
        } else {
            $result = DistillectBranding::apply(
                $entities_id,
                $imagesDir,
                !$input->getOption('keep-logos'),
                (bool) $input->getOption('with-preset')
            );
        }

        if ($result['ok'] === false) {
            $output->writeln('<error>' . $result['error'] . '</error>');

            return 1;
        }

        foreach ($result['lines'] as $line) {
            $output->writeln('<info>' . $line . '</info>');
        }

        $output->writeln('');
        $output->writeln('<comment>Hard-refresh the browser. Check Administration → Entities → Root → Branding.</comment>');

        return 0;
    }
}