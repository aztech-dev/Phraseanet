<?php

namespace Alchemy\Phrasea\Setup\Command;

class InstallCommand 
{
    /**
     * @var string
     */
    private $databaseHost;

    /**
     * @var string
     */
    private $databasePort;

    /**
     * @var string
     */
    private $databaseUser;

    /**
     * @var string
     */
    private $databasePassword;

    /**
     * @var string
     */
    private $databaseName;

    /**
     * @var array
     */
    private $params;

    /**
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $password
     * @param string $name
     * @param array $params
     */
    public function __construct($host, $port, $user, $password, $name, array $params = [])
    {
        $this->databaseHost = $host;
        $this->databasePort = $port;
        $this->databaseName = $name;
        $this->databaseUser = $user;
        $this->databasePassword = $password;

        $this->params = $params;
    }

    public function getParameters()
    {
        return $this->params;
    }

    /**
     * @return string
     */
    public function getDatabaseHost()
    {
        return $this->databaseHost;
    }

    /**
     * @return string
     */
    public function getDatabasePort()
    {
        return $this->databasePort;
    }

    /**
     * @return string
     */
    public function getDatabaseUser()
    {
        return $this->databaseUser;
    }

    /**
     * @return string
     */
    public function getDatabasePassword()
    {
        return $this->databasePassword;
    }

    /**
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }
}
