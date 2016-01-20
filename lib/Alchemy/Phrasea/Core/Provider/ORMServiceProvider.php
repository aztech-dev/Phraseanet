<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Cache\Manager;
use Alchemy\Phrasea\Core\Connection\ConnectionPoolManager;
use Alchemy\Phrasea\Core\Profiler\ProfilingSqlLogger;
use Alchemy\Phrasea\Core\Profiler\SqlProfiler;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;
use Gedmo\DoctrineExtensions;
use Gedmo\Timestampable\TimestampableListener;
use RandomLib\Factory;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Sorien\DataCollector\DoctrineDataCollector;

class ORMServiceProvider implements ServiceProviderInterface
{

    private static $customTypes = [
        'blob' => '\Alchemy\Phrasea\Model\Types\Blob',
        'enum' => '\Alchemy\Phrasea\Model\Types\Enum',
        'longblob' => '\Alchemy\Phrasea\Model\Types\LongBlob',
        'varbinary' => '\Alchemy\Phrasea\Model\Types\VarBinary',
        'binary' => '\Alchemy\Phrasea\Model\Types\Binary',
        'binary_string' => '\Alchemy\Phrasea\Model\Types\BinaryString',
    ];

    public function register(Application $app)
    {
        if (! $app instanceof PhraseaApplication) {
            throw new \LogicException('Application must be an instance of Alchemy\Phrasea\Application');
        }

        $app['dbal.profiler'] = $app->share(function (PhraseaApplication $app) {
            $factory = new Factory();

            try {
                $cache = $app['cache'];
            }
            catch (\Exception $ex) {
                $cache = new ArrayCache();
            }

            return new SqlProfiler(
                $factory->getMediumStrengthGenerator(),
                $cache,
                'sql_profiler',
                $factory->getMediumStrengthGenerator()->generateString(32)
            );
        });

        $app['dbal.logger'] = $app->share(function (PhraseaApplication $app) {
            return new ProfilingSqlLogger($app['dbal.profiler']);
        });

        $app['dbal.connection_pool'] = $app->share(function () {
            return new ConnectionPoolManager();
        });

        $app['dbal.provider'] = $app->protect(function (array $parameters) use ($app) {
            /** @var ConnectionPoolManager $connectionPool */
            $connectionPool = $app['dbal.connection_pool'];
            /** @var Connection $connection */
            return $connectionPool->get($parameters);
        });

        $app['orm.em'] = $app->share(function (PhraseaApplication $app) {
            $connectionParameters = $this->buildConnectionParameters($app);
            $configuration = $this->buildConfiguration($app, $app['dbal.logger']);

            /** @var ConnectionPoolManager $connectionPool */
            $connectionPool = $app['dbal.connection_pool'];
            /** @var Connection $connection */
            $connection = $connectionPool->get($connectionParameters);

            $this->registerCustomTypes();
            $this->registerEventListeners($connection->getEventManager());

            return EntityManager::create($connection, $configuration, $connection->getEventManager());
        });
    }

    private function registerCustomTypes()
    {
        foreach (self::$customTypes as $name => $type) {
            if (Type::hasType($name)) {
                Type::overrideType($name, $type);
            } else {
                Type::addType($name, $type);
            }
        }
    }

    private function registerEventListeners(EventManager $eventManager)
    {
        $eventManager->addEventSubscriber(new TimestampableListener());
    }

