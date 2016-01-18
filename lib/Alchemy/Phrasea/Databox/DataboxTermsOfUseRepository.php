<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Databox\Preference\DataboxPreference;

class DataboxTermsOfUseRepository
{

    /**
     * @var DataboxPreferencesRepository
     */
    private $preferencesRepository;

    /**
     * @var string[]
     */
    private $availableLocales = [];

    /**
     * @param DataboxPreferencesRepository $preferencesRepository
     * @param array $availableLocales
     */
    public function __construct(
        DataboxPreferencesRepository $preferencesRepository,
        array $availableLocales = []
    ) {
        $this->preferencesRepository = $preferencesRepository;
        $this->availableLocales = $availableLocales;
    }

    public function getTermsOfUse()
    {
        $termsOfUseVOs = $this->preferencesRepository->findByProperty('ToU');
        $termsOfUses = [];

        foreach ($termsOfUseVOs as $termsOfUseVO) {
            $termsOfUses[$termsOfUseVO->getLocale()] = [
                'updated_on' => $termsOfUseVO->getUpdatedOn(),
                'value' => $termsOfUseVO->getValue()
            ];
        }

        $missingLocales = [];

        foreach ($this->availableLocales as $code => $language) {
            if (!isset($termsOfUses[$code])) {
                $missingLocales[] = $code;
            }
        }

        $termsOfUses = array_intersect_key($termsOfUses, $this->availableLocales);

        foreach ($missingLocales as $missingLocale) {
            $preference = new DataboxPreference(null, $missingLocale, 'ToU');

            $this->preferencesRepository->save($preference);

            $termsOfUses[$missingLocale] = [
                'updated_on' => $preference->getUpdatedOn(),
                'value' => $preference->getValue()
            ];
        }

        return $termsOfUses;
    }
}
