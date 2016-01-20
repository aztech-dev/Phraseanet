<?php

namespace Alchemy\Phrasea\Core\Database;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Connection\ConnectionPoolManager;
use Alchemy\Phrasea\Setup\DoctrineMigrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use vierbergenlars\SemVer\version;

class DatabaseMaintenanceService
{

    private static $ormTables = [
        'AggregateTokens',
        'ApiAccounts',
        'ApiApplications',
        'ApiLogs',
        'ApiOauthCodes',
        'ApiOauthRefreshTokens',
        'ApiOauthTokens',
        'AuthFailures',
        'BasketElements',
        'Baskets',
        'FeedEntries',
        'FeedItems',
        'FeedPublishers',
        'Feeds',
        'FeedTokens',
        'FtpCredential',
        'FtpExportElements',
        'FtpExports',
        'LazaretAttributes',
        'LazaretChecks',
        'LazaretFiles',
        'LazaretSessions',
        'OrderElements',
        'Orders',
        'Registrations',
        'Secrets',
        'SessionModules',
        'Sessions',
        'StoryWZ',
        'Tasks',
        'UserNotificationSettings',
        'UserQueries',
        'Users',
        'UserSettings',
        'UsrAuthProviders',
        'UsrListOwners',
        'UsrLists',
        'UsrListsContent',
        'ValidationDatas',
        'ValidationParticipants',
        'ValidationSessions',
    ];

    private $app;

    private $connection;

    /**
     * @var ConnectionPoolManager
     */
    private $connectionPool;

    private $tableBuilderFactory;

    public function __construct(
        Application $application,
        Connection $connection,
        TableBuilderFactory $tableBuilderFactory
    ) {
        $this->app = $application;
        $this->connectionPool = $application['dbal.connection_pool'];
        $this->connection = $connection;
        $this->tableBuilderFactory = $tableBuilderFactory;
    }

    public function upgradeDatabase(\base $base, $applyPatches)
    {
        $recommends = [];
        $allTables = [];

        $schema = $base->get_schema();

        foreach ($schema->tables->table as $table) {
            $allTables[(string)$table['name']] = $table;
        }

        $foundTables = $this->connection->fetchAll("SHOW TABLE STATUS");

        foreach ($foundTables as $foundTable) {
            $tableName = $foundTable["Name"];

            if (isset($allTables[$tableName])) {
                $engine = strtolower(trim($allTables[$tableName]->engine));
                $ref_engine = strtolower($foundTable['Engine']);

                if ($engine != $ref_engine && in_array($engine, ['innodb', 'myisam'])) {
                    $recommends = $this->alterTableEngine($tableName, $engine, $recommends);
                }

                $ret = $this->upgradeTable($allTables[$tableName]);
                $recommends = array_merge($recommends, $ret);

                unset($allTables[$tableName]);
            } elseif (!in_array($tableName, self::$ormTables)) {
                $recommends[] = [
                    'message' => 'Une table pourrait etre supprime',
                    'sql' => 'DROP TABLE ' . $base->get_dbname() . '.`' . $tableName . '`;'
                ];
            }
        }

        foreach ($allTables as $tableName => $table) {
            $this->createTable($table);
        }

        $current_version = $base->get_version();

        if ($applyPatches) {
            $this->applyPatches(
                $base,
                $current_version,
                $this->app['phraseanet.version']->getNumber(),
                false,
                $this->app);
        }

        return $recommends;
    }

    /**
     * @param $tableName
     * @param $engine
     * @param $recommends
     * @return array
     */
    public function alterTableEngine($tableName, $engine, array & $recommends)
    {
        $sql = 'ALTER TABLE `' . $tableName . '` ENGINE = ' . $engine;

        try {
            $this->connection->exec($sql);
        } catch (\Exception $e) {
            $recommends[] = [
                'message' => $this->app->trans('Erreur lors de la tentative ; errreur : %message%',
                    ['%message%' => $e->getMessage()]),
                'sql' => $sql
            ];
        }
    }


    /**
     * @param  \SimpleXMLElement $table
     */
    public function createTable(\SimpleXMLElement $table)
    {
        $tableBuilder = $this->tableBuilderFactory->getTableBuilder($this->connection);

        $tableBuilder->buildTable($this->connection, $table);
    }

