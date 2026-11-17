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

use Glpi\Console\AbstractCommand;
use Glpi\Application\Environment;
use GlpiPlugin\Brandkit\DemoDataSeeder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class SeedDemoDataCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('plugins:brandkit:seed-demo');
        $this->setDescription('Insert BK-DEMO sample data for local UI / BrandKit styling review');
        $this->addOption(
            'username',
            'u',
            InputOption::VALUE_REQUIRED,
            'GLPI user for the session (default: glpi)',
            'glpi'
        );
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Re-run seeding (reuses existing BK-DEMO rows, adds any missing data)');
        $this->addOption('purge', null, InputOption::VALUE_NONE, 'Remove all BK-DEMO / [BK-DEMO] records');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (Environment::get() === Environment::PRODUCTION) {
            $output->writeln('<error>Refusing to run in production. Use a development stack.</error>');

            return 1;
        }

        $username = (string) $input->getOption('username');
        $this->loadUserSession($username);

        $seeder = new DemoDataSeeder(
            (bool) $input->getOption('force'),
            (bool) $input->getOption('purge'),
        );

        try {
            $result = $seeder->run();
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->outputSessionBufferedMessages();

            return 1;
        }

        if ($result['skipped']) {
            foreach ($result['lines'] as $line) {
                $output->writeln('<comment>' . $line . '</comment>');
            }

            return 0;
        }

        foreach ($result['lines'] as $line) {
            $output->writeln('<info>' . $line . '</info>');
        }

        if (!$input->getOption('purge')) {
            $output->writeln('');
            $output->writeln('<info>Created ~' . $result['created'] . ' records. Browse Assets, Assistance, Tools → Knowledge base, Management.</info>');
        }

        return 0;
    }
}
