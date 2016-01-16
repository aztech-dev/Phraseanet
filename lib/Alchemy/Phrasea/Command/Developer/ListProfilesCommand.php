<?php

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\Profiler\SqlProfiler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListProfilesCommand extends Command
{
    public function __construct()
    {
        parent::__construct('profiler:sql:list');

        $this->setDescription("Lists all recorded SQL profiles.");
    }
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var SqlProfiler $profiler */
        $profiler = $this->container['dbal.profiler'];

        $sessionKeys = $profiler->getSessionKeys();

        foreach ($sessionKeys as $sessionKey) {
            $output->writeln($sessionKey);
        }
    }
}
