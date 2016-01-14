<?php

namespace Alchemy\Phrasea\Databox\Process;

use Alchemy\Phrasea\Databox\Process\Unmount\UnmountStep;

class StepRegistry
{

    /**
     * @var callable[]
     */
    private $stepFactories = [];

    /**
     * @param callable $stepFactory
     */
    public function addStepFactory(callable $stepFactory)
    {
        $this->stepFactories[] = $stepFactory;
    }

    /**
     * @return \Generator|UnmountStep[]
     */
    public function getSteps()
    {
        foreach ($this->stepFactories as $stepFactory) {
            yield $stepFactory();
        }
    }
}
