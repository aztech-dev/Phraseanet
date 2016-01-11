<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\Databox\CreatedEvent;
use Alchemy\Phrasea\Core\Event\Databox\DataboxEvents;
use Alchemy\Phrasea\Core\Event\Databox\MountedEvent;
use Alchemy\Phrasea\Databox\Field\DublinCoreFieldProvider;
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

    /** @var DublinCoreFieldProvider */
    private $dcFieldProvider;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param Application $application
     * @param DataboxRepository $databoxRepository
     */
    public function __construct(Application $application, DataboxRepository $databoxRepository)
    {
        $this->application = $application;
        $this->databoxRepository = $databoxRepository;
        $this->dcFieldProvider = new DublinCoreFieldProvider();

        // @todo Constructor injection for following assignments
        $this->applicationBox = $application['phraseanet.appbox'];
        $this->configuration = $application['conf'];
        $this->eventDispatcher = $application['dispatcher'];
    }

    /**
     * @param Connection $connection
     * @param \SplFileInfo $dataTemplate
     * @return \databox
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createDatabox(Connection $connection, \SplFileInfo $dataTemplate)
    {
        if ( ! file_exists($dataTemplate->getRealPath())) {
            throw new \InvalidArgumentException($dataTemplate->getRealPath() . " does not exist");
        }

        $this->assertDatabaseIsNotUsedByAnotherDatabase($connection);
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

        $this->assertDatabaseIsNotUsedByAnotherDatabase($databoxConnection);

        $databox = $this->registerDataboxInAppBox($databoxConnection);

        $databox->delete_data_from_cache(\databox::CACHE_COLLECTIONS);

        \phrasea::reset_sbasDatas($this->applicationBox);
        \cache_databox::update($this->application, $databox->get_sbas_id(), 'structure');

        $this->eventDispatcher->dispatch(DataboxEvents::MOUNTED, new MountedEvent($databox));

        return $databox;
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
    protected function assertDatabaseIsNotUsedByAnotherDatabase(Connection $connection)
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

        $stmt = $this->applicationBox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            throw new \RuntimeException('Database is already used by another databox');
        }

        return $params;
    }

    /**
     * @param Connection $connection
     */
    protected function createDatabaseForNewDatabox(Connection $connection)
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
    protected function getNewDataboxOrdinal()
    {
        $sql = 'SELECT COALESCE(MAX(ord), 0) + 1 as ord FROM sbas';

        $stmt = $this->applicationBox->get_connection()->prepare($sql);
        $stmt->execute();

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        return $row['ord'];
    }

    /**
     * @param Connection $connection
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function useDataboxDatabase(Connection $connection)
    {
        $sql = 'USE `' . $connection->getDatabase() . '`';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @param Connection $databoxConnection
     * @return \databox
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function registerDataboxInAppBox(Connection $databoxConnection)
    {
        $appConnection = $this->applicationBox->get_connection();

        $sql = 'INSERT INTO sbas (sbas_id, ord, host, port, dbname, sqlengine, user, pwd)
              VALUES (null, :ord, :host, :port, :dbname, "MYSQL", :user, :password)';

        $stmt = $appConnection->prepare($sql);
        $stmt->execute([
            ':ord' => $this->getNewDataboxOrdinal(),
            ':host' => $databoxConnection->getHost(),
            ':port' => $databoxConnection->getPort(),
            ':dbname' => $databoxConnection->getDatabase(),
            ':user' => $databoxConnection->getUsername(),
            ':password' => $databoxConnection->getPassword()
        ]);

        $stmt->closeCursor();

        $databoxId = (int) $appConnection->lastInsertId();

        $this->application['orm.add']([
            'host' => $databoxConnection->getHost(),
            'port' => $databoxConnection->getPort(),
            'dbname' => $databoxConnection->getDatabase(),
            'user' => $databoxConnection->getUsername(),
            'password' => $databoxConnection->getPassword()
        ]);

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
    protected function createDataboxConnection($host, $port, $user, $password, $dbname)
    {
        $connectionParams = [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'dbname' => $dbname,
        ];

        /** @var Connection $databoxConnection */
        $databoxConnection = $this->application['db.provider']($connectionParams);
        $databoxConnection->connect();

        return $databoxConnection;
    }
}
