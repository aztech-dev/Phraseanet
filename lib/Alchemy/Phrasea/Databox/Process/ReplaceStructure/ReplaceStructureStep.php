<?php

namespace Alchemy\Phrasea\Databox\Process\ReplaceStructure;

use Alchemy\Phrasea\Databox\Databox;
use Alchemy\Phrasea\Databox\DataboxPreferencesRepository;
use Alchemy\Phrasea\Databox\Preference\DataboxPreference;
use Alchemy\Phrasea\Databox\Structure\Structure;
use Doctrine\DBAL\Connection;

class ReplaceStructureStep
{

    public function execute(DataboxPreferencesRepository $repository, Databox $databox, \DOMDocument $newStructure)
    {
        $structure = Structure::createFromDomDocument($newStructure);
        $preference = $repository->findFirstByProperty('structure');

        if (! $preference) {
            $preference = new DataboxPreference(null, '', 'structure');
        }

        $preference->setValue($structure->getRawStructure());
        $repository->save($preference);

        return $structure;
    }
}
