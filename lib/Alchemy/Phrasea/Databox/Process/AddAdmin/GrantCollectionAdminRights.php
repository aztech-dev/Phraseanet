<?php

namespace Alchemy\Phrasea\Databox\Process\AddAdmin;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Databox\Databox;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\Connection;

class GrantCollectionAdminRights implements AddAdminStep
{

    private static $collectionAdminRights = [
        'canpush'         => 1,
        'cancmd'          => 1,
        'canputinalbum'   => 1,
        'candwnldhd'      => 1,
        'candwnldpreview' => 1,
        'canadmin'        => 1,
        'actif'           => 1,
        'canreport'       => 1,
        'canaddrecord'    => 1,
        'canmodifrecord'  => 1,
        'candeleterecord' => 1,
        'chgstatus'       => 1,
        'imgtools'        => 1,
        'manage'          => 1,
        'modify_struct'   => 1,
        'nowatermark'     => 1
    ];

    /**
     * @var ACLProvider
     */
    private $aclProvider;

    /**
     * @var \appbox
     */
    private $appbox;

    public function __construct(\appbox $appbox, ACLProvider $aclProvider)
    {
        $this->appbox = $appbox;
        $this->aclProvider = $aclProvider;
    }

    public function execute(Connection $databoxConnection, Databox $databox, User $user)
    {
        $appboxConnection = $this->appbox->get_connection();
        $userAcl = $this->aclProvider->get($user);

        $sql = "SELECT * FROM coll";
        $stmt = $databoxConnection->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = "INSERT INTO bas
                            (base_id, active, server_coll_id, sbas_id) VALUES
                            (null,'1', :coll_id, :sbas_id)";

        $stmt = $appboxConnection->prepare($sql);

        $base_ids = [];

        foreach ($rs as $row) {
            try {
                $stmt->execute([
                    ':coll_id'  => $row['coll_id'],
                    ':sbas_id'  => $databox->getDataboxId()
                ]);

                $base_ids[] = $base_id = $appboxConnection->lastInsertId();

                if ( ! empty($row['logo'])) {
                    file_put_contents($this->app['root.path'] . '/config/minilogos/' . $base_id, $row['logo']);
                }
            } catch (\Exception $e) {
                unset($e);
            }
        }

        $stmt->closeCursor();

        $userAcl->give_access_to_base($base_ids);

        foreach ($base_ids as $base_id) {
            $userAcl->update_rights_to_base($base_id, self::$collectionAdminRights);
        }
    }
}
