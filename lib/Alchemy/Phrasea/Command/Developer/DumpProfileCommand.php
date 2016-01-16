<?php

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\Profiler\SqlProfile;
use Alchemy\Phrasea\Core\Profiler\SqlProfiler;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpProfileCommand extends Command
{
    public function __construct()
    {
        parent::__construct('profiler:sql:dump');

        $this->setDescription("Lists all recorded SQL profiles.");
        $this->addArgument('session', InputArgument::REQUIRED, 'Session key of the profile');
        $this->addOption('unique', 'u', InputOption::VALUE_NONE, 'Whether to dump only unique queries');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);

        $sessionKey = $input->getArgument('session');
        /** @var SqlProfiler $profiler */
        $profiler = $this->container['dbal.profiler'];
        $profiles = $profiler->getSqlProfiles($sessionKey);

        $table->setHeaders([ 'Stat name', 'Value' ]);

        $table->addRow(['Query count', count($profiles)]);
        $table->addRow([
            'Total duration',
            array_sum(array_map(function (SqlProfile $profile) {
                return $profile->getDuration();
            }, $profiles)) . ' ms'
        ]);

        $output->writeln('');
        $table->render($output);

        $allQueries = array_map(function (SqlProfile $profile) {
            return $profile->getQuery();
        }, $profiles);

        if ($input->getOption('unique')) {
            $allQueries = array_unique($allQueries);
        }

        $table = new Table($output);

        $table->setHeaders([ '#', 'SQL query' ]);

        $index = 0;

        foreach ($allQueries as $uniqueQuery) {
            $table->addRow([ $index++, substr(trim($uniqueQuery), 0, 100) ]);
        }

        $output->writeln('');
        $table->render();
    }
}
