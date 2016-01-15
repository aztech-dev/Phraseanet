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

        $sql = "UPDATE pref SET value= :structure, updated_on= :now WHERE prop='structure'";
        $stmt = $databoxConnection->prepare($sql);

        $stmt->execute(
            [
                ':structure' => $structure->getRawStructure(),
                ':now'       => $structure->getModificationDate()
            ]
        );

        $stmt->closeCursor();

        return $structure;
    }
}
