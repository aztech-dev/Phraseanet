<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;
use Doctrine\DBAL\Connection;

class Installer
{


    public function __construct(Application $app)
    {

    }

    public function install($email, $password, Connection $abConn, $serverName, $dataPath, Connection $dbConn = null, $template = null, array $binaryData = [])
    {

    }

}
