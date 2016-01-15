<?php

namespace Alchemy\Phrasea\Databox\Process;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class DataboxProcessRegistry
{

    /**
     * @var StepRegistry[]
     */
    private $registries = [];

    public function registerProcess($stepClass, StepRegistry $registry)
    {
        if (! $this->isValidTypename($stepClass)) {
            throw new InvalidArgumentException("Class '$stepClass' not found.");
        }

        $this->registries[$stepClass] = $registry;
    }

    public function getProcessSteps($stepClassName)
    {
        if (! isset($this->registries[$stepClassName])) {
            throw new InvalidArgumentException("No process for step class '$stepClassName'");
        }

        return $this->registries[$stepClassName]->getSteps();
    }

    /**
     * @param $stepClass
     * @return bool
     */
    protected function isValidTypename($stepClass)
    {
        return interface_exists($stepClass, true) || class_exists($stepClass, true);
    }
}
