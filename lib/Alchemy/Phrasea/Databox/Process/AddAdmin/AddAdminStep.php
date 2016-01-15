<?php

namespace Alchemy\Phrasea\Databox\Process\AddAdmin;

use Alchemy\Phrasea\Databox\Databox;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\Connection;

interface AddAdminStep
{

    public function execute(Connection $databoxConnection, Databox $databox, User $user);
}
