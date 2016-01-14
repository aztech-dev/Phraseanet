<?php

namespace Alchemy\Phrasea\Databox\Process\Mount;

use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\DBAL\Connection;

abstract class AbstractMountStep implements MountStep
{
    /**
     * @param Connection $connection
     * @param $steps
     * @return \databox
     */
    public static function runSteps(Connection $connection, $steps)
    {
        /** @var MountStep $firstStep */
        $firstStep = null;
        /** @var MountStep $previousStep */
        $previousStep = null;

        foreach ($steps as $step) {
            /** @var MountStep $step */
            if ($firstStep === null) {
                $firstStep = $step;
            }

            if ($previousStep !== null) {
                $previousStep->setNext($step);
            }

            $previousStep = $step;
        }

        /** @var \databox $databox */
        return $firstStep->execute($connection);
    }

    /**
     * @var null|MountStep
     */
    private $nextStep = null;

    /**
     * @param MountStep $step
     */
    public function setNext(MountStep $step)
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
     * @return MountStep
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
     * @return \databox
     */
    public function runNext(Connection $connection)
    {
        $nextStep = $this->getNext();

        return $nextStep->execute($connection);
    }
}
