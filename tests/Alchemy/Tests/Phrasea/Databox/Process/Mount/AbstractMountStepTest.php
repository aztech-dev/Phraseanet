<?php

namespace Alchemy\Tests\Phrasea\Databox\Process\Mount;

use Alchemy\Phrasea\Databox\Process\Mount\AbstractMountStep;
use Alchemy\Phrasea\Databox\Process\Mount\MountStep;
use Doctrine\DBAL\Connection;

class AbstractMountStepTest extends \PHPUnit_Framework_TestCase
{
    public function testRunStepsRunsAllDefinedSteps()
    {
        $databox = $this->prophesize(\databox::class)->reveal();
        $connection = $this->prophesize(Connection::class);

        $steps = [];

        $firstMock = $this->prophesize(MountStep::class);
        $steps[] = $firstMock->reveal();

        $secondMock = $this->prophesize(MountStep::class);
        $firstMock->setNext($secondMock->reveal())->shouldBeCalled();
        $steps[] = $secondMock->reveal();

        $thirdMock = $this->prophesize(MountStep::class);
        $secondMock->setNext($thirdMock->reveal())->shouldBeCalled();
        $steps[] = $thirdMock->reveal();

        $firstMock->execute($connection->reveal())->shouldBeCalled()->willReturn($databox);

        AbstractMountStep::runSteps($connection->reveal(), $steps);
    }
}
