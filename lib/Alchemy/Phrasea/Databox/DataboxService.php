<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\Databox\CreatedEvent;
use Alchemy\Phrasea\Core\Event\Databox\DataboxEvents;
use Alchemy\Phrasea\Core\Event\Databox\MountedEvent;
use Alchemy\Phrasea\Core\Event\Databox\UnmountedEvent;
use Alchemy\Phrasea\Databox\Field\DublinCoreFieldProvider;
use Alchemy\Phrasea\Databox\Process\Unmount\StepRegistry;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DataboxService
{

    /**
     * @var Application
     */
    private $application;

    /**
     * @var \appbox
     */
    private $applicationBox;

    /**
     * @var PropertyAccess
     */
    private $configuration;

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
     * @var StepRegistry
     */
    private $unmountStepRegistry;

    /**
     * @param Application $application
     * @param DataboxRepository $databoxRepository
     * @param EventDispatcherInterface $dispatcher
     * @param StepRegistry $unmountStepRegistry
     */
    public function __construct(
        Application $application,
        DataboxRepository $databoxRepository,
        EventDispatcherInterface $dispatcher,
        StepRegistry $unmountStepRegistry
    ) {
        $this->application = $application;
        $this->databoxRepository = $databoxRepository;
        $this->eventDispatcher = $dispatcher;
        $this->unmountStepRegistry = $unmountStepRegistry;

        $this->dcFieldProvider = new DublinCoreFieldProvider();

        // @todo Constructor injection for following assignments
        $this->applicationBox = $application['phraseanet.appbox'];
        $this->configuration = $application['conf'];
    }

    /**
     * @param Connection $connection
     * @param \SplFileInfo $dataTemplate
     * @return \databox
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createDatabox(Connection $connection, \SplFileInfo $dataTemplate)
    {
        if (!file_exists($dataTemplate->getRealPath())) {
            throw new \InvalidArgumentException($dataTemplate->getRealPath() . " does not exist");
        }

        $this->assertDatabaseIsNotUsedByAnotherDatabox($connection);
        $this->createDatabaseForNewDatabox($connection);
        $this->useDataboxDatabase($connection);

        $databox = $this->registerDataboxInAppBox($connection);
        $databoxStructure = $this->configuration->get(['main', 'storage', 'subdefs']);

        $databox->insert_datas();
        $databox->setNewStructure($dataTemplate, $databoxStructure);

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

        $this->assertDatabaseIsNotUsedByAnotherDatabox($databoxConnection);

        $databox = $this->registerDataboxInAppBox($databoxConnection);

        $databox->delete_data_from_cache(\databox::CACHE_COLLECTIONS);

        \phrasea::reset_sbasDatas($this->applicationBox);

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

        foreach ($this->unmountStepRegistry->getSteps() as $step) {
            $step->execute($databox);
        }

        $event = new UnmountedEvent(null, [ 'dbname' => $databaseName ]);
        $this->eventDispatcher->dispatch(DataboxEvents::UNMOUNTED, $event);
    }

    /**
     * @return \databox_Field_DCESAbstract[]
     */
    public function getAvailableDublinCoreFields()
    {
        return $this->dcFieldProvider->getAvailableDublinCoreFields();
    }

    /**
     * @param Connection $connection
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function assertDatabaseIsNotUsedByAnotherDatabox(Connection $connection)
    {
        $sql = 'SELECT sbas_id
                FROM sbas
                WHERE host = :host AND port = :port AND dbname = :dbname
                      AND user = :user AND pwd = :password';

        $params = [
            ':host' => $connection->getHost(),
            ':port' => $connection->getPort(),
            ':dbname' => $connection->getDatabase(),
            ':user' => $connection->getUsername(),
            ':password' => $connection->getPassword()
        ];

        $statement = $this->applicationBox->get_connection()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        if ($row) {
            throw new \RuntimeException('Database is already used by another databox');
        }

        return $params;
    }

    /**
     * @param Connection $connection
     */
    private function createDatabaseForNewDatabox(Connection $connection)
    {
        try {
            $sql = 'CREATE DATABASE `' . $connection->getDatabase() . '` CHARACTER SET utf8 COLLATE utf8_unicode_ci';

            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (\Exception $e) {
            // Do nothing
        }
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getNewDataboxOrdinal()
    {
        $sql = 'SELECT COALESCE(MAX(ord), 0) + 1 as ord FROM sbas';

        $stmt = $this->applicationBox->get_connection()->prepare($sql);
        $stmt->execute();

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row['ord'];
    }

    /**
     * @param Connection $connection
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function useDataboxDatabase(Connection $connection)
    {
        $sql = 'USE `' . $connection->getDatabase() . '`';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
    }

    /**
     * @param Connection $databoxConnection
     * @return \databox
     * @throws \Doctrine\DBAL\DBALException
     */
    private function registerDataboxInAppBox(Connection $databoxConnection)
    {
        $appConnection = $this->applicationBox->get_connection();

        $sql = 'INSERT INTO sbas (sbas_id, ord, host, port, dbname, sqlengine, user, pwd)
              VALUES (null, :ord, :host, :port, :dbname, "MYSQL", :user, :password)';

        $statement = $appConnection->prepare($sql);
        $statement->execute([
            ':ord' => $this->getNewDataboxOrdinal(),
            ':host' => $databoxConnection->getHost(),
            ':port' => $databoxConnection->getPort(),
            ':dbname' => $databoxConnection->getDatabase(),
            ':user' => $databoxConnection->getUsername(),
            ':password' => $databoxConnection->getPassword()
        ]);
        $statement->closeCursor();

        $databoxId = (int)$appConnection->lastInsertId();

        $this->applicationBox->delete_data_from_cache(\appbox::CACHE_LIST_BASES);

        return $this->applicationBox->get_databox($databoxId);
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
        $connectionParams = [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'dbname' => $dbname,
        ];

        /** @var Connection $databoxConnection */
        $databoxConnection = $this->application['dbal.provider']($connectionParams);
        $databoxConnection->connect();

        return $databoxConnection;
    }
}
