<?php

namespace Alchemy\Phrasea\Databox\Process\UpdateTerms;

class UpdateTermsProcess 
{

    /**
     * @param \databox $databox
     * @param string $locale
     * @param string $terms
     * @param bool $resetDate
     */
    public function execute(\databox $databox, $locale, $terms, $resetDate)
    {
        $repository = $databox->getTermsOfUseRepository();

        $repository->updateTermsOfUse($locale, $terms, (bool) $resetDate);
    }
}
