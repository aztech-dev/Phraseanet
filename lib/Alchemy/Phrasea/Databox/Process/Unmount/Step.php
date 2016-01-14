<?php

namespace Alchemy\Phrasea\Databox\Process\Unmount;

interface Step 
{

    public function execute(\databox $databox);
}
