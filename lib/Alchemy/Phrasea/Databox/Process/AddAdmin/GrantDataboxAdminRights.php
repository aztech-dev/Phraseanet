<?php

namespace Alchemy\Phrasea\Databox\Process\AddAdmin;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Databox\Databox;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\Connection;

class GrantDataboxAdminRights implements AddAdminStep
{

    private static $databoxAdminRights = [
        'bas_manage'        => 1,
        'bas_modify_struct' => 1,
        'bas_modif_th'      => 1,
        'bas_chupub'        => 1
    ];

    /**
     * @var ACLProvider
     */
    private $aclProvider;

    public function __construct(ACLProvider $aclProvider)
    {
        $this->aclProvider = $aclProvider;
    }

    public function execute(Connection $databoxConnection, Databox $databox, User $user)
    {
        $userAcl = $this->aclProvider->get($user);

        $userAcl
            ->give_access_to_sbas([ $databox->getDataboxId() ])
            ->update_rights_to_sbas($databox->getDataboxId(), self::$databoxAdminRights);
    }
}
