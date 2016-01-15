<?php

namespace Alchemy\Phrasea\Databox\Process\Mount;

use Alchemy\Phrasea\Databox\Util\DataboxConnectionValidator;
use Doctrine\DBAL\Connection;

class ValidateDataboxConnectionStep extends AbstractMountStep
{
    /**
     * @var \appbox
     */
    private $applicationBox;

    /**
     * @param \appbox $applicationBox
     */
    public function __construct(\appbox $applicationBox)
    {
        $this->applicationBox = $applicationBox;
    }

    /**
     * @param Connection $connection
     * @return \databox
     */
    public function execute(Connection $connection)
    {
        DataboxConnectionValidator::validateConnection($this->applicationBox, $connection);

        return $this->runNext($connection);
    }
}
