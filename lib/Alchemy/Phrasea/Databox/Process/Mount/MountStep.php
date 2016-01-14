<?php

namespace Alchemy\Phrasea\Databox\Process\Mount;

use Doctrine\DBAL\Connection;

interface MountStep
{

    /**
     * @param MountStep $mountStep
     * @return void
     */
    public function setNext(MountStep $mountStep);

    /**
     * @param Connection $connection
     * @return \databox
     */
    public function execute(Connection $connection);
}
