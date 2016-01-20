<?php

namespace Alchemy\Phrasea\Collection\Repository;

use Alchemy\Phrasea\Collection\Collection;
use Alchemy\Phrasea\Collection\CollectionRepository;

class ArrayCacheCollectionRepository implements CollectionRepository
{
    /**
     * @var CollectionRepository
     */
    private $collectionRepository;

    /**
     * @var \collection[]
     */
    private $collectionCache = null;

    /**
     * @var string[]
     */
    private $mountableCollectionsCache = null;

    /**
     * @param CollectionRepository $collectionRepository
     */
    public function __construct(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @return string[] The names of unmounted collections indexed by their collection ID.
     */
    public function findUnmountedCollections()
    {
        if ($this->mountableCollectionsCache === null) {
            $this->mountableCollectionsCache = $this->collectionRepository->findUnmountedCollections();
        }

        return $this->mountableCollectionsCache;
    }

    /**
     * @return \collection[]
     */
    public function findActivableCollections()
    {
        return array_filter($this->findAll(), function (\collection $collection) {
            return ! $collection->getReference()->isActive();
        });
    }

    /**
     * @return \collection[]
     */
    public function findAll()
    {
        if ($this->collectionCache === null) {
            $this->collectionCache = $this->collectionRepository->findAll();
        }

        return $this->collectionCache;
    }

    /**
     * @param int $collectionId
     * @return \collection|null
     */
    public function find($collectionId)
    {
        $collections = $this->findAll();

        if (isset($collections[$collectionId])) {
            return $collections[$collectionId];
        }

        return null;
    }

    public function save(Collection $collection)
    {
        $this->collectionRepository->save($collection);

        $this->collectionCache = null;
        $this->mountableCollectionsCache = null;
    }

    public function delete(Collection $collection)
    {
        $this->collectionRepository->delete($collection);

        if (isset($this->collectionCache[$collection->getCollectionId()])) {
            unset($this->collectionCache[$collection->getCollectionId()]);
        }

        $this->mountableCollectionsCache = null;
    }
}
