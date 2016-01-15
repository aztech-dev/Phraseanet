<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DataboxFactory
{
    /** @var Application */
    private $app;

    /** @var DataboxRepository */
    private $databoxRepository;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param DataboxRepository $databoxRepository
     */
    public function setDataboxRepository(DataboxRepository $databoxRepository)
    {
        $this->databoxRepository = $databoxRepository;
    }

    /**
     * @param int $id
     * @param array $data
     * @return \databox when Databox could not be retrieved from Persistence layer
     */
    public function create($id, array $data)
    {
        $databoxVO = new Databox(
            $id,
            $data['sqlengine'],
            $data['dsn'],
            $data['user'],
            $data['pwd'],
            $data['dbname']
        );

        $databoxVO->setDisplayIndex($data['ord']);
        $databoxVO->setViewName($data['viewname']);

        $databoxVO->setLabel('fr', $data['label_fr']);
        $databoxVO->setLabel('en', $data['label_en']);
        $databoxVO->setLabel('de', $data['label_de']);
        $databoxVO->setLabel('nl', $data['label_nl']);

        return new \databox($this->app, $this->databoxRepository, $databoxVO);
    }

    /**
     * @param array $rows
     * @throws NotFoundHttpException when Databox could not be retrieved from Persistence layer
     * @return \databox[]
     */
    public function createMany(array $rows)
    {
        $databoxes = [];

        foreach ($rows as $id => $row) {
            $databoxes[$id] = $this->create($id, $row);
        }

        return $databoxes;
    }
}
