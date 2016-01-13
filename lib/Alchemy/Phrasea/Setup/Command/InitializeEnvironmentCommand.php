<?php

namespace Alchemy\Phrasea\Setup\Command;

use Assert\Assertion;

class InitializeEnvironmentCommand
{

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $databaseTemplate;

    /**
     * @var string
     */
    private $dataPath;

    /**
     * @var string
     */
    private $serverName;

    /**
     * @var string[]
     */
    private $binaryPaths;

    /**
     * @param string $userEmail
     * @param string $userPassword
     * @param string $databaseTemplate
     * @param string $dataPath
     * @param string $serverName
     * @param string[] $binaryPaths
     */
    public function __construct(
        $userEmail,
        $userPassword,
        $databaseTemplate,
        $dataPath,
        $serverName,
        array $binaryPaths = []
    ) {
        Assertion::allString($binaryPaths);

        $this->email = $userEmail;
        $this->password = $userPassword;
        $this->databaseTemplate = $databaseTemplate;
        $this->dataPath = $dataPath;
        $this->serverName = $serverName;
        $this->binaryPaths = $binaryPaths;
    }

    /**
     * @return string
     */
    public function getUserEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getUserPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDatabaseTemplate()
    {
        return $this->databaseTemplate;
    }

    /**
     * @return string
     */
    public function getDataPath()
    {
        return $this->dataPath;
    }

    /**
     * @return string
     */
    public function getServerName()
    {
        return $this->serverName;
    }

    /**
     * @return string[]
     */
    public function getBinaryPaths()
    {
        return $this->binaryPaths;
    }
}
