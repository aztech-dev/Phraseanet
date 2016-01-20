<?php

namespace Alchemy\Phrasea\Collection;

interface CollectionRepository
{

    /**
     * @return string[] The names of unmounted collections indexed by their collection ID.
     */
    public function findUnmountedCollections();

    /**
     * @return \collection[]
     */
    public function findActivableCollections();

    /**
     * @return \collection[]
     */
    public function findAll();

    /**
     * @param int $collectionId
     * @return \collection|null
     */
    public function find($collectionId);

    /**
     * @param Collection $collection
     * @return void
     */
    public function save(Collection $collection);

    /**
     * @param Collection $collection
     * @return void
     */
    public function delete(Collection $collection);
}
