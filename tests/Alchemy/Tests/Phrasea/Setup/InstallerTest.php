<?php

namespace Alchemy\Tests\Phrasea\Setup;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Setup\Installer;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Alchemy\Phrasea\Core\Configuration\Compiler;

/**
 * @group functional
 * @group legacy
 */
class InstallerTest extends \PhraseanetTestCase
{
    public function testInstall()
    {
        $app = new Application(Application::ENV_TEST);

        $app->bindRoutes();

        $parser = new Parser();
        $config = $parser->parse(file_get_contents(__DIR__ . '/../../../../../config/configuration.yml'));
        $credentials = $config['main']['database'];

        $configFile = __DIR__ . '/configuration.yml';
        $compiledFile = __DIR__ . '/configuration.yml.php';

        @unlink($configFile);
        @unlink($compiledFile);

        $app['configuration.store'] = $app->share(function() use ($configFile, $compiledFile) {
            return new Configuration(new Yaml(), new Compiler(), $configFile, $compiledFile, true);
        });

        $app['conf'] = $app->share(function() use($app) {
            return new PropertyAccess($app['configuration.store']);
        });

        $app['phraseanet.appbox'] = $app->share(function() use($app) {
            return new \appbox($app);
        });

        $abConn = $app['dbal.provider']([
            'driver'   => 'pdo_sqlite',
            'path'     => sprintf('%s/%s', $app['tmp.path'], 'ab-test'),
        ]);

        $dbConn = $app['dbal.provider']([
            'driver'   => 'pdo_sqlite',
            'path'     => sprintf('%s/%s', $app['tmp.path'], 'db-test'),
        ]);

        $dataPath = __DIR__ . '/../../../../../datas/';

        $installer = new Installer($app);
        $installer->install(uniqid('admin') . '@example.com', 'sdfsdsd', $abConn, 'http://local.phrasea.test.installer/', $dataPath, $dbConn, 'en');

        $this->assertTrue($app['configuration.store']->isSetup());
        $this->assertTrue($app['phraseanet.configuration-tester']->isUpToDate());

        $databox = current($app->getDataboxes());
        $this->assertContains('<path>'.realpath($dataPath).'/db_setup_test/subdefs</path>', $databox->get_structure());

        $conf = $app['configuration.store']->getConfig();
        $this->assertArrayHasKey('main', $conf);
        $this->assertArrayHasKey('key', $conf['main']);
        $this->assertGreaterThan(10, strlen($conf['main']['key']));

        @unlink($configFile);
        @unlink($compiledFile);
    }
}
