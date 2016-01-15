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

final class DbalDataboxRepository implements DataboxRepository
{

    const SELECT_QUERY = <<<EOQ
SELECT
    sbas_id, ord, dsn,
    host, port, dbname, sqlengine, user, pwd, viewname, label_en, label_fr, label_de, label_nl
FROM
    sbas
EOQ;

    const UPDATE_QUERY = <<<EOQ
UPDATE sbas
SET ord = :ord, host = :host, port = :port, dbname = :dbname, sqlengine = :sqlengine, user = :user, pwd = :pwd,
    viewname = :viewname, label_en = :label_en, label_fr = :label_fr, label_de = :label_de, label_nl = :label_nl
WHERE sbas_id = :sbas_id
EOQ;

    /** @var Connection */
    private $connection;
    /** @var DataboxFactory */
    private $factory;

    /**
     * @param Connection $connection
     * @param DataboxFactory $factory
     */
    public function __construct(Connection $connection, DataboxFactory $factory)
    {
        $this->connection = $connection;
        $this->factory = $factory;
    }

    /**
     * @param int $id
     * @return \databox|null
     */
    public function find($id)
    {
        $row = $this->fetchRow($id);

        if (is_array($row)) {
            return $this->factory->create($id, $row);
        }

        return null;
    }

    /**
     * @return \databox[]
     */
    public function findAll()
    {
        return $this->factory->createMany($this->fetchRows());
    }

    public function save(Databox $databox)
    {
        $statement = $this->connection->prepare(self::UPDATE_QUERY);
        $statement->execute([
            'sbas_id'   => $databox->getDataboxId(),
            'ord'       => $databox->getDisplayIndex(),
            'viewname'  => $databox->getViewName(),
            'label_en'  => $databox->getLabel('en'),
            'label_fr'  => $databox->getLabel('fr'),
            'label_de'  => $databox->getLabel('de'),
            'label_nl'  => $databox->getLabel('nl'),
            'host'      => $databox->getHost(),
            'port'      => $databox->getPort(),
            'user'      => $databox->getUser(),
            'pwd'       => $databox->getPassword(),
            'dbname'    => $databox->getDatabase(),
            'sqlengine' => $databox->getType()
        ]);
    }

    /**
     * @param int $id
     * @return false|array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetchRow($id)
    {
        $query = self::SELECT_QUERY . ' WHERE sbas_id = :id';

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
        $query = self::SELECT_QUERY;

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

    /**
     * @param Connection $connection
     * @return \databox
     * @throws \Doctrine\DBAL\DBALException
     */
    public function mount(Connection $connection)
    {
        $sql = 'INSERT INTO sbas (sbas_id, ord, dsn, host, port, dbname, sqlengine, user, pwd)
              VALUES (null, :ord, :dsn, :host, :port, :dbname, :sqlengine, :user, :password)';

        $driverName = str_replace('pdo_', '', $connection->getDriver()->getName());
        $connectionParams = [];

        foreach ($connection->getParams() as $name => $value) {
            $connectionParams[] = $name . '=' . $value;
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            ':ord' => $this->getNewDataboxOrdinal(),
            ':host' => '',
            ':port' => '',
            ':dbname' => '',
            ':user' => $connection->getUsername(),
            ':password' => $connection->getPassword(),
            ':dsn' => $driverName . ':' . implode(';', $connectionParams),
            ':sqlengine' => $driverName
        ]);
        $statement->closeCursor();

        $databoxId = (int)$this->connection->lastInsertId();

        return $this->find($databoxId);
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getNewDataboxOrdinal()
    {
        $sql = 'SELECT COALESCE(MAX(ord), 0) + 1 as ord FROM sbas';

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row['ord'];
    }
}
