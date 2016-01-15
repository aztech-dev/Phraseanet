<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Databox as DataboxEvent;
use Alchemy\Phrasea\Core\Event\Databox\DataboxEvents;
use Alchemy\Phrasea\Databox\Field\DublinCoreFieldProvider;
use Alchemy\Phrasea\Databox\Process\AddAdmin\AddAdminStep;
use Alchemy\Phrasea\Databox\Process\Create\AbstractCreateStep;
use Alchemy\Phrasea\Databox\Process\Create\CreateStep;
use Alchemy\Phrasea\Databox\Process\DataboxProcessRegistry;
use Alchemy\Phrasea\Databox\Process\Delete\DeleteStep;
use Alchemy\Phrasea\Databox\Process\Mount\AbstractMountStep;
use Alchemy\Phrasea\Databox\Process\Mount\MountStep;
use Alchemy\Phrasea\Databox\Process\Reindex\ReindexStep;
use Alchemy\Phrasea\Databox\Process\ReplaceStructure\ReplaceStructureStep;
use Alchemy\Phrasea\Databox\Process\Unmount\UnmountStep;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\User;
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

        $this->eventDispatcher->dispatch(DataboxEvents::CREATED, new DataboxEvent\CreatedEvent($databox));

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

        $this->eventDispatcher->dispatch(DataboxEvents::MOUNTED, new DataboxEvent\MountedEvent($databox));

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
            new DataboxEvent\UnmountedEvent(null, [ 'dbname' => $databaseName ])
        );
    }

    /**
     * @param \databox $databox
     */
    public function deleteDatabox(\databox $databox)
    {
        $databoxVO = $databox->getDataObject();
        $databaseName = $databoxVO->getDatabase();

        foreach ($this->processRegistry->getProcessSteps(DeleteStep::class) as $step) {
            /** @var DeleteStep $step */
            $step->execute($databox);
        }

        $this->eventDispatcher->dispatch(
            DataboxEvents::DELETED,
            new DataboxEvent\DeletedEvent(null, [ 'dbname'=> $databaseName ])
        );
    }

    /**
     * @param \databox $databox
     * @param User $adminUser
     */
    public function addDataboxAdmin(\databox $databox, User $adminUser)
    {
        $databoxConnection = $databox->get_connection();
        $databoxVO = $databox->getDataObject();

        foreach ($this->processRegistry->getProcessSteps(AddAdminStep::class) as $step) {
            /** @var AddAdminStep $step */
            $step->execute($databoxConnection, $databoxVO, $adminUser);
        }

        // @todo Dispatch event
    }

    /**
     * @param \databox $databox
     */
    public function reindexDatabox(\databox $databox)
    {
        $databoxConnection = $databox->get_connection();
        $databoxVO = $databox->getDataObject();

        foreach ($this->processRegistry->getProcessSteps(ReindexStep::class) as $step) {
            /** @var ReindexStep $step */
            $step->execute($databoxConnection, $databoxVO);
        }

        $this->eventDispatcher->dispatch(DataboxEvents::REINDEX_ASKED, new DataboxEvent\ReindexAskedEvent($databox));
    }

    /**
     * @param \databox $databox
     * @param \DOMDocument $structureDom
     */
    public function replaceDataboxStructure(\databox $databox, \DOMDocument $structureDom)
    {
        $previousStructure = $databox->getStructure();;
        $databoxConnection = $databox->get_connection();
        $databoxVO = $databox->getDataObject();

        foreach ($this->processRegistry->getProcessSteps(ReplaceStructureStep::class) as $step) {
            /** @var ReplaceStructureStep $step */
            $step->execute($databoxConnection, $databoxVO, $structureDom);
        }

        $databox->delete_data_from_cache(\databox::CACHE_STRUCTURE);

        $this->eventDispatcher->dispatch(
            DataboxEvents::STRUCTURE_CHANGED,
            new DataboxEvent\StructureChangedEvent($databox, [
                'dom_before' => $previousStructure->getRawStructure()
            ])
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
