<?php

namespace Alchemy\Phrasea\Setup\Step;

use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Step;
use Doctrine\DBAL\Connection;

class CreateAdminUserStep implements Step
{

    /**
     * @var Authenticator
     */
    private $authenticator;

    /**
     * @var UserManipulator
     */
    private $userManipulator;

    public function __construct(UserManipulator $userManipulator, Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->userManipulator = $userManipulator;
    }

    public function getName()
    {
        return 'create-admin-user';
    }

    public function execute(
        InitializeEnvironmentCommand $initializeEnvironmentCommand,
        Connection $appboxConnection,
        Connection $databoxConnection = null
    ) {
        $user = $this->userManipulator->createUser(
            $initializeEnvironmentCommand->getUserEmail(),
            $initializeEnvironmentCommand->getUserPassword(),
            $initializeEnvironmentCommand->getUserEmail(),
            true
        );

        if (php_sapi_name() !== 'cli') {
            // Sessions should only be opened in web contexts
            $this->authenticator->openAccount($user);
        }
    }
}
