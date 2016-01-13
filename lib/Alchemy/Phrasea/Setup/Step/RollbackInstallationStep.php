<?php

namespace Alchemy\Phrasea\Setup\Step;

use Alchemy\Phrasea\Core\Configuration\HostConfiguration;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Step;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class RollbackInstallationStep implements Step
{
    /**
     * @var HostConfiguration
     */
    private $hostConfiguration;

    public function __construct(HostConfiguration $hostConfiguration)
    {
        $this->hostConfiguration = $hostConfiguration;
    }

    public function getName()
    {
        return 'rollback';
    }

    public function execute(
        InitializeEnvironmentCommand $initializeEnvironmentCommand,
        Connection $appboxConnection,
        Connection $databoxConnection = null
    ) {
        $structure = simplexml_load_file(__DIR__ . "/../../../../conf.d/bases_structure.xml");

        if (!$structure) {
            throw new \RuntimeException('Unable to load schema');
        }

        $this->dropAppboxTables($appboxConnection, $structure);
        $this->dropDataboxTables($databoxConnection, $structure);

        $this->hostConfiguration->delete();
    }

    /**
     * @param Connection $appboxConnection
     * @param $structure
     */
    private function dropAppboxTables(Connection $appboxConnection, $structure)
    {
        $appbox = $structure->appbox;

        foreach ($appbox->tables->table as $table) {
            $this->dropTable($appboxConnection, $table);
        }
    }

    /**
     * @param Connection $databoxConnection
     * @param $structure
     */
    private function dropDataboxTables(Connection $databoxConnection, $structure)
    {
        if ($databoxConnection === null) {
            return;
        }

        $databox = $structure->databox;

        foreach ($databox->tables->table as $table) {
            $this->dropTable($databoxConnection, $table);
        }
    }

    /**
     * @param Connection $connection
     * @param $table
     */
    private function dropTable(Connection $connection, $table)
    {
        try {
            $sql = sprintf('DROP TABLE IF EXISTS `%s`', $table['name']);

            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (DBALException $e) {
            // Ignore error, not sure why.
        }
    }
}
