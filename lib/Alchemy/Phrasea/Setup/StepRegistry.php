<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Exception\RuntimeException;

class StepRegistry
{
    /**
     * @var callable[]
     */
    private $factories = [];

    /**
     * @var callable
     */
    private $rollbackStepFactory;

    /**
     * @param callable $stepFactory
     */
    public function addStepFactory(callable $stepFactory)
    {
        $this->factories[] = $stepFactory;
    }

    /**
     * @param callable $stepFactory
     */
    public function setRollbackStepFactory(callable $stepFactory)
    {
        $this->rollbackStepFactory = $stepFactory;
    }

    /**
     * @return Step[]|\Generator
     */
    public function getSteps()
    {
        foreach ($this->factories as $factory) {
            yield $factory();
        }
    }

    /**
     * @return Step
     */
    public function getRollbackStep()
    {
        if (! $this->rollbackStepFactory) {
            throw new RuntimeException('Rollback step factory is not set.');
        }

        $factory = $this->rollbackStepFactory;

        return $factory();
    }
}
