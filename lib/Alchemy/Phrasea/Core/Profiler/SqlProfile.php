<?php

namespace Alchemy\Phrasea\Core\Profiler;

class SqlProfile
{
    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $query;

    /**
     * @var string
     */
    private $queryHash;

    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $types;

    /**
     * @var float
     */
    private $startTime = 0;

    /**
     * @var float
     */
    private $endTime = 0;

    /**
     * @var float
     */
    private $duration = 0;

    public function __construct($sessionKey, $profileKey, $query, array $params, array $types)
    {
        $this->query = $query;
        $this->params = array_map(function ($value) {
            if (is_object($value) && ! method_exists($value, '__toString')) {
                $value = get_class($value);
            }

            if (is_array($value)) {
                $value = implode(',', $value);
            }

            return (string) $value;
        }, $params);

        $this->types = $types;

        $queryHash = hash('sha256', trim(strtolower($query)));
        $paramsHash = hash('sha256', trim(strtolower(serialize($this->params))));

        $this->queryHash = $queryHash;
        $this->key = hash('sha256', $sessionKey . $profileKey . $queryHash . $paramsHash);
        $this->sessionKey  = $sessionKey;
    }

    /**
     * @return string
     */
    public function getSessionKey()
    {
        return $this->sessionKey;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getUniqueKey()
    {
        return $this->getSessionKey() . $this->getKey();
    }

    /**
     * @return string
     */
    public function getQueryHash()
    {
        return $this->queryHash;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return float
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return float
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @return float
     */
    public function getDuration()
    {
        return $this->duration;
    }

    public function recordQueryStart()
    {
        $this->startTime = microtime(true);
    }

    public function recordQueryDone()
    {
        $this->endTime = microtime(true);
        $this->duration = $this->endTime - $this->startTime;
    }

}
