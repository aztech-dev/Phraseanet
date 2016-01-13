<?php

namespace Alchemy\Phrasea\Setup\Step;

use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Step;
use Doctrine\DBAL\Connection;

class CreateDefaultUsersStep implements Step
{
    /**
     * @var UserManipulator
     */
    private $userManipulator;

    public function __construct(UserManipulator $userManipulator)
    {
        $this->userManipulator = $userManipulator;
    }

    public function execute(
        InitializeEnvironmentCommand $initializeEnvironmentCommand,
        Connection $appboxConnection,
        Connection $databoxConnection = null
    ) {
        $this->userManipulator->createUser(User::USER_AUTOREGISTER, User::USER_AUTOREGISTER);
        $this->userManipulator->createUser(User::USER_GUEST, User::USER_GUEST);
    }
}
