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
     * @param bool $success
     * @param string $reason
     */
    public function __construct($success = true, $reason = '')
    {
        $this->successful = $success;
        $this->reason = $reason;
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
}
