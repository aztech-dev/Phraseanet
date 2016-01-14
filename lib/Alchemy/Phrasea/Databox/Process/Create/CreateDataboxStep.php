<?php

namespace Alchemy\Phrasea\Databox\Process\Create;

use Alchemy\Phrasea\Databox\DataboxRepository;
use Doctrine\DBAL\Connection;

class CreateDataboxStep extends AbstractCreateStep
{
    /**
     * @var \appbox
     */
    private $applicationBox;

    /**
     * @var DataboxRepository
     */
    private $databoxRepository;

    /**
     * @param \appbox $appbox
     * @param DataboxRepository $databoxRepository
     */
    public function __construct(\appbox $appbox, DataboxRepository $databoxRepository)
    {
        $this->applicationBox = $appbox;
        $this->databoxRepository = $databoxRepository;
    }

    /**
     * @param Connection $connection
     * @param \SplFileInfo $dataTemplate
     * @return \databox
     */
    public function execute(Connection $connection, \SplFileInfo $dataTemplate)
    {
        $databox = $this->databoxRepository->mount($connection);

        $this->applicationBox->delete_data_from_cache(\appbox::CACHE_LIST_BASES);
        \phrasea::reset_sbasDatas($this->applicationBox);

        return $databox;
    }


}
