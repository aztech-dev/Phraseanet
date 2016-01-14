<?php

namespace Alchemy\Phrasea\Databox\Process\Create;

use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\DBAL\Connection;

abstract class AbstractCreateStep implements CreateStep
{

    /**
     * @param Connection $connection
     * @param \SplFileInfo $dataTemplate
     * @param $steps
     * @return \databox
     */
    public static function runSteps(Connection $connection, \SplFileInfo $dataTemplate, $steps)
    {
        /** @var CreateStep $firstStep */
        $firstStep = null;
        /** @var CreateStep $previousStep */
        $previousStep = null;

        foreach ($steps as $step) {
            /** @var CreateStep $step */
            if ($firstStep === null) {
                $firstStep = $step;
            }

            if ($previousStep !== null) {
                $previousStep->setNext($step);
            }

            $previousStep = $step;
        }

        /** @var \databox $databox */
        return $firstStep->execute($connection, $dataTemplate);
    }

    /**
     * @var null|CreateStep
     */
    private $nextStep = null;

    /**
     * @param CreateStep $step
     */
    public function setNext(CreateStep $step)
    {
        $this->nextStep = $step;
    }

    /**
     * @return bool
     */
    public function hasNext()
    {
        return $this->nextStep !== null;
    }

    /**
     * @return CreateStep
     */
    public function getNext()
    {
        if ($this->nextStep === null) {
            throw new RuntimeException('No next step defined, cannot continue.');
        }

        return $this->nextStep;
    }

    /**
     * @param Connection $connection
     * @param \SplFileInfo $dataTemplate
     * @return \databox
     */
    public function runNext(Connection $connection, \SplFileInfo $dataTemplate)
    {
        $nextStep = $this->getNext();

        return $nextStep->execute($connection, $dataTemplate);
    }
}
