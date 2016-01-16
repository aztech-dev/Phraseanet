<?php

namespace Alchemy\Phrasea\Databox\Process\Delete;

use Alchemy\Phrasea\Databox\DataboxRepository;

class DeleteStep
{
    /**
     * @var \appbox
     */
    private $applicationBox;

    /**
     * @var DataboxRepository
     */
    private $databoxRepository;

    public function __construct(\appbox $appbox, DataboxRepository $repository)
    {
        $this->applicationBox = $appbox;
        $this->databoxRepository = $repository;
    }

    /**
     * @param \databox $databox
     * @return void
     */
    public function execute(\databox $databox)
    {
        $databoxVo = $databox->getDataObject();

        $this->databoxRepository->unmount($databoxVo);
        $this->databoxRepository->dropDatabase($databox->get_connection());

        $this->applicationBox->delete_data_from_cache(\appbox::CACHE_LIST_BASES);
    }
}
