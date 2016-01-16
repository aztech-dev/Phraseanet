<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\Query;

use Doctrine\DBAL\Connection;

class QueryProviderFactory
{

    public static function getQueryProvider(Connection $connection)
    {
        switch ($connection->getDriver()->getName()) {
            case 'pdo_sqlite':
                return new SqliteQueryProvider();
            case 'pdo_mysql':
            default:
                return new MySqlQueryProvider();
        }
    }
}
