<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\Databox\CreatedEvent;
use Alchemy\Phrasea\Core\Event\Databox\DataboxEvents;
use Alchemy\Phrasea\Core\Event\Databox\MountedEvent;
use Alchemy\Phrasea\Core\Event\Databox\UnmountedEvent;
use Alchemy\Phrasea\Databox\Field\DublinCoreFieldProvider;
use Alchemy\Phrasea\Databox\Process\Create\AbstractCreateStep;
use Alchemy\Phrasea\Databox\Process\Create\CreateStep;
use Alchemy\Phrasea\Databox\Process\DataboxProcessRegistry;
use Alchemy\Phrasea\Databox\Process\Mount\AbstractMountStep;
use Alchemy\Phrasea\Databox\Process\Mount\MountStep;
use Alchemy\Phrasea\Databox\Process\StepRegistry;
use Alchemy\Phrasea\Databox\Process\Unmount\UnmountStep;
use Alchemy\Phrasea\Exception\RuntimeException;
use Assert\Assertion;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DataboxService
{

    /**
     * @var callable
     */
    private $connectionFactory;

    /**
     * @var DataboxRepository
     */
    private $databoxRepository;

    /**
     * @var DublinCoreFieldProvider
     */
    private $dcFieldProvider;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var DataboxProcessRegistry
     */
    private $processRegistry;

    /**
     * @param DataboxRepository $databoxRepository
     * @param EventDispatcherInterface $dispatcher
     * @param DataboxProcessRegistry $processRegistry
     * @param callable $connectionFactory
     */
    public function __construct(
        DataboxRepository $databoxRepository,
        EventDispatcherInterface $dispatcher,
        DataboxProcessRegistry $processRegistry,
        callable $connectionFactory
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->databoxRepository = $databoxRepository;
        $this->eventDispatcher = $dispatcher;
        $this->processRegistry = $processRegistry;

        $this->dcFieldProvider = new DublinCoreFieldProvider();
    }

    /**
     * @param Connection $connection
     * @param \SplFileInfo $dataTemplate
     * @return \databox
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createDatabox(Connection $connection, \SplFileInfo $dataTemplate)
    {
        $steps = $this->processRegistry->getProcessSteps(CreateStep::class);

        $databox = AbstractCreateStep::runSteps($connection, $dataTemplate, $steps);

        if ($databox == null) {
            throw new RuntimeException('Databox create process did not return a databox.');
        }

        $this->eventDispatcher->dispatch(DataboxEvents::CREATED, new CreatedEvent($databox));

        return $databox;
    }

    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $databaseName
     * @return \databox
     */
    public function mountDatabox($host, $port, $user, $password, $databaseName)
    {
        $databoxConnection = $this->createDataboxConnection($host, $port, $user, $password, $databaseName);
        $steps = $this->processRegistry->getProcessSteps(MountStep::class);

        $databox = AbstractMountStep::runSteps($databoxConnection, $steps);

        if ($databox == null) {
            throw new RuntimeException('Databox mount process did not return a databox.');
        }


        $this->eventDispatcher->dispatch(DataboxEvents::MOUNTED, new MountedEvent($databox));

        return $databox;
    }

    /**
     * @param \databox $databox
     */
    public function unmountDatabox(\databox $databox)
    {
        $databoxVO = $databox->getDataObject();
        $databaseName = $databoxVO->getDatabase();

        foreach ($this->processRegistry->getProcessSteps(UnmountStep::class) as $step) {
            /** @var UnmountStep $step */
            $step->execute($databox);
        }

        $this->eventDispatcher->dispatch(
            DataboxEvents::UNMOUNTED,
            new UnmountedEvent(null, [ 'dbname' => $databaseName ])
        );
    }

    /**
     * @return \databox_Field_DCESAbstract[]
     */
    public function getAvailableDublinCoreFields()
    {
        return $this->dcFieldProvider->getAvailableDublinCoreFields();
    }

    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $dbname
     * @return Connection
     */
    private function createDataboxConnection($host, $port, $user, $password, $dbname)
    {
        $connectionFactory = $this->connectionFactory;
        $connectionParams = [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'dbname' => $dbname,
        ];


        /** @var Connection $databoxConnection */
        $databoxConnection = $connectionFactory($connectionParams);
        $databoxConnection->connect();

        return $databoxConnection;
    }
}
