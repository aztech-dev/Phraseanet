<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Core\Event\InstallFinishEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Command\InstallCommand;
use Alchemy\Phrasea\Setup\Command\InstallCommandResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Exception\Exception;

class SetupService
{

    /**
     * @var callable
     */
    private $connectionFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var StepRegistry
     */
    private $stepRegistry;

    /**
     * @param callable $connectionFactory
     * @param StepRegistry $stepRegistry
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(callable $connectionFactory, StepRegistry $stepRegistry, EventDispatcherInterface $dispatcher)
    {
        $this->connectionFactory = $connectionFactory;
        $this->dispatcher = $dispatcher;
        $this->stepRegistry = $stepRegistry;
    }

    /**
     * @param InitializeEnvironmentCommand $initializeEnvironmentCommand
     * @param InstallCommand $appboxInstallCommand
     * @param InstallCommand $databoxInstallCommand
     * @return InstallCommandResult
     * @throws \Exception
     */
    public function install(
        InitializeEnvironmentCommand $initializeEnvironmentCommand,
        InstallCommand $appboxInstallCommand,
        InstallCommand $databoxInstallCommand = null)
    {
        try {
            $appboxConnection = $this->buildConnection($appboxInstallCommand);
        }
        catch (DBALException $exception) {
            return new InstallCommandResult(false, 'Appbox is unreachable');
        }

        $databoxConnection = null;

        if ($databoxInstallCommand !== null) {
            try {
                $databoxConnection = $this->buildConnection($databoxInstallCommand);
            }
            catch (DBALException $exception) {
                return new InstallCommandResult(false, 'Databox is unreachable');
            }
        }

        try {
            foreach ($this->stepRegistry->getSteps() as $step) {
                $step->execute($initializeEnvironmentCommand, $appboxConnection, $databoxConnection);
            }

            $this->dispatcher->dispatch(PhraseaEvents::INSTALL_FINISH, new InstallFinishEvent());
        }
        catch (Exception $exception) {
            $this->stepRegistry->getRollbackStep()->execute(
                $initializeEnvironmentCommand,
                $appboxConnection,
                $databoxConnection
            );

            return new InstallCommandResult(false, 'an error occured : %message%', [
                '%message%' =>$exception->getMessage()
            ]);
        }

        return new InstallCommandResult(true);
    }

    private function buildConnection(InstallCommand $installCommand)
    {
        $parameters = $installCommand->getParameters();

        if (empty($parameters)) {
            $parameters = [
                'driver' => 'pdo_mysql',
                'host' => $installCommand->getDatabaseHost(),
                'port' => $installCommand->getDatabasePort(),
                'user' => $installCommand->getDatabaseUser(),
                'password' => $installCommand->getDatabasePassword(),
                'dbname' => $installCommand->getDatabaseName()
            ];
        }

        $connectionFactory = $this->connectionFactory;
        /** @var Connection $connection */
        $connection = $connectionFactory($parameters);

        $connection->connect();

        return $connection;
    }
}
