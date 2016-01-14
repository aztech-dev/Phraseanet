<?php

namespace Alchemy\Phrasea\Databox;

class Databox
{

    /**
     * @var int
     */
    private $databoxId;

    /**
     * @var string
     */
    private $type = 'mysql';

    /**
     * @var string Connection DSN without the driver prefix
     */
    private $dsn;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string[]
     */
    private $parameters = null;

    /**
     * @var int
     */
    private $displayIndex = 0;

    /**
     * @var string
     */
    private $viewName = '';

    /**
     * @var string[]
     */
    private $labels = [];

    /**
     * @param int $databoxId
     * @param string $type
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param string $database
     */
    public function __construct($databoxId, $type, $dsn, $user, $password, $database)
    {
        $this->databoxId = $databoxId;
        $this->type = $type;
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
    }

    /**
     * @return int
     */
    public function getDataboxId()
    {
        return $this->databoxId;
    }

    /**
     * @return null|string
     */
    public function getHost()
    {
        return $this->getParameter('host');
    }

    /**
     * @return null|string
     */
    public function getPort()
    {
        return $this->getParameter('port');
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return int
     */
    public function getDisplayIndex()
    {
        return $this->displayIndex;
    }

    /**
     * @param $index
     */
    public function setDisplayIndex($index)
    {
        $this->displayIndex = max(0, $index);
    }

    /**
     * @return string
     */
    public function getViewName()
    {
        return $this->viewName;
    }

    /**
     * @param string $viewName
     */
    public function setViewName($viewName)
    {
        $this->viewName = $viewName;
    }

    /**
     * @return \string[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param string $languageCode
     * @param string $label
     */
    public function setLabel($languageCode, $label)
    {
        $this->labels[$languageCode] = $label;
    }

    /**
     * @return \string[]
     */
    public function getConnectionParameters()
    {
        $this->parseParameters();

        return $this->parameters;
    }

    private function getParameter($name)
    {
        $this->parseParameters();

        if (! isset($this->parameters[$name])) {
            return null;
        }

        return $this->parameters[$name];
    }

    private function parseParameters()
    {
        if ($this->parameters === null) {
            $this->parameters = explode(';', $this->dsn);

            $this->parameters['user'] = $this->user;
            $this->parameters['password'] = $this->password;
            $this->parameters['dbname'] = $this->database;
        }
    }

}
