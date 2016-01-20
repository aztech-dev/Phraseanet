<?php

namespace Alchemy\Phrasea\Databox\Process\Unmount;

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
     * @return \Generator|Step[]
     */
    public function getSteps()
    {
        foreach ($this->stepFactories as $stepFactory) {
            yield $stepFactory();
        }
    }
}
