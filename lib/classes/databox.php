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
use Alchemy\Phrasea\Core\Thumbnail\ThumbnailedElement;
use Alchemy\Phrasea\Databox\CandidateTerms\CandidateTerms;
use Alchemy\Phrasea\Databox\Databox as DataboxVO;
use Alchemy\Phrasea\Databox\DataboxPreferencesRepository;
use Alchemy\Phrasea\Databox\DataboxRepositoriesFactory;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Alchemy\Phrasea\Databox\DataboxService;
use Alchemy\Phrasea\Databox\DataboxTermsOfUseRepository;
use Alchemy\Phrasea\Databox\Record\RecordDetailsRepository;
use Alchemy\Phrasea\Databox\Record\RecordRepository;
use Alchemy\Phrasea\Databox\Structure\Structure;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Status\StatusStructure;
use Alchemy\Phrasea\Status\StatusStructureFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\HttpFoundation\File\File;
use Alchemy\Phrasea\Core\Event\Databox\DataboxEvents;
use Alchemy\Phrasea\Core\Event\Databox\ThesaurusChangedEvent;

class databox extends base implements ThumbnailedElement
{

    const BASE_TYPE = self::DATA_BOX;
    const CACHE_META_STRUCT = 'meta_struct';
    const CACHE_THESAURUS = 'thesaurus';
    const CACHE_COLLECTIONS = 'collections';
    const CACHE_STRUCTURE = 'structure';
    const PIC_PDF = 'logopdf';
    const CACHE_CGUS = 'cgus';

    /** @var array */
    protected static $_xpath_thesaurus = [];

    /** @var array */
    protected static $_dom_thesaurus = [];

    /** @var array */
    protected static $_thesaurus = [];

    /** @var SimpleXMLElement */
    protected static $_sxml_thesaurus = [];

    /**
     *
     * @param  int $sbas_id
     * @return string
     */
    public static function getPrintLogo($sbas_id)
    {
        $out = '';

        if (is_file(($filename = __DIR__ . '/../../config/minilogos/'.\databox::PIC_PDF.'_' . $sbas_id . '.jpg'))) {
            $out = file_get_contents($filename);
        }

        return $out;
    }

    /**
     * @deprecated Method is no longer required, cache busting is handled transparently.
     */
    public static function purge()
    {
        // No op. Method is left for BC only
    }

    /** @var databox_descriptionStructure */
    protected $meta_struct;

    /** @var databox_subdefsStructure */
    protected $subdef_struct;

    /** @var string */
    protected $thesaurus;

    /** @var \appbox */
    private $applicationBox;

    /** @var DataboxRepository */
    private $databoxRepository;

    /** @var RecordRepository */
    private $recordRepository;

    /** @var RecordDetailsRepository */
    private $recordDetailsRepository;

    /** @var DataboxPreferencesRepository */
    private $preferencesRepository;

    /** @var DataboxTermsOfUseRepository */
    private $termsOfUseRepository;

    /** @var DataboxVO */
    private $databox;

    /** @var CandidateTerms */
    private $candidateTerms = null;

    /** @var Structure */
    private $structure = null;

    /** @var mixed[] */
    private $termsOfUse = null;

    /**
     * @param Application $app
     * @param DataboxRepository $databoxRepository
     * @param DataboxVO $databox
     */
    public function __construct(Application $app, DataboxRepository $databoxRepository, DataboxVO $databox)
    {
        $this->applicationBox = $app->getApplicationBox();
        $this->databoxRepository = $databoxRepository;
        $this->databox = $databox;

        $factory = new DataboxRepositoriesFactory(
            $app['locales.available'],
            $app['cache'],
            'databox_' . $databox->getDataboxId()
        );

        $connection = $app['dbal.provider']($databox->getConnectionParameters());

        $this->preferencesRepository = $factory->getPreferencesRepository($connection);
        $this->recordDetailsRepository = $factory->getRecordDetailsRepository($connection);
        $this->termsOfUseRepository = $factory->getTermsOfUseRepository($connection);

        parent::__construct($app, $connection, $factory->getVersionRepository($connection));
    }

