<?php

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Core\Version\DataboxVersionRepository;
use Alchemy\Phrasea\Databox\Preference\ArrayCacheDataboxPreferencesRepository;
use Alchemy\Phrasea\Databox\Preference\CachingDataboxPreferencesRepository;
use Alchemy\Phrasea\Databox\Preference\DbalDataboxPreferencesRepository;
use Alchemy\Phrasea\Databox\Record\RecordDetailsRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Common\Cache\Cache;

class DataboxRepositoriesFactory
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $preferenceCacheKey;

    /**
     * @var string[]
     */
    private $locales;

    public function __construct(array $locales, Cache $cache, $rootCacheKey)
    {
        $this->locales = $locales;
        $this->cache = $cache;
        $this->preferenceCacheKey = $rootCacheKey . '_preferences';
    }

    /**
     * @param Connection $databoxConnection
     * @return DataboxVersionRepository
     */
    public function getVersionRepository(Connection $databoxConnection)
    {
        return new DataboxVersionRepository($databoxConnection);
    }

    public function getTermsOfUseRepository(Connection $databoxConnection)
    {
        $preferencesRepository = $this->getPreferencesRepository($databoxConnection);

        return new DataboxTermsOfUseRepository($preferencesRepository, $this->locales);
    }

    /**
     * @param Connection $databoxConnection
     * @return DataboxPreferencesRepository
     */
    public function getPreferencesRepository(Connection $databoxConnection)
    {
        $repository = new DbalDataboxPreferencesRepository($databoxConnection);

        if ($this->cache !== null) {
            $repository = new CachingDataboxPreferencesRepository($repository, $this->cache, $this->preferenceCacheKey);
        }

        return new ArrayCacheDataboxPreferencesRepository($repository);
    }

    /**
     * @param Connection $databoxConnection
     * @return RecordDetailsRepository
     */
    public function getRecordDetailsRepository(Connection $databoxConnection)
    {
        return new RecordDetailsRepository($databoxConnection);
    }
}
