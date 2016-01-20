<?php

namespace Alchemy\Phrasea\Databox\Field;

use Doctrine\Common\Cache\Cache;

class CachingDataboxFieldRepository implements DataboxFieldRepository
{

    /**
     * @var DataboxFieldRepository
     */
    private $repository;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * @var DataboxFieldFactory
     */
    private $factory;

    /**
     * @param DataboxFieldRepository $repository
     * @param DataboxFieldFactory $factory
     * @param Cache $cache
     * @param string $cacheKey
     */
    public function __construct(
        DataboxFieldRepository $repository,
        DataboxFieldFactory $factory,
        Cache $cache,
        $cacheKey
    ) {
        $this->repository = $repository;
        $this->factory = $factory;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Find all fields
     *
     * @return \databox_field[]
     */
    public function findAll()
    {
        $fields = $this->cache->fetch($this->cacheKey);

        if ($fields !== false && is_array($fields)) {
            return $this->factory->createMany($fields);
        }

        $fields = $this->repository->findAll();
        $rows = [];

        foreach ($fields as $field) {
            $rows[] = $field->getRowData();
        }

        $this->cache->save($this->cacheKey, $rows);

        return $fields;
    }
}
