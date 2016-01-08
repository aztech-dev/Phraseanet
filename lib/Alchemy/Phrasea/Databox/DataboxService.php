<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\Databox\CreatedEvent;
use Alchemy\Phrasea\Core\Event\Databox\DataboxEvents;
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

        // @todo Constructor injection for following assignments
        $this->applicationBox = $application['phraseanet.appbox'];
        $this->configuration = $application['conf'];
        $this->eventDispatcher = $application['dispatcher'];
    }

    /**
     * @param Connection $connection
     * @param \SplFileInfo $data_template
     * @return \databox
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createDatabox(Connection $connection, \SplFileInfo $data_template)
    {
        if ( ! file_exists($data_template->getRealPath())) {
            throw new \InvalidArgumentException($data_template->getRealPath() . " does not exist");
        }

        $sql = 'SELECT sbas_id
            FROM sbas
            WHERE host = :host AND port = :port AND dbname = :dbname
              AND user = :user AND pwd = :password';

        $params = [
            ':host'     => $connection->getHost(),
            ':port'     => $connection->getPort(),
            ':dbname'   => $connection->getDatabase(),
            ':user'     => $connection->getUsername(),
            ':password' => $connection->getPassword()
        ];

        $stmt = $this->applicationBox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            throw new \RuntimeException('Database is already used by another databox');
        }

        try {
            $sql = 'CREATE DATABASE `' . $connection->getDatabase() . '`
              CHARACTER SET utf8 COLLATE utf8_unicode_ci';
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (\Exception $e) {

        }

        $sql = 'USE `' . $connection->getDatabase() . '`';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'SELECT MAX(ord) as ord FROM sbas';
        $stmt = $this->applicationBox->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ord = 0;

        if ($row) {
            $ord = $row['ord'] + 1;
        }

        $params[':ord'] = $ord;

        $sql = 'INSERT INTO sbas (sbas_id, ord, host, port, dbname, sqlengine, user, pwd)
              VALUES (null, :ord, :host, :port, :dbname, "MYSQL", :user, :password)';
        $stmt = $this->applicationBox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();
        $sbas_id = (int) $this->applicationBox->get_connection()->lastInsertId();

        $app['orm.add']([
            'host'     => $connection->getHost(),
            'port'     => $connection->getPort(),
            'dbname'   => $connection->getDatabase(),
            'user'     => $connection->getUsername(),
            'password' => $connection->getPassword()
        ]);

        $this->applicationBox->delete_data_from_cache(\appbox::CACHE_LIST_BASES);

        $databox = $this->applicationBox->get_databox($sbas_id);
        $databox->insert_datas();
        $databox->setNewStructure(
            $data_template, $this->configuration->get(['main', 'storage', 'subdefs'])
        );

        $this->eventDispatcher->dispatch(DataboxEvents::CREATED, new CreatedEvent($databox));

        return $databox;
    }
}
