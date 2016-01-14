<?php

namespace Alchemy\Phrasea\Databox\Process\Create;

use Doctrine\DBAL\Connection;

class CreateDatabaseForDataboxStep extends AbstractCreateStep
{

    public function execute(Connection $connection, \SplFileInfo $dataTemplate)
    {
        try {
            $sql = 'CREATE DATABASE `' . $connection->getDatabase() . '` CHARACTER SET utf8 COLLATE utf8_unicode_ci';

            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (\Exception $e) {
            // Do nothing
        }

        return $this->runNext($connection, $dataTemplate);
    }
}