    /**
     * @param $name
     * @param User $owner
     * @return \Alchemy\Phrasea\Collection\Collection|collection
     */
    public function createCollection($name, User $owner = null)
    {
        return \collection::create($this->app, $this, $this->applicationBox, $name, $owner);
    }

    /**
     * @return DataboxVO
     */
    public function getDataObject()
    {
        return $this->databox;
    }

    /**
     * @return DataboxPreferencesRepository
     */
    public function getPreferenceRepository()
    {
        return $this->preferencesRepository;
    }

    /**
     * @return DataboxTermsOfUseRepository
     */
    public function getTermsOfUseRepository()
    {
        return $this->termsOfUseRepository;
    }

    /**
     * @return CandidateTerms
     */
    public function getCandidateTerms()
    {
        $this->loadCandidateTerms();

        return $this->candidateTerms;
    }

    /**
     * @return Structure
     */
    public function getStructure()
    {
        $this->loadStructure();

        return $this->structure;
    }

    /**
     * @param SplFileInfo $data_template
     * @param $path_doc
     * @return $this
     * @deprecated Use DataboxService::setDataboxStructure() instead
     */
    public function setNewStructure(\SplFileInfo $data_template, $path_doc)
    {
        $service = $this->getDataboxService();

        $service->setDataboxStructure($this, $data_template, $path_doc);

        return $this;
    }

