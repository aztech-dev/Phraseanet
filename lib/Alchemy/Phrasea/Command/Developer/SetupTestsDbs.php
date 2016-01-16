<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class SetupTestsDbs extends Command
{
    public function __construct()
    {
        parent::__construct('ini:setup-tests-dbs');

        $this->setDescription('Setup dbs for tests environment');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->container['phraseanet.configuration']->isSetup()) {
            throw new RuntimeException(sprintf(
                'Phraseanet is not setup. You can run <info>bin/setup system::install</info> command to install Phraseanet.'
            ));
        }

        /** @var Connection $connection */
        $connection = $this->container['orm.em']->getConnection();
        /** @var AbstractSchemaManager $schema */
        $schema = $connection->getSchemaManager();

        $output->writeln('Creating database "'.$connection->getDatabase().'"...<info>OK</info>');
        $schema->createDatabase($connection->getDatabase());

        if ($connection->getDriver()->getName() == 'pdo_mysql') {
            $connection->executeUpdate('
                GRANT ALL PRIVILEGES ON ' . $connection->getDatabase() . '.* TO \'' . $connection->getUsername() . '\'@\'' . $connection->getHost() . '\' IDENTIFIED BY \'' . $connection->getPassword() . '\' WITH GRANT OPTION
            ');

            $connection->executeUpdate('SET @@global.sql_mode= ""');
        }

        return 0;
    }
}
