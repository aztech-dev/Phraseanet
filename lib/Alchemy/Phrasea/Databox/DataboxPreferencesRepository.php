<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Databox\Preference\DataboxPreference;

interface DataboxPreferencesRepository
{

    /**
     * @return DataboxPreference[]
     */
    public function findAll();

    /**
     * @param $propertyName
     * @return DataboxPreference[]
     */
    public function findByProperty($propertyName);

    /**
     * @param string $propertyName
     * @return DataboxPreference|null
     */
    public function findFirstByProperty($propertyName);

    /**
     * @param $propertyName
     * @param $locale
     * @return DataboxPreference|null
     */
    public function findFirstByPropertyAndLocale($propertyName, $locale);

    /**
     * @param DataboxPreference $preference
     * @return bool
     */
    public function save(DataboxPreference $preference);
}
