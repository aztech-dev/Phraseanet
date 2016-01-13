<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Doctrine\DBAL\Connection;

interface Step
{
    public function getName();

    public function execute(
        InitializeEnvironmentCommand $initializeEnvironmentCommand,
        Connection $appboxConnection,
        Connection $databoxConnection = null
    );
}