    public function upgradeTable(\SimpleXMLElement $table)
    {
        $correct_table = ['fields' => [], 'indexes' => [], 'collation' => []];
        $alter = $alter_pre = $return = [];

        foreach ($table->fields->field as $field) {
            $expr = trim((string)$field->type);

            $_extra = trim((string)$field->extra);
            if ($_extra) {
                $expr .= ' ' . $_extra;
            }

            $collation = trim((string)$field->collation) != '' ? trim((string)$field->collation) : 'utf8_unicode_ci';

            if (in_array(strtolower((string)$field->type), ['text', 'longtext', 'mediumtext', 'tinytext'])
                || substr(strtolower((string)$field->type), 0, 7) == 'varchar'
                || in_array(substr(strtolower((string)$field->type), 0, 4), ['char', 'enum'])
            ) {
                $collations = array_reverse(explode('_', $collation));
                $code = array_pop($collations);

                $collation = ' CHARACTER SET ' . $code . ' COLLATE ' . $collation;

                $correct_table['collation'][trim((string)$field->name)] = $collation;

                $expr .= $collation;
            }

            $_null = mb_strtolower(trim((string)$field->null));
            if (!$_null || $_null == 'no') {
                $expr .= ' NOT NULL';
            }

            $_default = (string)$field->default;
            if ($_default && $_default != 'CURRENT_TIMESTAMP') {
                $expr .= ' DEFAULT \'' . $_default . '\'';
            } elseif ($_default == 'CURRENT_TIMESTAMP') {
                $expr .= ' DEFAULT ' . $_default . '';
            }

            $correct_table['fields'][trim((string)$field->name)] = $expr;
        }
        if ($table->indexes) {
            foreach ($table->indexes->index as $index) {
                $i_name = (string)$index->name;
                $expr = [];
                foreach ($index->fields->field as $field) {
                    $expr[] = '`' . trim((string)$field) . '`';
                }

                $expr = implode(', ', $expr);

                switch ((string)$index->type) {
                    case "PRIMARY":
                        $correct_table['indexes']['PRIMARY'] = 'PRIMARY KEY (' . $expr . ')';
                        break;
                    case "UNIQUE":
                        $correct_table['indexes'][$i_name] = 'UNIQUE KEY `' . $i_name . '` (' . $expr . ')';
                        break;
                    case "INDEX":
                        $correct_table['indexes'][$i_name] = 'KEY `' . $i_name . '` (' . $expr . ')';
                        break;
                }
            }
        }

        $sql = "SHOW FULL FIELDS FROM `" . $table['name'] . "`";
        $rs2 = $this->connection->fetchAll($sql);

        foreach ($rs2 as $row2) {
            $f_name = $row2['Field'];
            $expr_found = trim($row2['Type']);

            $_extra = $row2['Extra'];

            if ($_extra) {
                $expr_found .= ' ' . $_extra;
            }

            $_collation = $row2['Collation'];

            $current_collation = '';

            if ($_collation) {
                $_collation = explode('_', $row2['Collation']);

                $expr_found .= $current_collation = ' CHARACTER SET ' . $_collation[0] . ' COLLATE ' . implode('_',
                        $_collation);
            }

            $_null = mb_strtolower(trim($row2['Null']));

            if (!$_null || $_null == 'no') {
                $expr_found .= ' NOT NULL';
            }

            $_default = $row2['Default'];

            if ($_default) {
                if (trim($row2['Type']) == 'timestamp' && $_default == 'CURRENT_TIMESTAMP') {
                    $expr_found .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $expr_found .= ' DEFAULT \'' . $_default . '\'';
                }
            }

            if (isset($correct_table['fields'][$f_name])) {
                if (isset($correct_table['collation'][$f_name]) && $correct_table['collation'][$f_name] != $current_collation) {
                    $old_type = mb_strtolower(trim($row2['Type']));
                    $new_type = false;

                    switch ($old_type) {
                        case 'text':
                            $new_type = 'blob';
                            break;
                        case 'longtext':
                            $new_type = 'longblob';
                            break;
                        case 'mediumtext':
                            $new_type = 'mediumblob';
                            break;
                        case 'tinytext':
                            $new_type = 'tinyblob';
                            break;
                        default:
                            if (substr($old_type, 0, 4) == 'char') {
                                $new_type = 'varbinary(255)';
                            }
                            if (substr($old_type, 0, 7) == 'varchar') {
                                $new_type = 'varbinary(767)';
                            }
                            break;
                    }

                    if ($new_type) {
                        $alter_pre[] = "ALTER TABLE `" . $table['name'] . "` CHANGE `$f_name` `$f_name` " . $new_type . "";
                    }
                }

                if (strtolower($expr_found) !== strtolower($correct_table['fields'][$f_name])) {
                    $alter[] = "ALTER TABLE `" . $table['name'] . "` CHANGE `$f_name` `$f_name` " . $correct_table['fields'][$f_name];
                }
                unset($correct_table['fields'][$f_name]);
            } else {
                $return[] = [
                    'message' => 'Un champ pourrait etre supprime',
                    'sql' => "ALTER TABLE " . $this->connection->getDatabase() . ".`" . $table['name'] . "` DROP `$f_name`;"
                ];
            }
        }

        foreach ($correct_table['fields'] as $f_name => $expr) {
            $alter[] = "ALTER TABLE `" . $table['name'] . "` ADD `$f_name` " . $correct_table['fields'][$f_name];
        }

        $tIndex = [];
        $sql = "SHOW INDEXES FROM `" . $table['name'] . "`";
        $rs2 = $this->connection->fetchAll($sql);

        foreach ($rs2 as $row2) {
            if (!isset($tIndex[$row2['Key_name']])) {
                $tIndex[$row2['Key_name']] = ['unique' => ((int)($row2['Non_unique']) == 0), 'columns' => []];
            }
            $tIndex[$row2['Key_name']]['columns'][(int)($row2['Seq_in_index'])] = $row2['Column_name'];
        }

        foreach ($tIndex as $kIndex => $vIndex) {
            $strColumns = [];

            foreach ($vIndex['columns'] as $column) {
                $strColumns[] = '`' . $column . '`';
            }

            $strColumns = '(' . implode(', ', $strColumns) . ')';

            if ($kIndex == 'PRIMARY') {
                $expr_found = 'PRIMARY KEY ' . $strColumns;
            } else {
                if ($vIndex['unique']) {
                    $expr_found = 'UNIQUE KEY `' . $kIndex . '` ' . $strColumns;
                } else {
                    $expr_found = 'KEY `' . $kIndex . '` ' . $strColumns;
                }
            }

            $full_name_index = ($kIndex == 'PRIMARY') ? 'PRIMARY KEY' : ('INDEX `' . $kIndex . '`');

            if (isset($correct_table['indexes'][$kIndex])) {

                if (mb_strtolower($expr_found) !== mb_strtolower($correct_table['indexes'][$kIndex])) {
                    $alter[] = 'ALTER TABLE `' . $table['name'] . '` DROP ' . $full_name_index . ', ADD ' . $correct_table['indexes'][$kIndex];
                }

                unset($correct_table['indexes'][$kIndex]);
            } else {
                $return[] = [
                    'message' => 'Un index pourrait etre supprime',
                    'sql' => 'ALTER TABLE ' . $this->connection->getDatabase() . '.`' . $table['name'] . '` DROP ' . $full_name_index . ';'
                ];
            }
        }

        foreach ($correct_table['indexes'] as $kIndex => $expr) {
            $alter[] = 'ALTER TABLE `' . $table['name'] . '` ADD ' . $expr;
        }

        foreach ($alter_pre as $a) {
            try {
                $this->connection->exec($a);
            } catch (\Exception $e) {
                $return[] = [
                    'message' => $this->app->trans('Erreur lors de la tentative ; errreur : %message%',
                        ['%message%' => $e->getMessage()]),
                    'sql' => $a
                ];
            }
        }

        foreach ($alter as $a) {
            try {
                $this->connection->exec($a);
            } catch (\Exception $e) {
                $return[] = [
                    'message' => $this->app->trans('Erreur lors de la tentative ; errreur : %message%',
                        ['%message%' => $e->getMessage()]),
                    'sql' => $a
                ];
            }
        }

        return $return;
    }

