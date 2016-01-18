<?php

namespace Alchemy\Phrasea\Databox\Record;

use Alchemy\Phrasea\Core\PhraseaTokens;
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
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getRecordCount()
    {
        $query = "SELECT COALESCE(COUNT(record_id), 0) AS n FROM record";
        $statement = $this->connection->prepare($query);
        $statement->execute();

        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        $amount = $result ? (int) $result["n"] : 0;

        return $amount;
    }

    /**
     * @return array
     */
    public function getRecordStatistics()
    {
        // we only care about these tokens
        $mask = PhraseaTokens::MAKE_SUBDEF | PhraseaTokens::TO_INDEX | PhraseaTokens::INDEXING;
        $query = "SELECT type, jeton & (".$mask.") AS status, SUM(1) AS n FROM record GROUP BY type, (jeton & ".$mask.")";

        $statement = $this->connection->prepare($query);
        $statement->execute();
        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $ret = array(
            'records'             => 0,
            'records_indexed'     => 0,    // jetons = 0;0
            'records_to_index'    => 0,    // jetons = 0;1
            'records_not_indexed' => 0,    // jetons = 1;0
            'records_indexing'    => 0,    // jetons = 1;1
            'subdefs_todo'        => array()   // by type "image", "video", ...
        );

        foreach ($results as $row) {
            $ret['records'] += ($n = (int)($row['n']));
            $status = $row['status'];
            switch($status & (PhraseaTokens::TO_INDEX | PhraseaTokens::INDEXING)) {
                case 0:
                    $ret['records_indexed'] += $n;
                    break;
                case PhraseaTokens::TO_INDEX:
                    $ret['records_to_index'] += $n;
                    break;
                case PhraseaTokens::INDEXING:
                    $ret['records_not_indexed'] += $n;
                    break;
                case PhraseaTokens::INDEXING | PhraseaTokens::TO_INDEX:
                    $ret['records_indexing'] += $n;
                    break;
            }
            if($status & PhraseaTokens::MAKE_SUBDEF) {
                if(!array_key_exists($row['type'], $ret['subdefs_todo'])) {
                    $ret['subdefs_todo'][$row['type']] = 0;
                }
                $ret['subdefs_todo'][$row['type']] += $n;
            }
        }

        return $ret;
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
