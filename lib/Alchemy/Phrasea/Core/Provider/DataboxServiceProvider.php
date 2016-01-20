<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Databox\CachingDataboxRepository;
use Alchemy\Phrasea\Databox\DataboxFactory;
use Alchemy\Phrasea\Databox\DataboxService;
use Alchemy\Phrasea\Databox\DbalDataboxRepository;
use Alchemy\Phrasea\Databox\Process\Unmount\DeleteDataboxEntitiesStep;
use Alchemy\Phrasea\Databox\Process\Unmount\DeleteDataboxReferencesStep;
use Alchemy\Phrasea\Databox\Process\Unmount\DeleteUserRightsStep;
use Alchemy\Phrasea\Databox\Process\Unmount\StepRegistry;
use Alchemy\Phrasea\Databox\Process\Unmount\UnmountCollectionsStep;
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
            $unmountStepRegistry = $this->buildStepRegistry($app);

            return new DataboxService($app, $app['repo.databoxes'], $app['dispatcher'], $unmountStepRegistry);
        });
    }

    private function buildStepRegistry(PhraseaApplication $app)
    {
        $registry = new StepRegistry();

        $registry->addStepFactory(function () {
            return new UnmountCollectionsStep();
        });

        $registry->addStepFactory(function () use ($app) {
            return new DeleteUserRightsStep($app, $app->getAclProvider());
        });

        $registry->addStepFactory(function () use ($app) {
            return new DeleteDataboxEntitiesStep(
                $app,
                $app['orm.em'],
                $app['repo.story-wz'],
                $app['repo.basket-elements']
            );
        });

        $registry->addStepFactory(function () use ($app) {
            return new DeleteDataboxReferencesStep($app->getApplicationBox(), $app['conf']);
        });

        return $registry;
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
