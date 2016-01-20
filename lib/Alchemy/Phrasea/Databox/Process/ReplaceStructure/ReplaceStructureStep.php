<?php

namespace Alchemy\Phrasea\Databox\Process\ReplaceStructure;

use Alchemy\Phrasea\Databox\Databox;
use Alchemy\Phrasea\Databox\Structure\Structure;
use Doctrine\DBAL\Connection;

class ReplaceStructureStep
{

    public function execute(Connection $databoxConnection, Databox $databox, \DOMDocument $newStructure)
    {
        $structure = Structure::createFromDomDocument($newStructure);
        $parameters = [
            ':structure' => $structure->getRawStructure(),
            ':now'       => $structure->getModificationDate()
        ];

        $sql = "UPDATE pref SET value= :structure, updated_on= :now WHERE prop='structure'";
        $stmt = $databoxConnection->prepare($sql);

        if (! $stmt->execute($parameters) || $stmt->rowCount() == 0) {
            $stmt->closeCursor();

            $sql = "INSERT INTO pref(value, updated_on, prop) VALUES (:structure, :now, 'structure')";
            $stmt = $databoxConnection->prepare($sql);

            if (! $stmt->execute($parameters) || $stmt->rowCount() == 0) {
                throw new \RuntimeException('Unable to replace structure: ' . implode(PHP_EOL, $stmt->errorInfo()));
            }
        }

        $stmt->closeCursor();

        return $structure;
    }
}
