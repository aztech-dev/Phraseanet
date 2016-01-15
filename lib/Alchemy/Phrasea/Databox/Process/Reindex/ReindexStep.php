<?php

namespace Alchemy\Phrasea\Databox\Process\Reindex;

use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\Databox\Databox;
use Doctrine\DBAL\Connection;

class ReindexStep
{

    public function execute(Connection $databoxConnection, Databox $databox)
    {
        $databoxConnection->update('pref', ['updated_on' => '0000-00-00 00:00:00'], ['prop' => 'indexes']);

        // Set TO_INDEX flag on all records
        $sql = "UPDATE record SET jeton = (jeton | :token)";
        $stmt = $databoxConnection->prepare($sql);

        $stmt->bindValue(':token', PhraseaTokens::TO_INDEX, \PDO::PARAM_INT);
        $stmt->execute();
    }
}
