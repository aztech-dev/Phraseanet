<?php

namespace Alchemy\Phrasea\Databox;

interface DataboxMountRepository
{

    /**
     * @return DataboxMount[]
     */
    public function findAll();
}
