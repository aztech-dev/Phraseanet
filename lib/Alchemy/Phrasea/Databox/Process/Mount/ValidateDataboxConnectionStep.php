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
     * @var DataboxConnectionValidator
     */
    private $connectionValidator;

    /**
     * @param \appbox $applicationBox
     * @param DataboxConnectionValidator $connectionValidator
     */
    public function __construct(\appbox $applicationBox, DataboxConnectionValidator $connectionValidator = null)
    {
        $this->applicationBox = $applicationBox;
        $this->connectionValidator = $connectionValidator ?: new DataboxConnectionValidator();
    }

    /**
     * @param Connection $connection
     * @return \databox
     */
    public function execute(Connection $connection)
    {
        $this->connectionValidator->validateConnection($this->applicationBox, $connection);

        return $this->runNext($connection);
    }
}