    public function applyPatches(\base $base, $from, $to, $post_process)
    {
        if (version::eq($from, $to)) {
            return true;
        }

        $list_patches = [];

        $iterator = new \DirectoryIterator($this->app['root.path'] . '/lib/classes/patch/');

        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isDot()) {
                if (substr($fileinfo->getFilename(), 0, 1) == '.') {
                    continue;
                }

                $versions = array_reverse(explode('.', $fileinfo->getFilename()));
                $classname = 'patch_' . array_pop($versions);

                /** @var \patchAbstract $patch */
                $patch = new $classname();

                if (!in_array($base->get_base_type(), $patch->concern())) {
                    continue;
                }

                if (!!$post_process !== !!$patch->require_all_upgrades()) {
                    continue;
                }

                // if patch is older than current install
                if (version::lte($patch->get_release(), $from)) {
                    continue;
                }
                // if patch is new than current target
                if (version::gt($patch->get_release(), $to)) {
                    continue;
                }

                $n = 0;
                do {
                    $key = $patch->get_release() . '.' . $n;
                    $n++;
                } while (isset($list_patches[$key]));

                $list_patches[$key] = $patch;
            }
        }

        uasort($list_patches, function (\patchInterface $patch1, \patchInterface $patch2) {
            return version::lt($patch1->get_release(), $patch2->get_release()) ? -1 : 1;
        });

        $success = true;

        // disable mail
        $this->app['swiftmailer.transport'] = null;

        foreach ($list_patches as $patch) {
            // Gets doctrine migrations required for current patch
            foreach ($patch->getDoctrineMigrations() as $doctrineVersion) {
                /** @var \Doctrine\DBAL\Migrations\Version $version */
                $version = $this->app['doctrine-migration.configuration']->getVersion($doctrineVersion);
                // Skip if already migrated
                if ($version->isMigrated()) {
                    continue;
                }

                $migration = $version->getMigration();

                // Handle legacy migrations
                if ($migration instanceof AbstractMigration) {
                    // Inject entity manager
                    $migration->setEntityManager($this->app['orm.em']);

                    // Execute migration if not marked as migrated and not already applied by an older patch
                    if (!$migration->isAlreadyApplied()) {
                        $version->execute('up');
                        continue;
                    }

                    // Or mark it as migrated
                    $version->markMigrated();
                } else {
                    $version->execute('up');
                }
            }

            if (false === $patch->apply($base, $this->app)) {
                $success = false;
            }
        }

        return $success;
    }
}
