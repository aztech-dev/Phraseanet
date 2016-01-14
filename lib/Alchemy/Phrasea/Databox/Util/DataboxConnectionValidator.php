<?php

namespace Alchemy\Phrasea\Databox\Util;

use Doctrine\DBAL\Connection;

class DataboxConnectionValidator
{
    public static function validateConnection(\appbox $applicationBox, Connection $connection)
    {
        $sql = 'SELECT sbas_id
                FROM sbas
                WHERE host = :host AND port = :port AND dbname = :dbname
                      AND user = :user AND pwd = :password';

        $params = [
            ':host' => $connection->getHost(),
            ':port' => $connection->getPort(),
            ':dbname' => $connection->getDatabase(),
            ':user' => $connection->getUsername(),
            ':password' => $connection->getPassword()
        ];

        $statement = $applicationBox->get_connection()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        if ($row) {
            throw new \RuntimeException('Database is already used by another databox');
        }
    }
}
