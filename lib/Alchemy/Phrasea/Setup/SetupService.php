<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Setup\Command\InstallCommand;
use Alchemy\Phrasea\Setup\Command\InstallCommandResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class SetupService
{
    /**
     * @var callable
     */
    private $connectionFactory;

    /**
     * @param callable $connectionFactory
     */
    public function __construct(callable $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    public function install(InstallCommand $appboxInstallCommand, InstallCommand $databoxInstallCommand = null)
    {
        try {
            $appboxConnection = $this->buildConnection($appboxInstallCommand);
        }
        catch (DBALException $exception) {
            return new InstallCommandResult(false, 'Appbox is unreachable');
        }

        if ($databoxInstallCommand !== null) {
            try {
                $databoxConnection = $this->buildConnection($databoxInstallCommand);
            }
            catch (DBALException $exception) {
                return new InstallCommandResult(false, 'Databox is unreachable');
            }
        }

        return new InstallCommandResult(true);
    }

    private function buildConnection(InstallCommand $installCommand)
    {
        $connectionFactory = $this->connectionFactory;
        /** @var Connection $connection */
        $connection = $connectionFactory([
            'driver' => 'pdo_mysql',
            'host' => $installCommand->getDatabaseHost(),
            'port' => $installCommand->getDatabasePort(),
            'user' => $installCommand->getDatabaseUser(),
            'password' => $installCommand->getDatabasePassword(),
            'dbname' => $installCommand->getDatabaseName()
        ]);

        $connection->connect();

        return $connection;
    }
}
