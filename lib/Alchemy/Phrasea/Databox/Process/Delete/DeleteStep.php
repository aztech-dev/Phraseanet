<?php

namespace Alchemy\Phrasea\Databox\Process\Delete;

class DeleteStep
{
    /**
     * @var \appbox
     */
    private $applicationBox;

    public function __construct(\appbox $appbox)
    {
        $this->applicationBox = $appbox;
    }

    /**
     * @param \databox $databox
     * @return void
     */
    public function execute(\databox $databox)
    {
        $databoxVo = $databox->getDataObject();

        $sql = 'DROP DATABASE `' . $databoxVo->getDatabase() . '`';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $this->applicationBox->delete_data_from_cache(\appbox::CACHE_LIST_BASES);
    }
}
