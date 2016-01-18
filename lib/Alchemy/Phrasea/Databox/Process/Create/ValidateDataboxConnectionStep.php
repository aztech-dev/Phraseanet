<?php

namespace Alchemy\Phrasea\Databox\Process\Create;

use Alchemy\Phrasea\Databox\Util\DataboxConnectionValidator;
use Doctrine\DBAL\Connection;

class ValidateDataboxConnectionStep extends AbstractCreateStep
{

    /**
     * @var \appbox
     */
    private $applicationBox;

    /**
     * @var DataboxConnectionValidator
     */
    private $connectionValidator;

    public function __construct(\appbox $applicationBox, DataboxConnectionValidator $connectionValidator = null)
    {
        $this->applicationBox = $applicationBox;
        $this->connectionValidator = $connectionValidator ?: new DataboxConnectionValidator();
    }

    public function execute(Connection $connection, \SplFileInfo $dataTemplate)
    {
        $this->connectionValidator->validateConnection($this->applicationBox, $connection);

        return $this->runNext($connection, $dataTemplate);
    }
}
