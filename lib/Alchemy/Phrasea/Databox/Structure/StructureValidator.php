<?php

namespace Alchemy\Phrasea\Databox\Structure;

class StructureValidator 
{

    const MISSING_NAME_ATTRIBUTE = 'ERREUR : TOUTES LES BALISES subdefgroup necessitent un attribut name';

    const NON_UNIQUE_NAME = 'ERREUR : Les name de subdef sont uniques par groupe de subdefs et necessaire';

    const INVALID_SUBDEF_CLASS = 'ERREUR : La classe de subdef est necessaire et egal a "thumbnail","preview" ou "document"';

    public function validateDataboxStructure(\databox $databox)
    {
        return $this->validateStructure($databox->get_structure());
    }

    /**
     * @param string $structure
     * @return StructureErrorCollection
     */
    public function validateStructure($structure)
    {
        $structureDom = simplexml_load_string($structure);

        /** @var \SimpleXMLElement[] $subdefGroup */
        $subdefGroup = $structureDom->subdefs[0];
        $availableSubdefs = [];

        $errors = new StructureErrorCollection();

        foreach ($subdefGroup as $subdefs) {
            $subdefGroupName = trim((string) $subdefs->attributes()->name);

            if ($subdefGroupName == '') {
                $errors->addErrorMessage(self::MISSING_NAME_ATTRIBUTE);

                continue;
            }

            if ( ! isset($availableSubdefs[$subdefGroupName])) {
                $availableSubdefs[$subdefGroupName] = [];
            }

            /** @var \SimpleXMLElement[] $subdefs */
            foreach ($subdefs as $subdef) {
                $subdefName = trim(mb_strtolower((string) $subdef->attributes()->name));
                $subdefClass = trim(mb_strtolower((string) $subdef->attributes()->class));

                if ($subdefName == '' || isset($availableSubdefs[$subdefGroupName][$subdefName])) {
                    $errors->addErrorMessage(self::NON_UNIQUE_NAME);

                    continue;
                }

                if ( ! in_array($subdefClass, ['thumbnail', 'preview', 'document'])) {
                    $errors->addErrorMessage(self::INVALID_SUBDEF_CLASS);

                    continue;
                }

                $availableSubdefs[$subdefGroupName][$subdefName] = $subdef;
            }
        }

        return $errors;
    }
}