    private function buildConnectionParameters(PhraseaApplication $app)
    {
        try {
            return $app['conf']->get(['main', 'database'], array());
        }
        catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @param PhraseaApplication $app
     * @return \Doctrine\ORM\Configuration
     */
    private function buildConfiguration(PhraseaApplication $app)
    {
        $devMode = $app->getEnvironment() == PhraseaApplication::ENV_DEV;
        $proxiesDirectory = $app['root.path'] . '/resources/proxies';
        $doctrineAnnotationsPath = $app['root.path'] . '/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php';

        $cache = $this->buildCache($app, 'EntityManager');
        $driver = $this->buildMetadataDriver($app, $cache, $doctrineAnnotationsPath);

        $configuration = Setup::createConfiguration($devMode, $proxiesDirectory, $cache);

        $configuration->setMetadataDriverImpl($driver);
        $configuration->addEntityNamespace('Phraseanet', 'Alchemy\Phrasea\Model\Entities');
        $configuration->setAutoGenerateProxyClasses($devMode);
        $configuration->setProxyNamespace('Alchemy\Phrasea\Model\Proxies');
        $configuration->setSQLLogger(new EchoSQLLogger());

        return $configuration;
    }

    private function buildCache(PhraseaApplication $app, $cacheType)
    {
        /** @var Cache $cache */
        static $cache;

        if ($cache !== null) {
            return $cache;
        }

        /** @var Manager $cacheManager */
        $cacheManager = $app['phraseanet.cache-service'];

        $cacheDriver = $this->getCacheDriver($app);
        $cacheOptions = $this->getCacheOptions($app);

        $cache = $cacheManager->factory($cacheType, $cacheDriver, $cacheOptions);

        return $cache;
    }

    /**
     * @param PhraseaApplication $app
     * @param Cache $cache
     * @param $doctrineAnnotationsPath
     * @return AnnotationDriver
     */
    private function buildMetadataDriver(PhraseaApplication $app, Cache $cache, $doctrineAnnotationsPath)
    {
        DoctrineExtensions::registerAnnotations();
        AnnotationRegistry::registerFile($doctrineAnnotationsPath);

        $reader = new AnnotationReader();
        $reader = new CachedReader($reader, $cache);

        $driver = new AnnotationDriver($reader, [
            $app['root.path'] . '/vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity/MappedSuperclass',
            $app['root.path'] . '/vendor/gedmo/doctrine-extensions/lib/Gedmo/Loggable/Entity/MappedSuperclass',
            $app['root.path'] . '/vendor/gedmo/doctrine-extensions/lib/Gedmo/Tree/Entity/MappedSuperclass',
            $app['root.path'] . '/lib/Alchemy/Phrasea/Model/Entities'
        ]);

        return $driver;
    }

    private function getCacheDriver(PhraseaApplication $app)
    {
        try {
            $conf = $app['conf']->get(['main', 'db-cache'], $app['conf']->get(['main', 'cache']));

            return isset($conf['type']) ? $conf['type'] : 'ArrayCache';
        }
        catch (\Exception $exception) {
            return 'ArrayCache';
        }
    }

    private function getCacheOptions(PhraseaApplication $app)
    {
        try {
            $conf = $app['conf']->get(['main', 'db-cache'], $app['conf']->get(['main', 'cache']));

            return isset($conf['options']) ? $conf['options'] : [];
        }
        catch (\Exception $exception) {
            return [];
        }
    }

    private function validateConnectionSettings(array $parameters)
    {
        if (!isset($parameters['driver'])) {
            $parameters['driver'] = 'pdo_mysql';
        }

        if (!isset($parameters['charset'])) {
            $parameters['charset'] = 'utf8';
        }


        switch ($parameters['driver']) {
            case 'pdo_mysql':
                foreach (array('user', 'password', 'host', 'dbname', 'port') as $param) {
                    if (!array_key_exists($param, $parameters)) {
                        throw new InvalidArgumentException(sprintf('Missing "%s" argument for database connection using driver %s', $param, $parameters['driver']));
                    }
                }
                break;
            case 'pdo_sqlite':
                if (!array_key_exists('path', $parameters)) {
                    throw new InvalidArgumentException(sprintf('Missing "path" argument for database connection using driver %s', $parameters['driver']));
                }
                break;
        }

        return $parameters;
    }

    public function boot(Application $app)
    {
        // NO-OP
    }
}
