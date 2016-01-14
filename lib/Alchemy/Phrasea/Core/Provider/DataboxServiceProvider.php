<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Databox\CachingDataboxRepository;
use Alchemy\Phrasea\Databox\DataboxFactory;
use Alchemy\Phrasea\Databox\DataboxService;
use Alchemy\Phrasea\Databox\DbalDataboxRepository;
use Alchemy\Phrasea\Databox\Process\Create;
use Alchemy\Phrasea\Databox\Process\DataboxProcessRegistry;
use Alchemy\Phrasea\Databox\Process\StepRegistry;
use Alchemy\Phrasea\Databox\Process\Mount;
use Alchemy\Phrasea\Databox\Process\Unmount;
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
            $process = new DataboxProcessRegistry();

            $process->registerProcess(Create\CreateStep::class, $this->buildCreateStepRegisty($app));
            $process->registerProcess(Mount\MountStep::class, $this->buildMountStepRegistry($app));
            $process->registerProcess(Unmount\UnmountStep::class, $this->buildUnmountStepRegistry($app));

            return new DataboxService($app, $app['repo.databoxes'], $app['dispatcher'], $process);
        });
    }

    private function buildCreateStepRegisty(PhraseaApplication $app)
    {
        $registry = new StepRegistry();

        $registry->addStepFactory(function () {
            return new Create\ValidateDataTemplateStep();
        });

        $registry->addStepFactory(function () use ($app) {
           return new Create\ValidateDataboxConnectionStep($app->getApplicationBox());
        });

        $registry->addStepFactory(function () {
           return new Create\CreateDatabaseForDataboxStep();
        });

        $registry->addStepFactory(function () {
            return new Create\SetCurrentDatabaseStep();
        });

        $registry->addStepFactory(function () use ($app) {
            return new Create\PopulateDataboxStep($app['conf']);
        });

        $registry->addStepFactory(function () use ($app) {
            return new Create\CreateDataboxStep($app->getApplicationBox(), $app['repo.databoxes']);
        });

        return $registry;
    }

    private function buildMountStepRegistry(PhraseaApplication $app)
    {
        $registry = new StepRegistry();

        $registry->addStepFactory(function () use ($app) {
            return new Mount\ValidateDataboxConnectionStep($app->getApplicationBox());
        });

        $registry->addStepFactory(function () use ($app) {
            return new Mount\MountDataboxStep($app->getApplicationBox(), $app['repo.databoxes']);
        });

        return $registry;
    }

    private function buildUnmountStepRegistry(PhraseaApplication $app)
    {
        $registry = new StepRegistry();

        $registry->addStepFactory(function () {
            return new Unmount\UnmountCollectionsStep();
        });

        $registry->addStepFactory(function () use ($app) {
            return new Unmount\DeleteUserRightsStep($app, $app->getAclProvider());
        });

        $registry->addStepFactory(function () use ($app) {
            return new Unmount\DeleteDataboxEntitiesStep(
                $app,
                $app['orm.em'],
                $app['repo.story-wz'],
                $app['repo.basket-elements']
            );
        });

        $registry->addStepFactory(function () use ($app) {
            return new Unmount\DeleteDataboxReferencesStep($app->getApplicationBox(), $app['conf']);
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
        $app['databoxes.repo'] = $app['repo.databoxes'];
    }
}
