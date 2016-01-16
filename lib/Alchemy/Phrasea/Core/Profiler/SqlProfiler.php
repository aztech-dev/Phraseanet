<?php

namespace Alchemy\Phrasea\Core\Profiler;

use Doctrine\Common\Cache\Cache;
use RandomLib\Generator;

class SqlProfiler
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @var Generator
     */
    private $randomGenerator;

    /**
     * @param Generator $randomGenerator
     * @param Cache $cache
     * @param string $cacheKey
     * @param string $sessionKey
     */
    public function __construct(Generator $randomGenerator, Cache $cache, $cacheKey, $sessionKey)
    {
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
        $this->sessionKey = $sessionKey;
        $this->randomGenerator = $randomGenerator;
    }

    /**
     * @param $query
     * @param array $params
     * @param array $types
     * @return SqlProfile
     */
    public function startProfile($query, array $params, array $types)
    {
        return new SqlProfile($this->sessionKey, $this->randomGenerator->generateString(32), $query, $params, $types);
    }

    /**
     * @param SqlProfile $profile
     */
    public function saveProfile(SqlProfile $profile)
    {
        $profileIndex = $this->getProfileIndex();

        if (! array_key_exists($profile->getSessionKey(), $profileIndex)) {
            $profileIndex[$profile->getSessionKey()] = [];
        }

        $profileIndex[$profile->getSessionKey()][] = $profile->getUniqueKey();

        $this->cache->save($this->cacheKey, serialize($profileIndex));
        $this->cache->save($profile->getUniqueKey(), serialize($profile));
    }

    public function purge()
    {
        foreach ($this->getSessionKeys() as $sessionKey) {
            $this->cache->delete($sessionKey);
        }

        $this->cache->delete($this->cacheKey);
    }

    public function getProfileIndex()
    {
        return $this->cache->contains($this->cacheKey) ?
            unserialize($this->cache->fetch($this->cacheKey)) :
            [ ];
    }

    public function getSessionKeys()
    {
        $index = $this->getProfileIndex();
        $sessions = [];

        foreach ($index as $sessionKey => $profileKeys) {
            $sessions[] = $sessionKey;
        }

        return $sessions;
    }

    /**
     * @param $sessionKey
     * @return SqlProfile[]
     */
    public function getSqlProfiles($sessionKey)
    {
        $sqlProfiles = [];
        $profileIndex = $this->getProfileIndex();

        if (! isset($profileIndex[$sessionKey])) {
            throw new \InvalidArgumentException('Session not found.');
        }

        foreach ($profileIndex[$sessionKey] as $profileKey) {
            $sqlProfiles[] = unserialize($this->cache->fetch($profileKey));
        }

        return $sqlProfiles;
    }
}
