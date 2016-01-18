<?php

namespace Alchemy\Phrasea\Databox\Process\SetStructure;

use Alchemy\Phrasea\Databox\DataboxService;

class BuildStructureStep implements SetStructureStep
{

    /**
     * @var DataboxService
     */
    private $databoxService;

    /**
     * @param DataboxService $databoxService
     */
    public function __construct(DataboxService $databoxService)
    {
        $this->databoxService = $databoxService;
    }


    public function execute(\databox $databox, \SplFileInfo $dataTemplate, $documentPath)
    {
        if ( ! file_exists($dataTemplate->getPathname())) {
            throw new \InvalidArgumentException(sprintf('File %s does not exists'));
        }

        $contents = file_get_contents($dataTemplate->getPathname());
        $contents = str_replace(
            ["{{basename}}", "{{datapathnoweb}}"],
            [$databox->get_connection()->getDatabase(), rtrim($documentPath, '/').'/'],
            $contents
        );

        $structureDom = new \DOMDocument();
        $structureDom->loadXML($contents);

        $this->databoxService->replaceDataboxStructure($databox, $structureDom);
    }
}
