<?php

namespace Alchemy\Phrasea\Setup\Step;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Step;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

class CreateApplicationBoxStep implements Step
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(Application $application, EntityManager $entityManager)
    {
        $this->application = $application;
        $this->entityManager = $entityManager;
    }

    public function getName()
    {
        return 'create-appbox';
    }

    public function execute(
        InitializeEnvironmentCommand $initializeEnvironmentCommand,
        Connection $appboxConnection,
        Connection $databoxConnection = null
    ) {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            $tool = new SchemaTool($this->entityManager);

            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        }

        $this->application->getApplicationBox()->get_connection()->close();
        $this->application->getApplicationBox()->get_connection()->connect();
        $this->application->getApplicationBox()->insert_datas();
    }
}
