<?php

namespace Alchemy\Phrasea\Setup;

class StepRegistry 
{
    /**
     * @var callable[]
     */
    private $factories = [];

    /**
     * @param callable $stepFactory
     */
    public function addStepFactory(callable $stepFactory)
    {
        $this->factories[] = $stepFactory;
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
}
