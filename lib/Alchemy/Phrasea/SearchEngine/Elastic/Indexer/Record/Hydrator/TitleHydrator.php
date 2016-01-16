<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator;

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\Query\MySqlQueryProvider;
use Doctrine\DBAL\Connection;

class TitleHydrator implements HydratorInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var HydratorQueryProvider
     */
    private $queryProvider;

    /**
     * @param Connection $connection
     * @param HydratorQueryProvider $queryProvider
     */
    public function __construct(Connection $connection, HydratorQueryProvider $queryProvider)
    {
        $this->connection = $connection;
        $this->queryProvider = $queryProvider;
    }

    public function hydrateRecords(array &$records)
    {
        $sql = $this->queryProvider->getRecordTitleQuery();
        $statement = $this->connection->executeQuery(
            $sql,
            array(array_keys($records)),
            array(Connection::PARAM_INT_ARRAY)
        );

        while ($row = $statement->fetch()) {
            $records[$row['record_id']]['title'][$row['locale']] = $row['title'];
        }
    }
}
