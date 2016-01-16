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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;

class SubDefinitionHydrator implements HydratorInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var HydratorQueryProvider
     */
    private $queryProvider;

    public function __construct(Connection $connection, HydratorQueryProvider $queryProvider)
    {
        $this->connection = $connection;
        $this->queryProvider = $queryProvider;
    }

    public function hydrateRecords(array &$records)
    {
        $sql = $this->queryProvider->getSubdefinitionQuery();
        $statement = $this->connection->executeQuery($sql,
            array(array_keys($records)),
            array(Connection::PARAM_INT_ARRAY)
        );

        while ($subdefs = $statement->fetch()) {
            $records[$subdefs['record_id']]['subdefs'][$subdefs['name']] = array(
                'path' => $subdefs['path'],
                'width' => $subdefs['width'],
                'height' => $subdefs['height'],
            );
        }
    }
}
