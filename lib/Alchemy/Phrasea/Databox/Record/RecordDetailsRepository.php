<?php

namespace Alchemy\Phrasea\Databox\Record;

use Doctrine\DBAL\Connection;

class RecordDetailsRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $sort
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getRecordDetails($sort)
    {
        $sql = <<<EOQ
(
    SELECT
        record.coll_id, ISNULL(coll.coll_id) AS lostcoll,
        COALESCE(asciiname, CONCAT('_',record.coll_id)) AS asciiname, name,
        SUM(1) AS n, SUM(size) AS siz
    FROM (record, subdef)
        LEFT JOIN coll ON record.coll_id=coll.coll_id
    WHERE
        record.record_id = subdef.record_id
    GROUP BY
        record.coll_id, name
) UNION (
    SELECT
        coll.coll_id, 0, asciiname, '_' AS name, 0 AS n, 0 AS siz
    FROM coll
        LEFT JOIN record ON record.coll_id=coll.coll_id
    WHERE
        ISNULL(record.coll_id)
    GROUP BY
        record.coll_id, name
)
EOQ;

        if ($sort == "obj") {
            $sortk1 = "name";
            $sortk2 = "asciiname";
        } else {
            $sortk1 = "asciiname";
            $sortk2 = "name";
        }

        $stmt = $this->connection->prepare($sql);

        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $rowbas) {
            if ( ! isset($trows[$rowbas[$sortk1]]))
                $trows[$rowbas[$sortk1]] = [];
            $trows[$rowbas[$sortk1]][$rowbas[$sortk2]] = [
                "coll_id"   => $rowbas["coll_id"],
                "asciiname" => $rowbas["asciiname"],
                "lostcoll"  => $rowbas["lostcoll"],
                "name"      => $rowbas["name"],
                "n"         => $rowbas["n"],
                "siz"       => $rowbas["siz"]
            ];
        }

        ksort($trows);

        foreach ($trows as $kgrp => $vgrp)
            ksort($trows[$kgrp]);

        return $trows;
    }
}
