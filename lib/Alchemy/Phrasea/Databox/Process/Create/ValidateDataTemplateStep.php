<?php

namespace Alchemy\Phrasea\Databox\Process\Create;

use Doctrine\DBAL\Connection;

class ValidateDataTemplateStep extends AbstractCreateStep
{

    public function execute(Connection $connection, \SplFileInfo $dataTemplate)
    {
        if (!file_exists($dataTemplate->getRealPath())) {
            throw new \InvalidArgumentException($dataTemplate->getRealPath() . " does not exist");
        }

        return $this->runNext($connection, $dataTemplate);
    }
}
