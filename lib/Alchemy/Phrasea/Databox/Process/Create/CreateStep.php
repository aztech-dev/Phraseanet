<?php

namespace Alchemy\Phrasea\Databox\Process\Create;

use Doctrine\DBAL\Connection;

interface CreateStep
{

    public function setNext(CreateStep $step);

    /**
     * @param Connection $connection
     * @param \SplFileInfo $dataTemplate
     * @return \databox
     */
    public function execute(Connection $connection, \SplFileInfo $dataTemplate);
}
