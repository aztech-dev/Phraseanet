<?php

namespace Alchemy\Phrasea\Databox\Structure;

class StructureError 
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var string[]
     */
    private $context;

    /**
     * @param string $message
     * @param string[] $context
     */
    public function __construct($message, array $context = [])
    {
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string[]
     */
    public function getContext()
    {
        return $this->context;
    }
}
