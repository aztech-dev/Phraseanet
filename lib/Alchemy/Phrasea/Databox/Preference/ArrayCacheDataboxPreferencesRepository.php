<?php

namespace Alchemy\Phrasea\Databox\Preference;

use Alchemy\Phrasea\Databox\DataboxPreferencesRepository;

class ArrayCacheDataboxPreferencesRepository implements DataboxPreferencesRepository
{

    private $preferences = null;

    /**
     * @var DataboxPreferencesRepository
     */
    private $repository;

    public function __construct(DataboxPreferencesRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return DataboxPreference[]
     */
    public function findAll()
    {
        if ($this->preferences === null) {
            $this->preferences = [];
            $preferences = $this->repository->findAll();

            foreach ($preferences as $preference) {
                $this->preferences[$preference->getId()] = $preference;
            }
        }

        return $this->preferences;
    }

    /**
     * @param $propertyName
     * @return DataboxPreference[]
     */
    public function findByProperty($propertyName)
    {
        $preferences = $this->findAll();
        $foundPreferences = [];

        foreach ($preferences as $preference) {
            if ($preference->getProperty() == $propertyName) {
                $foundPreferences[] = $preference;
            }
        }

        return $foundPreferences;
    }

    /**
     * @param string $propertyName
     * @return DataboxPreference|null
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
     * @return DataboxPreference|null
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
        // Force loading preferences to build identity map
        $this->findAll();
        $this->repository->save($preference);

        $this->preferences[$preference->getId()] = $preference;
    }
}
