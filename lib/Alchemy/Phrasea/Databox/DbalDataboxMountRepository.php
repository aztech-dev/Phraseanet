<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

use Doctrine\DBAL\Connection;

final class DbalDataboxMountRepository implements DataboxMountRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    /**
     * @return \databox[]
     */
    public function findAll()
    {
        return $this->factory->createMany($this->fetchRows());
    }

    /**
     * @param int $id
     * @return false|array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetchRow($id)
    {
        $query = 'SELECT ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas WHERE sbas_id = :id';
        $statement = $this->connection->prepare($query);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $row;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetchRows()
    {
        $query = 'SELECT sbas_id, ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas';
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $rows = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['sbas_id'];
            unset($row['sbas_id']);
            $rows[$id] = $row;
        }
        $statement->closeCursor();

        return $rows;
    }
}
