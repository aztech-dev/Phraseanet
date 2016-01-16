<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\Query;

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\HydratorQueryProvider;

class SqliteQueryProvider implements HydratorQueryProvider
{

    /**
     * @return string
     */
    public function getMetadataQuery()
    {
        return <<<SQL
SELECT record_id, ms.name AS `key`, m.value AS value, 'caption' AS type, ms.business AS private
FROM metadatas AS m
INNER JOIN metadatas_structure AS ms ON (ms.id = m.meta_struct_id)
WHERE record_id IN (?)

UNION

SELECT record_id, t.name AS `key`, t.value AS value, 'exif' AS type, 0 AS private
FROM technical_datas AS t
WHERE record_id IN (?)
SQL;
    }

    /**
     * @return string
     */
    public function getRecordTitleQuery()
    {
        return <<<EOQ
SELECT
    m.`record_id`,
    CASE ms.`thumbtitle`
      WHEN "1" THEN "default"
      WHEN "0" THEN "default"
      ELSE ms.`thumbtitle`
    END AS locale,
    CASE ms.`thumbtitle`
      WHEN "0" THEN r.`originalname`
      ELSE GROUP_CONCAT(m.`value`)
    END AS title
FROM metadatas AS m
JOIN metadatas_structure AS ms ON (ms.`id` = m.`meta_struct_id`)
JOIN record AS r ON (r.`record_id` = m.`record_id`)
WHERE m.`record_id` IN (?)
GROUP BY m.`record_id`, ms.`thumbtitle`
EOQ;
    }

    public function getSubdefinitionQuery()
    {
        return <<<SQL
SELECT
    s.record_id,
    s.name,
    s.height,
    s.width,
    RTRIM(s.path, '/') || '/' || s.file AS path
FROM
    subdef s
WHERE
    s.record_id IN (?)
    AND s.name IN ('thumbnail', 'preview', 'thumbnailgif')
SQL;
    }
}
