<?php

namespace Alchemy\Phrasea\Databox\Structure;

class StructureValidator 
{

    const MISSING_NAME_ATTRIBUTE = 'ERREUR : TOUTES LES BALISES subdefgroup necessitent un attribut name';

    const NON_UNIQUE_NAME = 'ERREUR : Les name de subdef sont uniques par groupe de subdefs et necessaire';

    const INVALID_SUBDEF_CLASS = 'ERREUR : La classe de subdef est necessaire et egal a "thumbnail","preview" ou "document"';

    /**
     * @param \databox $databox
     * @return StructureErrorCollection
     */
    public function validateDataboxStructure(\databox $databox)
    {
        return $this->validateStructure($databox->getStructure());
    }

    /**
     * @param Structure $structure
     * @return StructureErrorCollection
     */
    public function validateStructure(Structure $structure)
    {
        $structureDom = $structure->getSimpleXmlElement();

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

                if ($this->isNameAlreadyUsed($subdefName, $availableSubdefs, $subdefGroupName)) {
                    $errors->addErrorMessage(self::NON_UNIQUE_NAME);

                    continue;
                }

                if ($this->isInvalidSubdefClass($subdefClass)) {
                    $errors->addErrorMessage(self::INVALID_SUBDEF_CLASS);

                    continue;
                }

                $availableSubdefs[$subdefGroupName][$subdefName] = $subdef;
            }
        }

        return $errors;
    }

    /**
     * @param $subdefName
     * @param $availableSubdefs
     * @param $subdefGroupName
     * @return bool
     */
    private function isNameAlreadyUsed($subdefName, $availableSubdefs, $subdefGroupName)
    {
        return $subdefName == '' || isset($availableSubdefs[$subdefGroupName][$subdefName]);
    }

    /**
     * @param $subdefClass
     * @return bool
     */
    private function isInvalidSubdefClass($subdefClass)
    {
        return !in_array($subdefClass, ['thumbnail', 'preview', 'document']);
    }
}