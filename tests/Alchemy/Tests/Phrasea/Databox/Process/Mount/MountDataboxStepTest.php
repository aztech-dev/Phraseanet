<?php

namespace Tests\Phrasea\Databox\Process\Mount;

use Alchemy\Phrasea\Databox\DataboxRepository;
use Alchemy\Phrasea\Databox\Process\Mount\MountDataboxStep;
use Doctrine\DBAL\Connection;

class MountDataboxStepTest extends \PHPUnit_Framework_TestCase
{
    public function testMountIsDelegatedToRepository()
    {
        $appbox = $this->prophesize(\appbox::class);
        $databox = $this->prophesize(\databox::class);

        $connection = $this->prophesize(Connection::class);
        $repository = $this->prophesize(DataboxRepository::class);

        $repository->mount($connection->reveal())->shouldBeCalled()->willReturn($databox->reveal());

        $step = new MountDataboxStep($appbox->reveal(), $repository->reveal());

        $this->assertSame($databox->reveal(), $step->execute($connection->reveal()));
    }
}
