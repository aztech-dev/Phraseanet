<?php

namespace Alchemy\Phrasea\Databox\Process\Unmount;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Doctrine\DBAL\Connection;

class DeleteDataboxReferencesStep implements Step
{
    /**
     * @var PropertyAccess
     */
    private $configuration;

    /**
     * @var \appbox
     */
    private $applicationBox;

    public function __construct(\appbox $appbox, PropertyAccess $configuration)
    {
        $this->configuration = $configuration;
        $this->applicationBox = $appbox;
    }

    public function execute(\databox $databox)
    {
        $appboxConnection = $this->applicationBox->get_connection();
        $databoxConnection = $databox->get_connection();

        $params = [ ':site_id' => $this->configuration->get(['main', 'key']) ];

        $this->executeQuery($databoxConnection, 'DELETE FROM clients WHERE site_id = :site_id', $params);
        $this->executeQuery($databoxConnection, 'DELETE FROM memcached WHERE site_id = :site_id', $params);

        $params = [ ':sbas_id' => $databox->get_sbas_id() ];

        $this->executeQuery($appboxConnection, 'DELETE FROM sbas WHERE sbas_id = :sbas_id', $params);
        $this->executeQuery($appboxConnection, 'DELETE FROM sbasusr WHERE sbas_id = :sbas_id', $params);
    }

    private function executeQuery(Connection $connection, $query, array $parameters = [])
    {
        $statement = $connection->prepare($query);

        $statement->execute($parameters);
        $statement->closeCursor();
    }
}
