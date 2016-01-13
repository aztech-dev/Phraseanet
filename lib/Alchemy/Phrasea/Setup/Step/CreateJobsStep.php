<?php

namespace Alchemy\Phrasea\Setup\Step;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Step;
use Alchemy\Phrasea\TaskManager\Job\Factory as JobFactory;
use Doctrine\DBAL\Connection;

class CreateJobsStep implements Step
{

    /**
     * @var PropertyAccess
     */
    private $configuration;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var TaskManipulator
     */
    private $taskManipulator;

    /**
     * @param JobFactory $jobFactory
     * @param TaskManipulator $taskManipulator
     * @param PropertyAccess $configuration
     */
    public function __construct(JobFactory $jobFactory, TaskManipulator $taskManipulator, PropertyAccess $configuration)
    {
        $this->configuration = $configuration;
        $this->jobFactory = $jobFactory;
        $this->taskManipulator = $taskManipulator;
    }

    public function getName()
    {
        return 'create-tasks';
    }

    public function execute(
        InitializeEnvironmentCommand $initializeEnvironmentCommand,
        Connection $appboxConnection,
        Connection $databoxConnection = null
    ) {
        if ($databoxConnection === null) {
            return;
        }

        $jobs = ['Subdefs', 'WriteMetadata'];

        foreach ($jobs as $jobName) {
            $job = $this->jobFactory->create($jobName);
            $this->taskManipulator->create(
                $job->getName(),
                $job->getJobId(),
                $job->getEditor()->getDefaultSettings($this->configuration),
                $job->getEditor()->getDefaultPeriod()
            );
        }
    }
}
