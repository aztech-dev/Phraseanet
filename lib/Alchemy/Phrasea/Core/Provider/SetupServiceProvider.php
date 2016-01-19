<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Setup\Installer;
use Alchemy\Phrasea\Setup\SetupService;
use Alchemy\Phrasea\Setup\Step\CreateAdminUserStep;
use Alchemy\Phrasea\Setup\Step\CreateApplicationBoxStep;
use Alchemy\Phrasea\Setup\Step\CreateConfigurationStep;
use Alchemy\Phrasea\Setup\Step\CreateDataboxStep;
use Alchemy\Phrasea\Setup\Step\CreateDefaultUsersStep;
use Alchemy\Phrasea\Setup\Step\CreateJobsStep;
use Alchemy\Phrasea\Setup\Step\RollbackInstallationStep;
use Alchemy\Phrasea\Setup\StepRegistry;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SetupServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        if (!$app instanceof PhraseaApplication) {
            throw new \LogicException('Expects $app to be an instance of Phraseanet application');
        }

        $app['setup.service'] = $app->share(function (PhraseaApplication $app) {
            $connectionFactory = $app['dbal.provider'];
            $dispatcher = $app['dispatcher'];

            $stepRegistry = $this->buildStepRegistry($app);

            return new SetupService($connectionFactory, $stepRegistry, $dispatcher);
        });
    }

    public function buildStepRegistry(PhraseaApplication $app)
    {
        $registry = new StepRegistry();

        $rollbackStepFactory = function () use ($app) {
            static $step;

            if ($step === null) {
                $step = new RollbackInstallationStep($app['configuration.store']);
            }

            return $step;
        };

        $registry->setRollbackStepFactory($rollbackStepFactory);
        $registry->addStepFactory($rollbackStepFactory);

        $registry->addStepFactory(function () use ($app) {
            return new CreateConfigurationStep(
                $app['configuration.store'],
                $app['registry.manipulator'],
                $app['random.medium'],
                $app['root.path']
            );
        });

        $registry->addStepFactory(function () use ($app) {
            return new CreateApplicationBoxStep($app, $app['orm.em']);
        });

        $registry->addStepFactory(function () use ($app) {
            return new CreateAdminUserStep($app['manipulator.user'], $app['authentication']);
        });

        $registry->addStepFactory(function () use ($app) {
            return new CreateDefaultUsersStep($app['manipulator.user']);
        });

        $registry->addStepFactory(function () use ($app) {
            return new CreateDataboxStep($app->getAclProvider(), $app['databoxes.service'], $app['repo.users']);
        });

        $registry->addStepFactory(function () use ($app) {
            return new CreateJobsStep($app['task-manager.job-factory'], $app['manipulator.task'], $app['conf']);
        });

        return $registry;
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        // NO-OP
    }
}
