<?php

namespace Alchemy\Phrasea\Core\Database;

use Doctrine\DBAL\Connection;

interface TableBuilder
{
    /**
     * @param Connection $connection
     * @param \SimpleXMLElement $table
     * @return void
     */
    public function buildTable(Connection $connection, \SimpleXMLElement $table);
}
