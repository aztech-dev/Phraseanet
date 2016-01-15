<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Assert\Assertion;

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
        Assertion::notEmpty($dsn, 'Databox DSN is required.');

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
     * @return string
     */
    public function getDsn()
    {
        return $this->dsn;
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
     * @param bool $fallbackToDatabaseName
     * @return string
     */
    public function getViewName($fallbackToDatabaseName = true)
    {
        if (! $this->viewName && $fallbackToDatabaseName) {
            return $this->getDatabase();
        }

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
     * @return string
     */
    public function getLabel($languageCode)
    {
        if (array_key_exists($languageCode, $this->labels)) {
            return $this->labels[$languageCode];
        }

        throw new InvalidArgumentException(sprintf('Code %s is not defined', $languageCode));
    }

    /**
     * @param string $languageCode
     * @param string $defaultValue
     * @return string
     */
    public function getLabelOrDefault($languageCode, $defaultValue)
    {
        if (! array_key_exists($languageCode, $this->labels)) {
            return $defaultValue;
        }

        return $this->getLabel($languageCode);
    }

    public function getLabelOrViewname($languageCode)
    {
        return $this->getLabelOrDefault($languageCode, $this->getViewName(true));
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
            $dsnParameters = explode(';', $this->dsn);

            $this->parameters = [];

            foreach ($dsnParameters as $dsnParameter) {
                if (trim($dsnParameter) == '') {
                    continue;
                }

                list ($paramName, $paramValue) = explode('=', $dsnParameter, 2);

                $this->parameters[$paramName] = $paramValue;
            }

            $this->parameters['user'] = $this->user;
            $this->parameters['password'] = $this->password;
            $this->parameters['dbname'] = $this->database;
        }
    }

}
