<?php

namespace Alchemy\Phrasea\Core\Database;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\DBAL\Connection;

class TableBuilderFactory
{

    private $driverFactories = [];

    private $tableBuilders = [];

    public function addDriverFactory($driverName, callable $tableBuilderFactory)
    {
        $this->driverFactories[$driverName] = $tableBuilderFactory;
    }

    /**
     * @param Connection $connection
     * @return TableBuilder
     */
    public function getTableBuilder(Connection $connection)
    {
        $driverName = $connection->getDriver()->getName();

        if (isset($this->tableBuilders[$driverName])) {
            return $this->tableBuilders[$driverName];
        }

        if (! isset($this->driverFactories[$driverName])) {
            throw new InvalidArgumentException("No builder registered for type '$driverName'.");
        }

        $factory = $this->driverFactories[$driverName];
        $this->tableBuilders[$driverName] = $factory();

        return $this->tableBuilders[$driverName];
    }
}
