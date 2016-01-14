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

    public function __construct(\appbox $applicationBox)
    {
        $this->applicationBox = $applicationBox;
    }

    public function execute(Connection $connection, \SplFileInfo $dataTemplate)
    {
        DataboxConnectionValidator::validateConnection($this->applicationBox, $connection);

        return $this->runNext($connection, $dataTemplate);
    }
}
