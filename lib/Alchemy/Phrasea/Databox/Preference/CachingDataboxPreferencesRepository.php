<?php

namespace Alchemy\Phrasea\Databox\Preference;

use Alchemy\Phrasea\Databox\DataboxPreferencesRepository;
use Doctrine\Common\Cache\Cache;

class CachingDataboxPreferencesRepository implements DataboxPreferencesRepository
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
     * @var DataboxPreferencesRepository
     */
    private $repository;

    /**
     * @param DataboxPreferencesRepository $preferencesRepository
     * @param Cache $cache
     * @param $cacheKey
     */
    public function __construct(DataboxPreferencesRepository $preferencesRepository, Cache $cache, $cacheKey)
    {
        $this->repository = $preferencesRepository;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * @return DataboxPreference[]
     */
    public function findAll()
    {
        if ($this->cache->contains($this->cacheKey)) {
            return unserialize($this->cache->fetch($this->cacheKey));
        }

        $preferences = $this->repository->findAll();

        $this->cache->save($this->cacheKey, serialize($preferences));

        return $preferences;
    }

    /**
     * @param $propertyName
     * @return DataboxPreference[]
     */
    public function findByProperty($propertyName)
    {
        $preferences = $this->findAll();

        return array_filter($preferences, function (DataboxPreference $preference) use ($propertyName) {
            return $preference->getProperty() == $propertyName;
        });
    }

    /**
     * @param string $propertyName
     * @return DataboxPreference
     */
    public function findFirstByProperty($propertyName)
    {
        $preferences = $this->findAll();

        foreach ($preferences as $preference) {
            if ($preference->getProperty() == $propertyName) {
                return $preference;
            }
        }

        return null;
    }

    /**
     * @param string $propertyName
     * @param string $locale
     * @return DataboxPreference[]
     */
    public function findFirstByPropertyAndLocale($propertyName, $locale)
    {
        $preferences = $this->findAll();

        foreach ($preferences as $preference) {
            if ($preference->getProperty() == $propertyName && $preference->getLocale() == $locale) {
                return $preference;
            }
        }

        return null;
    }

    /**
     * @param DataboxPreference $preference
     * @return bool
     */
    public function save(DataboxPreference $preference)
    {
        $this->cache->delete($this->cacheKey);
        $this->repository->save($preference);
    }
}
