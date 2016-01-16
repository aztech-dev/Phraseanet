<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator;

interface HydratorQueryProvider 
{

    /**
     * @return string
     */
    public function getMetadataQuery();

    /**
     * @return string
     */
    public function getRecordTitleQuery();

    /**
     * @return string
     */
    public function getSubdefinitionQuery();
}
