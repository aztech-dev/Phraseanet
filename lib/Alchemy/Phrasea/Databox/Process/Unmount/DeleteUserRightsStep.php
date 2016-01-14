<?php

namespace Alchemy\Phrasea\Databox\Process\Unmount;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;

class DeleteUserRightsStep implements UnmountStep
{
    /**
     * @var ACLProvider
     */
    private $aclProvider;

    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application, ACLProvider $aclProvider)
    {
        $this->aclProvider = $aclProvider;
    }

    public function execute(\databox $databox)
    {
        $query = new \User_Query($this->application);

        $total = $query->on_sbas_ids([ $databox->get_sbas_id() ])
            ->include_phantoms(false)
            ->include_special_users(true)
            ->include_invite(true)
            ->include_templates(true)
            ->get_total();

        $n = 0;

        while ($n < $total) {
            $results = $query->limit($n, 50)->execute()->get_results();

            foreach ($results as $user) {
                $this->aclProvider->get($user)->delete_injected_rights_sbas($databox);
            }

            $n+=50;
        }
    }
}
