<?php

namespace Alchemy\Phrasea\Setup\Command;

class InstallCommandResult 
{

    /**
     * @var bool
     */
    private $successful;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var string[]
     */
    private $reasonContext;

    /**
     * @param bool $success
     * @param string $reason
     * @param string[] $reasonContext
     */
    public function __construct($success = true, $reason = '', $reasonContext = [])
    {
        $this->successful = $success;
        $this->reason = $reason;
        $this->reasonContext = $reasonContext;
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->successful;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return string[]
     */
    public function getReasonContext()
    {
        return $this->reasonContext;
    }
}
