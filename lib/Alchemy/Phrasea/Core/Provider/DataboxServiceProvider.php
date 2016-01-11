<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Databox\CachingDataboxRepository;
use Alchemy\Phrasea\Databox\DataboxFactory;
use Alchemy\Phrasea\Databox\DataboxService;
use Alchemy\Phrasea\Databox\DbalDataboxRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;

class DataboxServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app
     */
    public function register(Application $app)
    {
        if (!$app instanceof PhraseaApplication) {
            throw new \LogicException('Expects $app to be an instance of Phraseanet application');
        }

        $app['repo.databoxes'] = $app->share(function (PhraseaApplication $app) {
            $factory = new DataboxFactory($app);
            $appbox = $app->getApplicationBox();

            $repository = new CachingDataboxRepository(
                new DbalDataboxRepository($appbox->get_connection(), $factory),
                $app['cache'],
                $appbox->get_cache_key($appbox::CACHE_LIST_BASES),
                $factory
            );

            $factory->setDataboxRepository($repository);

            return $repository;
        });

        $app['databoxes.service'] = $app->share(function (PhraseaApplication $app) {
             return new DataboxService($app, $app['repo.databoxes']);
        });
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }
}
