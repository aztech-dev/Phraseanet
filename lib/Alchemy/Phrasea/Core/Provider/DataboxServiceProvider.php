<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\Database\DatabaseMaintenanceService;
use Alchemy\Phrasea\Core\Database\TableBuilder\DoctrineSqliteTableBuilder;
use Alchemy\Phrasea\Core\Database\TableBuilder\MySqlTableBuilder;
use Alchemy\Phrasea\Core\Database\TableBuilderFactory;
use Alchemy\Phrasea\Databox\CachingDataboxRepository;
use Alchemy\Phrasea\Databox\DataboxFactory;
use Alchemy\Phrasea\Databox\DataboxService;
use Alchemy\Phrasea\Databox\DbalDataboxRepository;
use Alchemy\Phrasea\Databox\Process\AddAdmin;
use Alchemy\Phrasea\Databox\Process\Create;
use Alchemy\Phrasea\Databox\Process\Delete;
use Alchemy\Phrasea\Databox\Process\DataboxProcessRegistry;
use Alchemy\Phrasea\Databox\Process\Reindex\ReindexStep;
use Alchemy\Phrasea\Databox\Process\ReplaceStructure\ReplaceStructureStep;
use Alchemy\Phrasea\Databox\Process\StepRegistry;
use Alchemy\Phrasea\Databox\Process\Mount;
use Alchemy\Phrasea\Databox\Process\Unmount;
use Doctrine\DBAL\Connection;
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

        $app['databoxes.repo'] = function () use ($app) {
            return $app['repo.databoxes'];
        };

        $app['databoxes.service'] = $app->share(function (PhraseaApplication $app) {
            $processRegistry = new DataboxProcessRegistry();

            $processRegistry->registerProcess(Create\CreateStep::class, $this->buildCreateStepRegistry($app));
            $processRegistry->registerProcess(Mount\MountStep::class, $this->buildMountStepRegistry($app));
            $processRegistry->registerProcess(Unmount\UnmountStep::class, $this->buildUnmountStepRegistry($app));
            $processRegistry->registerProcess(Delete\DeleteStep::class, $this->buildDeleteStepRegistry($app));
            $processRegistry->registerProcess(AddAdmin\AddAdminStep::class, $this->buildAddAdminStepRegistry($app));
            $processRegistry->registerProcess(ReindexStep::class, $this->buildReindexStepRegistry($app));
            $processRegistry->registerProcess(
                ReplaceStructureStep::class,
                $this->buildReplaceStructureStepRegistry($app)
            );

            return new DataboxService(
                $app['repo.databoxes'],
                $app['dispatcher'],
                $processRegistry,
                $app['dbal.provider']
            );
        });

        $app['databoxes.maintenance_service'] = $app->protect(function (Connection $connection) use ($app) {
            $tableBuilderFactory = new TableBuilderFactory();

            $tableBuilderFactory->addDriverFactory('pdo_mysql', function () use ($app) {
                return new MySqlTableBuilder($app['auth.password-encoder'], $app['random.medium']);
            });

            $tableBuilderFactory->addDriverFactory('pdo_sqlite', function () use ($app) {
                return new DoctrineSqliteTableBuilder($app['auth.password-encoder'], $app['random.medium']);
            });

            return new DatabaseMaintenanceService($app, $connection, $tableBuilderFactory);
        });
    }

    private function buildCreateStepRegistry(PhraseaApplication $app)
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

        $registry->addStepFactory(function () use ($app) {
            return new Create\PopulateDataboxStep($app['conf']);
        });

        $registry->addStepFactory(function () {
            return new Create\SetCurrentDatabaseStep();
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

    private function buildDeleteStepRegistry(PhraseaApplication $app)
    {
        $registry = new StepRegistry();

        $registry->addStepFactory(function () use ($app) {
            return new Delete\DeleteStep($app->getApplicationBox(), $app['repo.databoxes']);
        });

        return $registry;
    }

    private function buildAddAdminStepRegistry(PhraseaApplication $app)
    {
        $registry = new StepRegistry();

        $registry->addStepFactory(function () use ($app) {
            return new AddAdmin\GrantDataboxAdminRights($app->getAclProvider());
        });

        $registry->addStepFactory(function () use ($app) {
            return new AddAdmin\GrantCollectionAdminRights($app->getApplicationBox(), $app->getAclProvider());
        });

        return $registry;
    }

    private function buildReindexStepRegistry(PhraseaApplication $app)
    {
        $registry = new StepRegistry();

        $registry->addStepFactory(function() {
            return new ReindexStep();
        });

        return $registry;
    }

    private function buildReplaceStructureStepRegistry(PhraseaApplication $app)
    {
        $registry = new StepRegistry();

        $registry->addStepFactory(function () {
            return new ReplaceStructureStep();
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

    }
}
