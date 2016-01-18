<?php

namespace Tests\Phrasea\Databox\Process\Mount;

use Alchemy\Phrasea\Databox\Process\Mount\MountDataboxStep;
use Alchemy\Phrasea\Databox\Process\Mount\ValidateDataboxConnectionStep;
use Alchemy\Phrasea\Databox\Util\DataboxConnectionValidator;
use Doctrine\DBAL\Connection;

class ValidateDataboxConnectionStepTest extends \PHPUnit_Framework_TestCase
{
    public function testNextStepResultIsReturnByStep()
    {
        $appbox = $this->prophesize(\appbox::class);
        $databox = $this->prophesize(\databox::class);
        $connection = $this->prophesize(Connection::class);
        $nextStep = $this->prophesize(MountDataboxStep::class);
        $validator = $this->prophesize(DataboxConnectionValidator::class);

        $nextStep->execute($connection->reveal())
            ->willReturn($databox->reveal());

        $step = new ValidateDataboxConnectionStep($appbox->reveal(), $validator->reveal());
        $step->setNext($nextStep->reveal());

        $this->assertSame($databox->reveal(), $step->execute($connection->reveal()));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testConnectionValidationExceptionIsPropagated()
    {
        $appbox = $this->prophesize(\appbox::class);
        $databox = $this->prophesize(\databox::class);
        $connection = $this->prophesize(Connection::class);
        $nextStep = $this->prophesize(MountDataboxStep::class);
        $validator = $this->prophesize(DataboxConnectionValidator::class);

        $validator->validateConnection($appbox->reveal(), $connection->reveal())
            ->will(function () { throw new \RuntimeException(); });

        $nextStep->execute($connection->reveal())
            ->willReturn($databox->reveal());

        $step = new ValidateDataboxConnectionStep($appbox->reveal(), $validator->reveal());
        $step->setNext($nextStep->reveal());

        $step->execute($connection->reveal());
    }
}
