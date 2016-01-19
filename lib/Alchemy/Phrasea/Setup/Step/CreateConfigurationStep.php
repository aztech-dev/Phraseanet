<?php

namespace Alchemy\Phrasea\Setup\Step;

use Alchemy\Phrasea\Core\Configuration\HostConfiguration;
use Alchemy\Phrasea\Core\Configuration\RegistryManipulator;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Command\InstallCommand;
use Alchemy\Phrasea\Setup\Step;
use Doctrine\DBAL\Connection;
use RandomLib\Generator;

class CreateConfigurationStep implements Step
{
    /**
     * @var HostConfiguration
     */
    private $hostConfiguration;

    /**
     * @var RegistryManipulator
     */
    private $registryManipulator;

    /**
     * @var Generator
     */
    private $randomGenerator;

    /**
     * @var string
     */
    private $rootPath;

    public function __construct(
        HostConfiguration $hostConfiguration,
        RegistryManipulator $registryManipulator,
        Generator $randomGenerator,
        $rootPath
    ) {
        $this->hostConfiguration = $hostConfiguration;
        $this->registryManipulator = $registryManipulator;
        $this->randomGenerator = $randomGenerator;
        $this->rootPath = $rootPath;
    }

    public function getName()
    {
        return 'create-configuration';
    }

    public function execute(InitializeEnvironmentCommand $initializeEnvironmentCommand, Connection $appboxConnection, Connection $databoxConnection = null)
    {
        $dataPath = realpath($initializeEnvironmentCommand->getDataPath());

        if ($dataPath === null) {
            throw new \InvalidArgumentException(sprintf('Path %s does not exist.', $dataPath));
        }

        $config = $this->hostConfiguration->initialize()->getConfig();

        $config['registry'] = $this->registryManipulator->getRegistryData();
        $config['servername'] = $initializeEnvironmentCommand->getServerName();

        $config['main']['binaries'] = $initializeEnvironmentCommand->getBinaryPaths();
        $config['main']['key'] = $this->randomGenerator->generateString(16);

        $config['main']['database'] = $appboxConnection->getParams();

        $config['main']['storage'] = [
            'cache' => realpath($this->rootPath . '/cache'),
            'log' => realpath($this->rootPath .  '/logs'),
            'download' => realpath($this->rootPath . '/tmp/download'),
            'lazaret' => realpath($this->rootPath . '/tmp/lazaret'),
            'caption' => realpath($this->rootPath . '/tmp/caption'),
            'subdefs' => $dataPath
        ];

        $this->hostConfiguration->setConfig($config);
    }
}
