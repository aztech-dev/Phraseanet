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

use Doctrine\DBAL\Connection;

interface DataboxRepository
{
    /**
     * @param Connection $connection
     * @return void
     */
    public function dropDatabase(Connection $connection);

    /**
     * @param Connection $connection
     * @return \databox
     */
    public function mount(Connection $connection);

    /**
     * @param Databox $databox
     * @return mixed
     */
    public function unmount(Databox $databox);

    /**
     * @param int $id
     * @return \databox
     */
    public function find($id);

    /**
     * @return \databox[]
     */
    public function findAll();

    /**
     * @param Databox $databox
     */
    public function save(Databox $databox);
}
