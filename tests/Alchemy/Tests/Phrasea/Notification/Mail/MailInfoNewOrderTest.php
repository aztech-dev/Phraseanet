<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoNewOrder;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoNewOrder
 */
class MailInfoNewOrderTest extends MailTestCase
{
    public function testSetUser()
    {
        $this->assertEquals('%user% has ordered documents', $this->getMail()->getMessage());
    }

    public function testShouldThrowALogicExceptionIfNoUserProvided()
    {
        $mail =  MailInfoNewOrder::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function getMail()
    {
        $mail =  MailInfoNewOrder::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $user = $this->createUserMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setUser($user);

        return $mail;
    }
}
