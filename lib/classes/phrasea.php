<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\CollectionRepositoryRegistry;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @deprecated This class doesn't know it yet, but it's already dead.
 */
class phrasea
{
    private static $_bas2sbas = false;
    private static $_sbas_names = false;
    private static $_sbas_labels = false;
    private static $_coll2bas = false;
    private static $_bas2coll = false;
    private static $_bas_labels = false;
    private static $_sbas_params = false;

    const CACHE_BAS_2_SBAS = 'bas_2_sbas';
    const CACHE_COLL_2_BAS = 'coll_2_bas';
    const CACHE_BAS_2_COLL = 'bas_2_coll';
    const CACHE_BAS_LABELS = 'bas_labels';
    const CACHE_SBAS_NAMES = 'sbas_names';
    const CACHE_SBAS_LABELS = 'sbas_labels';
    const CACHE_SBAS_FROM_BAS = 'sbas_from_bas';
    const CACHE_SBAS_PARAMS = 'sbas_params';

    /**
     * @param Application $app
     * @return bool
     * @deprecated I don't know who wrote this, nor why
     */
    public static function clear_sbas_params(Application $app)
    {
        throw new \BadMethodCallException('Sorry, this method is dead.');
    }

    /**
     * @param Application $app
     * @return array|bool
     * @throws \Doctrine\DBAL\DBALException
     * @deprecated I don't know who wrote this, nor why
     */
    public static function sbas_params(Application $app)
    {
        throw new \BadMethodCallException('Sorry, this method is dead.');
    }

    /**
     * @param TranslatorInterface $translator
     * @param $array_modules
     * @return array
     * @deprecated I don't know who wrote this, nor why
     */
    public static function modulesName(TranslatorInterface $translator, $array_modules)
    {
        $array = [];

        $modules = [
            1 => $translator->trans('admin::monitor: module production'),
            2 => $translator->trans('admin::monitor: module client'),
            3 => $translator->trans('admin::monitor: module admin'),
            4 => $translator->trans('admin::monitor: module report'),
            5 => $translator->trans('admin::monitor: module thesaurus'),
            6 => $translator->trans('admin::monitor: module comparateur'),
            7 => $translator->trans('admin::monitor: module validation'),
            8 => $translator->trans('admin::monitor: module upload')
        ];

        foreach ($array_modules as $a) {
            if (isset($modules[$a]))
                $array[] = $modules[$a];
        }

        return $array;
    }

    /**
     * @param Application $app
     * @param $base_id
     * @return bool|int
     * @deprecated I don't know who wrote this, nor why
     */
    public static function sbasFromBas(Application $app, $base_id)
    {
        /** @var CollectionReferenceRepository $repository */
        $repository = $app['repo.collection-references'];
        $reference = $repository->find($base_id);

        if ($reference) {
            return $reference->getDataboxId();
        }

        return false;
    }

    /**
     * @param $sbas_id
     * @param $coll_id
     * @param Application $app
     * @return bool|int
     * @deprecated I don't know who wrote this, nor why
     */
    public static function baseFromColl($sbas_id, $coll_id, Application $app)
    {
        /** @var CollectionReferenceRepository $repository */
        $repository = $app['repo.collection-references'];
        $reference = $repository->findByCollectionId($sbas_id, $coll_id);

        if ($reference) {
            return $reference->getBaseId();
        }

        return false;
    }

    /**
     * @deprecated I don't know who wrote this, nor why
     */
    public static function reset_baseDatas()
    {
        self::$_coll2bas = self::$_bas2coll = self::$_bas_labels = self::$_bas2sbas = null;
    }

    /**
     * @deprecated I don't know who wrote this, nor why
     */
    public static function reset_sbasDatas()
    {
        self::$_sbas_names = self::$_sbas_labels = self::$_sbas_params = self::$_bas2sbas = null;
    }

    /**
     * @param Application $app
     * @param $base_id
     * @return bool|int
     * @deprecated I don't know who wrote this, nor why
     */
    public static function collFromBas(Application $app, $base_id)
    {
        /** @var CollectionReferenceRepository $repository */
        $repository = $app['repo.collection-references'];
        $reference = $repository->find($base_id);

        if ($reference) {
            return $reference->getCollectionId();
        }

        return false;
    }

    /**
     * @param $sbas_id
     * @param Application $app
     * @return string
     * @deprecated I don't know who wrote this, nor why
     */
    public static function sbas_names($sbas_id, Application $app)
    {
        return $app->getApplicationBox()
            ->get_databox($sbas_id)
            ->getDataObject()
            ->getViewName(true);
    }

    /**
     * @param $sbas_id
     * @param Application $app
     * @return string
     * @deprecated I don't know who wrote this, nor why
     */
    public static function sbas_labels($sbas_id, Application $app)
    {
        return $app->getApplicationBox()
            ->get_databox($sbas_id)
            ->getDataObject()
            ->getLabels();
    }

    /**
     * @param $base_id
     * @param Application $app
     * @return string
     * @deprecated I don't know who wrote this, nor why
     */
    public static function bas_labels($base_id, Application $app)
    {
        /** @var CollectionReferenceRepository $repository */
        $referenceRepository = $app['repo.collection-references'];
        /** @var CollectionReference $reference */
        $reference = $referenceRepository->find($base_id);

        if (! $reference) {
            return 'Unknown collection';
        }

        /** @var CollectionRepositoryRegistry $collectionRepositoryRegistry */
        $collectionRepositoryRegistry = $app['repo.collections-registry'];
        $collectionRepository = $collectionRepositoryRegistry->getRepositoryByDatabox($reference->getDataboxId());

        $collection = $collectionRepository->find($reference->getCollectionId());

        if (! $collection) {
            throw new \RuntimeException('Missing collection ' . $base_id . '.');
        }

        $labels = $collection->getCollection()->getLabels();

        if (isset($labels[$app['locale']])) {
            return $labels[$app['locale']];
        }

        return 'Unknown collection';
    }
}
