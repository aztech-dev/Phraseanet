<?php

namespace Alchemy\Phrasea\Databox\Process\Create;

use Doctrine\DBAL\Connection;

class SetCurrentDatabaseStep extends AbstractCreateStep
{

    public function execute(Connection $connection, \SplFileInfo $dataTemplate)
    {
        if ($connection->getDriver()->getName() == 'pdo_mysql') {
            $sql = 'USE `' . $connection->getDatabase() . '`';
            $stmt = $connection->prepare($sql);
            $stmt->execute();
        }

        return $this->runNext($connection, $dataTemplate);
    }
}
