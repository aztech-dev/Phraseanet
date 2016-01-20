<?php

namespace Alchemy\Tests\Phrasea\Cache;

use Alchemy\Phrasea\Cache\Manager;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Alchemy\Phrasea\Exception\RuntimeException;

/**
 * @group functional
 * @group legacy
 */
class ManagerTest extends \PhraseanetTestCase
{
    private $file;

    public function setUp()
    {
        parent::setUp();
        $this->file = __DIR__ . '/tmp-file.php';
        $this->compiler = new Compiler();
        $this->clean();
    }

    public function tearDown()
    {
        $this->clean();
        parent::tearDown();
    }

    private function clean()
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    private function createEmptyRegistry()
    {
        file_put_contents($this->file, $this->compiler->compile([]));
    }

    public function testFactoryCreateOne()
    {
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getMockBuilder('Alchemy\Phrasea\Cache\Factory')
            ->disableOriginalConstructor()
            ->getMock();

        $cache = $this->getMock('Alchemy\Phrasea\Cache\Cache');

        $name = 'array';
        $values = ['option', 'value'];

        $factory->expects($this->once())
            ->method('create')
            ->with($name, $values)
            ->will($this->returnValue($cache));

        $this->createEmptyRegistry();

        $manager = new Manager($logger, $factory);
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
    }

    public function testUnknownCacheReturnsArrayCacheAndLogs()
    {
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getMockBuilder('Alchemy\Phrasea\Cache\Factory')
            ->disableOriginalConstructor()
            ->getMock();

        $logger->expects($this->once())
            ->method('error');

        $cache = $this->getMock('Alchemy\Phrasea\Cache\Cache');

        $name = 'unknown';
        $values = ['option', 'value'];

        $factory->expects($this->at(0))
            ->method('create')
            ->with($name, $values)
            ->will($this->throwException(new RuntimeException('Unknown cache type')));

        $factory->expects($this->at(1))
            ->method('create')
            ->with('array', [])
            ->will($this->returnValue($cache));

        $manager = new Manager($logger, $factory);
        $this->assertSame($cache, $manager->factory('custom-type', $name, $values));
    }
}