    /**
     *
     * @param  DOMDocument $dom_struct
     * @return databox
     * @deprecated Use DataboxService::replaceDataboxStructure() instead
     */
    public function saveStructure(DOMDocument $dom_struct)
    {
        $service = $this->getDataboxService();

        $service->replaceDataboxStructure($this, $dom_struct);

        $this->structure = new Structure($dom_struct->saveXML());

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTermsOfUse()
    {
        if (! $this->termsOfUse) {
            $this->loadTermsOfUse();
        }

        return $this->termsOfUse;
    }

    /**
     * @return DOMDocument
     * @deprecated use \databox::getStructure() instead
     */
    public function get_dom_structure()
    {
        return $this->getStructure()->getDomDocument();
    }

    /**
     * @return string
     * @deprecated use \databox::getStructure() instead
     */
    public function get_structure()
    {
        return $this->getStructure()->getRawStructure();
    }

    /**
     *
     * @return SimpleXMLElement
     * @deprecated use \databox::getStructure() instead
     */
    public function get_sxml_structure()
    {
        return $this->getStructure()->getSimpleXmlElement();
    }

    /**
     * @return DOMXpath
     * @deprecated use \databox::getStructure() instead
     */
    public function get_xpath_structure()
    {
        return $this->getStructure()->getDomXpath();
    }

    /**
     * @return $this
     * @deprecated Do not use directly
     */
    public function feed_meta_fields()
    {
        return $this;
    }

    /**
     * @return RecordRepository
     */
    public function getRecordRepository()
    {
        if ($this->recordRepository === null) {
            $this->recordRepository = $this->app['repo.records.factory']($this);
        }

        return $this->recordRepository;
    }

    /**
     * @return int
     * @deprecated use Databox::getDisplayIndex()
     */
    public function get_ord()
    {
        return $this->databox->getDisplayIndex();
    }

    /**
     * @return int
     */
    public function getRootIdentifier()
    {
        return $this->databox->getDataboxId();
    }

    /**
     * Returns current sbas_id
     *
     * @return int
     */
    public function get_sbas_id()
    {
        return $this->databox->getDataboxId();
    }

    public function updateThumbnail($thumbnailType, File $file = null)
    {
        $this->delete_data_from_cache('printLogo');
    }

    /**
     * @return int[]
     */
    public function get_collection_unique_ids()
    {
        $collectionsIds = [];

        foreach ($this->get_collections() as $collection) {
            $collectionsIds[] = $collection->get_base_id();
        }

        return $collectionsIds;
    }

    /**
     * @return collection[]
     */
    public function get_collections()
    {
        /** @var CollectionRepositoryRegistry $repositoryRegistry */
        $repositoryRegistry = $this->app['repo.collections-registry'];
        $repository = $repositoryRegistry->getRepositoryByDatabox($this->get_sbas_id());

        return array_filter($repository->findAll(), function (collection $collection) {
            return $collection->is_active();
        });
    }

    /**
     *
     * @param  int            $record_id
     * @param  int            $number
     * @return record_adapter
     */
    public function get_record($record_id, $number = null)
    {
        return new record_adapter($this->app, $this->databox->getDataboxId(), $record_id, $number);
    }

    public function get_label($code, $substitute = true)
    {
        if ($substitute) {
            return $this->databox->getLabelOrDefault($code, $this->databox->getViewName());
        }

        return $this->databox->getLabel($code);
    }

    /**
     * @return string
     * @deprecated use Databox::getViewName() instead.
     */
    public function get_viewname()
    {
        return $this->databox->getViewName(true);
    }

    public function set_viewname($viewname)
    {
        $this->databox->setViewName($viewname);
        $this->databoxRepository->save($this->databox);

        return $this;
    }

    /**
     * @return appbox
     */
    public function get_appbox()
    {
        return $this->applicationBox;
    }

    public function set_label($code, $label)
    {
        $this->databox->setLabel($code, $label);
        $this->databoxRepository->save($this->databox);

        return $this;
    }

    /**
     * @return StatusStructure
     */
    public function getStatusStructure()
    {
        /** @var StatusStructureFactory $structureFactory */
        $structureFactory = $this->app['factory.status-structure'];

        return $structureFactory->getStructure($this);
    }

    public function get_record_details($sort)
    {
        return $this->recordDetailsRepository->getRecordDetails($sort);
    }

    public function get_record_amount()
    {
        return $this->recordDetailsRepository->getRecordCount();
    }

    public function get_counts()
    {
        return $this->recordDetailsRepository->getRecordStatistics();
    }

    /**
     * @deprecated DataboxService::unmountDatabox() instead
     */
    public function unmount_databox()
    {
        $service = $this->getDataboxService();
        $service->unmountDatabox($this);
    }

    public function get_base_type()
    {
        return self::BASE_TYPE;
    }

    public function get_cache_key($option = null)
    {
        return 'databox_' . $this->databox->getDataboxId() . '_' . ($option ? $option . '_' : '');
    }

    /**
     * @return databox_descriptionStructure|databox_field[]
     */
    public function get_meta_structure()
    {
        if ($this->meta_struct) {
            return $this->meta_struct;
        }

        /** @var \Alchemy\Phrasea\Databox\Field\DataboxFieldRepository $fieldRepository */
        $fieldRepository = $this->app['repo.fields.factory']($this);

        $this->meta_struct = new databox_descriptionStructure($fieldRepository->findAll());

        return $this->meta_struct;
    }

    /**
     *
     * @return databox_subdefsStructure
     */
    public function get_subdef_structure()
    {
        if (! $this->subdef_struct) {
            $this->subdef_struct = new databox_subdefsStructure($this, $this->app['translator']);
        }

        return $this->subdef_struct;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @deprecated Use DataboxService::deleteDatabox() instead
     */
    public function delete()
    {
        $service = $this->getDataboxService();

        $service->deleteDatabox($this);
    }

    public function get_serialized_server_info()
    {
        return sprintf("%s@%s:%s (MySQL %s)",
            $this->connection->getDatabase(),
            $this->connection->getHost(),
            $this->connection->getPort(),
            $this->get_connection()->getWrappedConnection()->getAttribute(\PDO::ATTR_SERVER_VERSION)
        );
    }

    /**
     *
     * @return Array
     */
    public function get_mountable_colls()
    {
        /** @var Connection $conn */
        $conn = $this->get_appbox()->get_connection();
        $colls = [];

        $sql = 'SELECT server_coll_id FROM bas WHERE sbas_id = :sbas_id';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':sbas_id' => $this->databox->getDataboxId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        foreach ($rs as $row) {
            $colls[] = (int) $row['server_coll_id'];
        }

        $mountable_colls = [];

        $builder = $this->get_connection()->createQueryBuilder();
        $builder
            ->select('c.coll_id', 'c.asciiname')
            ->from('coll', 'c');

        if (count($colls) > 0) {
            $builder
                ->where($builder->expr()->notIn('c.coll_id', [':colls']))
                ->setParameter('colls', $colls, Connection::PARAM_INT_ARRAY)
            ;
        }

        /** @var Statement $stmt */
        $stmt = $builder->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        foreach ($rs as $row) {
            $mountable_colls[$row['coll_id']] = $row['asciiname'];
        }

        return $mountable_colls;
    }

    public function get_activable_colls()
    {
        /** @var Connection $conn */
        $conn = $this->get_appbox()->get_connection();
        $base_ids = [];

        $sql = 'SELECT base_id FROM bas WHERE sbas_id = :sbas_id AND active = "0"';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':sbas_id' => $this->databox->getDataboxId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $base_ids[] = (int) $row['base_id'];
        }

        return $base_ids;
    }

    public function saveCterms(DOMDocument $dom_cterms)
    {
        $dom_cterms->documentElement->setAttribute("modification_date", $now = date("YmdHis"));

        $sql = "UPDATE pref SET value = :xml, updated_on = :date
                WHERE prop='cterms'";

        $this->cterms = $dom_cterms->saveXML();
        $params = [
            ':xml'  => $this->cterms
            , ':date' => $now
        ];

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->databoxRepository->save($this->databox);

        return $this;
    }

    public function saveThesaurus(DOMDocument $dom_thesaurus)
    {
        $old_thesaurus = $this->get_dom_thesaurus();

        $dom_thesaurus->documentElement->setAttribute("modification_date", $now = date("YmdHis"));
        $this->thesaurus = $dom_thesaurus->saveXML();

        $sql = "UPDATE pref SET value = :xml, updated_on = :date WHERE prop='thesaurus'";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute([':xml'  => $this->thesaurus, ':date' => $now]);
        $stmt->closeCursor();
        $this->delete_data_from_cache(databox::CACHE_THESAURUS);

        $this->databoxRepository->save($this->databox);

        $this->app['dispatcher']->dispatch(
            DataboxEvents::THESAURUS_CHANGED,
            new ThesaurusChangedEvent(
                $this,
                array(
                    'dom_before'=>$old_thesaurus,
                )
            )
        );

        return $this;
    }

    /**
     * @return DOMDocument
     */
    public function get_dom_thesaurus()
    {
        $sbas_id = $this->databox->getDataboxId();
        if (isset(self::$_dom_thesaurus[$sbas_id])) {
            return self::$_dom_thesaurus[$sbas_id];
        }

        $thesaurus = $this->get_thesaurus();

        $dom = new DOMDocument();

        if ($thesaurus && false !== $dom->loadXML($thesaurus)) {
            self::$_dom_thesaurus[$sbas_id] = $dom;
        } else {
            self::$_dom_thesaurus[$sbas_id] = false;
            unset($dom);
        }

        return self::$_dom_thesaurus[$sbas_id];
    }

    /**
     * @return string
     */
    public function get_thesaurus()
    {
        $preference = $this->preferencesRepository->findFirstByProperty('thesaurus');

        return $preference->getValue();
    }

    /**
     *
     * @param  User    $user
     * @return databox
     * @deprecated Use DataboxService::addDataboxAdmin() instead
     */
    public function registerAdmin(User $user)
    {
        $service = $this->getDataboxService();

        $service->addDataboxAdmin($this, $user);

        return $this;
    }

    public function clear_logs()
    {
        foreach (['log', 'log_colls', 'log_docs', 'log_search', 'log_view', 'log_thumb'] as $table) {
            $this->get_connection()->delete($table, []);
        }

        return $this;
    }

    /**
     * @return $this
     * @deprecated Use DataboxService::reindexDatabox instead
     */
    public function reindex()
    {
        $service = $this->getDataboxService();

        $service->reindexDatabox($this);

        return $this;
    }

    /**
     * @return DOMXpath
     */
    public function get_xpath_thesaurus()
    {
        $sbas_id = $this->databox->getDataboxId();
        if (isset(self::$_xpath_thesaurus[$sbas_id])) {
            return self::$_xpath_thesaurus[$sbas_id];
        }

        $DOM_thesaurus = $this->get_dom_thesaurus();

        if ($DOM_thesaurus && ($tmp = new thesaurus_xpath($DOM_thesaurus)) !== false)
            self::$_xpath_thesaurus[$sbas_id] = $tmp;
        else
            self::$_xpath_thesaurus[$sbas_id] = false;

        return self::$_xpath_thesaurus[$sbas_id];
    }

    /**
     * @return SimpleXMLElement
     */
    public function get_sxml_thesaurus()
    {
        $sbas_id = $this->databox->getDataboxId();
        if (isset(self::$_sxml_thesaurus[$sbas_id])) {
            return self::$_sxml_thesaurus[$sbas_id];
        }

        $thesaurus = $this->get_thesaurus();

        if ($thesaurus && false !== $tmp = simplexml_load_string($thesaurus))
            self::$_sxml_thesaurus[$sbas_id] = $tmp;
        else
            self::$_sxml_thesaurus[$sbas_id] = false;

        return self::$_sxml_thesaurus[$sbas_id];
    }

    /**
     * @deprecated Use \databox::getCandidateTerms()->getDomDocument() instead
     */
    public function get_dom_cterms()
    {
        return $this->getCandidateTerms()->getDomDocument();
    }

    /**
     *
     * @deprecated Use \databox::getCandidateTerms()->getRawTerms() instead
     */
    public function get_cterms()
    {
        return $this->getCandidateTerms()->getRawTerms();
    }

    /**
     * @deprecated Use DataboxService::updateTermsOfUse instead
     */
    public function update_cgus($locale, $terms, $reset_date)
    {
        $this->getDataboxService()->updateDataboxTermsOfUse($this, $locale, $terms, (bool) $reset_date);

        return $this;
    }

    /**
     * @deprecated Use \databox::getTermsOfUse() instead
     */
    public function get_cgus()
    {
        return $this->getTermsOfUse();
    }

    public function __sleep()
    {
        $vars = [];

        foreach ($this as $key => $value) {
            if (in_array($key, ['app', 'meta_struct'])) {
                continue;
            }

            $vars[] = $key;
        }

        return $vars;
    }

    /**
     * Tells whether registration is enabled or not.
     *
     * @return boolean
     * @deprecated Use \databox::getStructure()->isRegistrationEnabled() instead
     */
    public function isRegistrationEnabled()
    {
        return $this->getStructure()->isRegistrationEnabled();
    }

    /**
     * Returns an array that can be used to restore databox.
     *
     * @return array
     */
    public function getRawData()
    {
        return [
            'ord'       => $this->databox->getDisplayIndex(),
            'viewname'  => $this->databox->getViewName(),
            'label_en'  => $this->databox->getLabel('en'),
            'label_fr'  => $this->databox->getLabel('fr'),
            'label_de'  => $this->databox->getLabel('de'),
            'label_nl'  => $this->databox->getLabel('nl'),
            'dsn'       => $this->databox->getDsn(),
            'host'      => $this->databox->getHost(),
            'port'      => $this->databox->getPort(),
            'user'      => $this->databox->getUser(),
            'pwd'       => $this->databox->getPassword(),
            'dbname'    => $this->databox->getDatabase(),
            'sqlengine' => $this->databox->getType()
        ];
    }

    private function loadCandidateTerms()
    {
        if (! $this->candidateTerms) {
            $terms = '';
            $preference = $this->preferencesRepository->findFirstByProperty('cterms');

            if ($preference) {
                $terms = $preference->getValue();
            }

            $this->candidateTerms = new CandidateTerms($terms);
        }
    }

    private function loadStructure()
    {
        if (! $this->structure) {
            $structure = '';
            $preference = $this->preferencesRepository->findFirstByProperty('structure');

            if ($preference) {
                $structure = $preference->getValue();
            }

            $this->structure = new Structure($structure);
        }
    }

    private function loadTermsOfUse()
    {
        $this->termsOfUse = $this->termsOfUseRepository->getTermsOfUse();
    }

    /**
     * @return DataboxService
     */
    private function getDataboxService()
    {
        return $service = $this->app['databoxes.service'];
    }
}
