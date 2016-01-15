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
use Alchemy\Phrasea\Core\Version as PhraseaVersion;
use Doctrine\DBAL\Connection;

abstract class base implements cache_cacheableInterface
{

    const APPLICATION_BOX = 'APPLICATION_BOX';

    const DATA_BOX = 'DATA_BOX';

    /**
     * @var string
     */
    protected $version;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var SimpleXMLElement
     */
    protected $schema;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var PhraseaVersion\VersionRepository
     */
    protected $versionRepository;

    /**
     * @param Application $application
     * @param Connection $connection
     * @param PhraseaVersion\VersionRepository $versionRepository
     */
    public function __construct(Application $application,
        Connection $connection,
        PhraseaVersion\VersionRepository $versionRepository)
    {
        $this->app = $application;
        $this->connection = $connection;
        $this->versionRepository = $versionRepository;
    }

    /**
     * @return string
     */
    abstract public function get_base_type();

    /**
     * @return SimpleXMLElement
     * @throws Exception
     */
    public function get_schema()
    {
        if ($this->schema) {
            return $this->schema;
        }

        $this->load_schema();

        return $this->schema;
    }

    /**
     * @return string
     */
    public function get_dbname()
    {
        return $this->connection->getDatabase();
    }

    /**
     * @return string
     */
    public function get_passwd()
    {
        return $this->connection->getPassword();
    }

    /**
     * @return string
     */
    public function get_user()
    {
        return $this->connection->getUsername();
    }

    /**
     * @return int
     */
    public function get_port()
    {
        return $this->connection->getPort();
    }

    /**
     * @return string
     */
    public function get_host()
    {
        return $this->connection->getHost();
    }

    /**
     * @return Connection
     */
    public function get_connection()
    {
        return $this->connection;
    }

    /**
     * @return \Alchemy\Phrasea\Cache\Cache
     */
    public function get_cache()
    {
        return $this->app['cache'];
    }

    /**
     * @deprecated App is faster without cache
     */
    public function get_data_from_cache($option = null)
    {
        return false;
    }

    /**
     * @deprecated App is faster without cache
     */
    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return false;
    }

    /**
     * @deprecated App is faster without cache
     */
    public function delete_data_from_cache($option = null)
    {
        return false;
    }

    public function get_version()
    {
        if (! $this->version) {
            $this->version = $this->versionRepository->getVersion();
        }

        return $this->version;
    }

    protected function setVersion(PhraseaVersion $version)
    {
        try {   
            return $this->versionRepository->saveVersion($version);
        } catch (\Exception $e) {
            throw new Exception('Unable to set the database version : ' . $e->getMessage());
        }
    }

    protected function upgradeDb($applyPatches)
    {
        $service = $this->app['databoxes.maintenance_service']($this->connection);

        return $service->upgradeDatabase($this, $applyPatches);
    }

    /**
     * @return base
     * @throws Exception
     */
    protected function load_schema()
    {
        if ($this->schema) {
            return $this;
        }

        if (false === $structure = simplexml_load_file(__DIR__ . "/../../lib/conf.d/bases_structure.xml")) {
            throw new Exception('Unable to load schema');
        }

        if ($this->get_base_type() === self::APPLICATION_BOX) {
            $this->schema = $structure->appbox;
        } elseif ($this->get_base_type() === self::DATA_BOX) {
            $this->schema = $structure->databox;
        } else {
            throw new Exception('Unknown schema type');
        }

        return $this;
    }

    /**
     * @return base
     */
    public function insert_datas()
    {
        $this->load_schema();

        $service = $this->app['databoxes.maintenance_service']($this->connection);

        foreach ($this->get_schema()->tables->table as $table) {
            $service->createTable($table);
        }

        $this->setVersion($this->app['phraseanet.version']);

        return $this;
    }

    public function apply_patches($from, $to, $post_process, Application $app)
    {
        $service = $this->app['databoxes.maintenance_service']($this->connection);

        return $service->applyPatches($this, $from, $to, $post_process, $app);
    }
}
