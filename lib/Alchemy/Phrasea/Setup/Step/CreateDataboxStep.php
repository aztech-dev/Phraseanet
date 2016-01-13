<?php

namespace Alchemy\Phrasea\Setup\Step;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Databox\DataboxService;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Step;
use Doctrine\DBAL\Connection;

class CreateDataboxStep implements Step
{

    private static $defaultDataboxRights = [
        'bas_manage' => 1,
        'bas_modify_struct' => 1,
        'bas_modif_th' => 1,
        'bas_chupub' => 1
    ];

    private static $defaultCollectionRights = [
        'canpush' => 1,
        'cancmd' => 1,
        'canputinalbum' => 1,
        'candwnldhd' => 1,
        'candwnldpreview' => 1,
        'canadmin' => 1,
        'actif' => 1,
        'canreport' => 1,
        'canaddrecord' => 1,
        'canmodifrecord' => 1,
        'candeleterecord' => 1,
        'chgstatus' => 1,
        'imgtools' => 1,
        'manage' => 1,
        'modify_struct' => 1,
        'nowatermark' => 1
    ];

    /**
     * @var ACLProvider
     */
    private $aclProvider;

    /**
     * @var DataboxService
     */
    private $databoxService;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @param ACLProvider $aclProvider
     * @param DataboxService $databoxService
     * @param UserRepository $userRepository
     */
    public function __construct(
        ACLProvider $aclProvider,
        DataboxService $databoxService,
        UserRepository $userRepository
    ) {
        $this->aclProvider = $aclProvider;
        $this->databoxService = $databoxService;
        $this->userRepository = $userRepository;
    }

    public function execute(
        InitializeEnvironmentCommand $initializeEnvironmentCommand,
        Connection $appboxConnection,
        Connection $databoxConnection = null
    ) {
        if ($databoxConnection === null) {
            return;
        }

        $admin = $this->userRepository->findByLogin($initializeEnvironmentCommand->getUserEmail());

        if (!$admin) {
            throw new RuntimeException('Admin user is not created.');
        }

        $templatePath = sprintf(
            '%s/../../../../conf.d/data_templates/%s-simple.xml',
            __DIR__,
            $initializeEnvironmentCommand->getDatabaseTemplate()
        );

        $template = new \SplFileInfo($templatePath);

        $databox = $this->databoxService->createDatabox($databoxConnection, $template);
        $adminAcl = $this->aclProvider->get($admin);

        $adminAcl->give_access_to_sbas([$databox->get_sbas_id()]);
        $adminAcl->update_rights_to_sbas($databox->get_sbas_id(), self::$defaultDataboxRights);

        $collection = $databox->createCollection('test', $admin);

        $adminAcl->give_access_to_base([$collection->get_base_id()]);
        $adminAcl->update_rights_to_base($collection->get_base_id(), self::$defaultCollectionRights);
    }
}
