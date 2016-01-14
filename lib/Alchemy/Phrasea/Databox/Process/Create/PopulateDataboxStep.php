<?php

namespace Alchemy\Phrasea\Databox\Process\Create;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Doctrine\DBAL\Connection;

class PopulateDataboxStep extends AbstractCreateStep
{
    /**
     * @var PropertyAccess
     */
    private $configuration;

    /**
     * @param PropertyAccess $configuration
     */
    public function __construct(PropertyAccess $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param Connection $connection
     * @param \SplFileInfo $dataTemplate
     * @return \databox
     */
    public function execute(Connection $connection, \SplFileInfo $dataTemplate)
    {
        $databox = $this->runNext($connection, $dataTemplate);

        $databoxStructure = $this->configuration->get(['main', 'storage', 'subdefs']);

        $databox->insert_datas();
        $databox->setNewStructure($dataTemplate, $databoxStructure);
    }
}
