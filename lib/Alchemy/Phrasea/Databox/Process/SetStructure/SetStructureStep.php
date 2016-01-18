<?php

namespace Alchemy\Phrasea\Databox\Process\SetStructure;

interface SetStructureStep 
{

    public function execute(\databox  $databox, \SplFileInfo $dataTemplate, $documentPath);
}
