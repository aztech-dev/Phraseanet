<?php

namespace Alchemy\Phrasea\Databox\Process\Unmount;

class UnmountCollectionsStep implements UnmountStep
{

    public function execute(\databox $databox)
    {
        foreach ($databox->get_collections() as $collection) {
            $collection->unmount();
        }
    }
}