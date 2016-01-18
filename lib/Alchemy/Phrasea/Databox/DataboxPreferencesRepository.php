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
     * @return DataboxPreference
     */
    public function findFirstByProperty($propertyName);

    /**
     * @param DataboxPreference $preference
     * @return bool
     */
    public function save(DataboxPreference $preference);
}
