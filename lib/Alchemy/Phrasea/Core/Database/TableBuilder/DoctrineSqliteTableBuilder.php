<?php

namespace Alchemy\Phrasea\Core\Database\TableBuilder;

use Alchemy\Phrasea\Core\Database\TableBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\SchemaTool;
use RandomLib\Generator;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class DoctrineSqliteTableBuilder implements TableBuilder
{
    /**
     * @var PasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var Generator
     */
    private $randomGenerator;

    public function __construct(PasswordEncoderInterface $passwordEncoder, Generator $randomGenerator)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->randomGenerator = $randomGenerator;
        $this->schemaTool = null;
    }

    /**
     * @param Connection $connection
     * @param \SimpleXMLElement $table
     * @return void
     */
    public function buildTable(Connection $connection, \SimpleXMLElement $tableStructure)
    {
        $connection = DriverManager::getConnection($connection->getParams());
        $schema = new Schema();

        $table = $schema->createTable($tableStructure['name']);

        foreach ($tableStructure->fields->field as $field) {
            $table = $this->addField($table, $field);
        }

        if ($tableStructure->indexes) {
            foreach ($tableStructure->indexes->index as $index) {
                $this->addIndex($table, $index);
            }
        }

        if (count($table->getColumns()) == 0) {
            return;
        }

        $statements = $schema->toSql($connection->getDatabasePlatform());

        foreach ($statements as $statement) {
            $connection->executeQuery($statement);
        }
    }

    public function addField(Table $table, \SimpleXMLElement $field)
    {
        $character_set = '';
        $fieldType = (string) $field->type;

        if (in_array(strtolower((string)$field->type), ['text', 'longtext', 'mediumtext', 'tinytext'])
            || substr(strtolower((string)$field->type), 0, 7) == 'varchar'
            || in_array(substr(strtolower((string)$field->type), 0, 4), ['char', 'enum'])
        ) {
            $collation = trim((string)$field->collation) != '' ? trim((string)$field->collation) : 'utf8_unicode_ci';

            $collations = array_reverse(explode('_', $collation));
            $code = array_pop($collations);

            $character_set = ' CHARACTER SET ' . $code . ' COLLATE ' . $collation;

            $fieldType = 'string';
        }

        $fieldType = strtolower(trim($fieldType));

        if (strpos($fieldType, 'unsigned') !== false) {
            $fieldType = str_replace('unsigned', '', $fieldType);
        }

        if (strpos(' ' . $fieldType, ' int') !== false) {
            $fieldType = Type::INTEGER;
        }

        if (strpos(' ' . $fieldType, ' bigint') !== false) {
            $fieldType = Type::BIGINT;
        }

        if (strpos(' ' . $fieldType, ' smallint') !== false) {
            $fieldType = Type::SMALLINT;
        }

        if (strpos(' ' . $fieldType, ' tinyint') !== false) {
            $fieldType = Type::SMALLINT;
        }

        if (strpos(' ' . $fieldType, ' varbinary') !== false) {
            $fieldType = Type::BINARY;
        }

        if (strpos($fieldType, 'datetime') !== false) {
            $fieldType = Type::DATETIME;
        }

        if (strpos($fieldType, 'timestamp') !== false) {
            $fieldType = Type::DATETIME;
        }

        $autoIncrement = false;

        if (strpos(strtolower((string) $field->extra), 'auto_increment') !== false) {
            $autoIncrement = true;
        }

        try {
            $column = $table->addColumn((string) $field->name, $fieldType);

            $column->setAutoincrement($autoIncrement);

            if (((string) $field->default) != ''){
                $column->setDefault((string) $field->default);
            }

            $column->setNotnull(trim((string) $field->null) == "");
        }
        catch (DBALException $exception) {
            throw new \RuntimeException('', 0, $exception);
        }

        return $table;
    }

    public function addIndex(Table $table, \SimpleXMLElement $index)
    {
        switch ($index->type) {
            case "PRIMARY":
                $primary_fields = [];

                foreach ($index->fields->field as $field) {
                    $primary_fields[] = (string) $field;
                }

                $table->setPrimaryKey($primary_fields, false);

                break;
            case "UNIQUE":
                $unique_fields = [];

                foreach ($index->fields->field as $field) {
                    $unique_fields[] = "`" . $field . "`";
                }

                $field_stmt[] = 'UNIQUE KEY `' . $index->name . '` (' . implode(',', $unique_fields) . ')';
                break;
            case "INDEX":
                $index_fields = [];

                foreach ($index->fields->field as $field) {
                    $index_fields[] = "`" . $field . "`";
                }

                $field_stmt[] = 'KEY `' . $index->name . '` (' . implode(',', $index_fields) . ')';
                break;
        }
    }
}
