<?php

namespace Alchemy\Phrasea\Setup\Step;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Step;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Class CreateApplicationBoxStep
 * @package Alchemy\Phrasea\Setup\Step
 */
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

    /**
     * @param Application $application
     * @param EntityManager $entityManager
     */
    public function __construct(Application $application, EntityManager $entityManager)
    {
        $this->application = $application;
        $this->entityManager = $entityManager;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'create-appbox';
    }

    /**
     * @param InitializeEnvironmentCommand $initializeEnvironmentCommand
     * @param Connection $appboxConnection
     * @param Connection $databoxConnection
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function execute(
        InitializeEnvironmentCommand $initializeEnvironmentCommand,
        Connection $appboxConnection,
        Connection $databoxConnection = null
    ) {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $metadata = [];

        if ($metadataFactory !== null) {
            $metadata = $metadataFactory->getAllMetadata();
        }

        if (!empty($metadata)) {
            $tool = new SchemaTool($this->entityManager);

            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        }

        $this->application->getApplicationBox()->insert_datas();
    }
}
