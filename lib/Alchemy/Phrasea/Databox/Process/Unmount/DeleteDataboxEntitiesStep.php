<?php

namespace Alchemy\Phrasea\Databox\Process\Unmount;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Alchemy\Phrasea\Model\Repositories\StoryWZRepository;
use Doctrine\ORM\EntityManager;

class DeleteDataboxEntitiesStep implements UnmountStep
{

    /**
     * @var Application
     */
    private $application;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var StoryWZRepository
     */
    private $storyWzRepository;

    /**
     * @var BasketElementRepository
     */
    private $basketElementRepository;

    /**
     * @param Application $application
     * @param EntityManager $entityManager
     * @param StoryWZRepository $storyWZRepository
     * @param BasketElementRepository $basketElementRepository
     */
    public function __construct(
        Application $application,
        EntityManager $entityManager,
        StoryWZRepository $storyWZRepository,
        BasketElementRepository $basketElementRepository
    ) {
        $this->application = $application;
        $this->entityManager = $entityManager;
        $this->storyWzRepository = $storyWZRepository;
        $this->basketElementRepository = $basketElementRepository;
    }

    public function execute(\databox $databox)
    {
        foreach ($this->storyWzRepository->findByDatabox($this->application, $databox) as $story) {
            $this->entityManager->remove($story);
        }

        foreach ($this->basketElementRepository->findElementsByDatabox($databox) as $element) {
            $this->entityManager->remove($element);
        }

        $this->entityManager->flush();
    }
}
