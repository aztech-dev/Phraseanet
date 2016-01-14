<?php

namespace Alchemy\Phrasea\Databox\Process\Unmount;

interface UnmountStep
{

    /**
     * @param \databox $databox
     * @return void
     */
    public function execute(\databox $databox);
}
