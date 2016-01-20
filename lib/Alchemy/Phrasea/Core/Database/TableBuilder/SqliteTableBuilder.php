<?php

namespace Alchemy\Phrasea\Core\Database\TableBuilder;

use Alchemy\Phrasea\Core\Database\TableBuilder;
use Doctrine\DBAL\Connection;
use RandomLib\Generator;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class SqliteTableBuilder implements TableBuilder
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
    }

    /**
     * @param Connection $connection
     * @param \SimpleXMLElement $table
     * @return void
     */
    public function buildTable(Connection $connection, \SimpleXMLElement $table)
    {
        $field_stmt = $defaults_stmt = [];

        $create_stmt = "CREATE TABLE IF NOT EXISTS `" . $table['name'] . "` (";

        foreach ($table->fields->field as $field) {
            $field_stmt = $this->pushFieldStatement($field, $field_stmt);
        }

        if ($table->indexes) {
            foreach ($table->indexes->index as $index) {
                $field_stmt = $this->pushIndexStatement($index, $field_stmt);
            }
        }

        if ($table->defaults) {
            foreach ($table->defaults->default as $default) {
                $defaults_stmt = $this->pushDefaultStatement($table, $default, $defaults_stmt);
            }
        }

        $engine = mb_strtolower(trim($table->engine));
        $engine = in_array($engine, ['innodb', 'myisam']) ? $engine : 'innodb';

        $create_stmt .= implode(',', $field_stmt);
        $create_stmt .= ") ENGINE=" . $engine . " CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

        $connection->exec($create_stmt);

        foreach ($defaults_stmt as $def) {
            $stmt = $connection->prepare($def['sql']);
            $stmt->execute($def['params']);
        }

        unset($stmt);
    }

    /**
     * @param $field
     * @param $field_stmt
     * @return array
     */
    private function pushFieldStatement($field, $field_stmt)
    {
        $isnull = trim($field->null) == "" ? "NOT NULL" : "NULL";

        if (trim($field->default) != "" && trim($field->default) != "CURRENT_TIMESTAMP") {
            $is_default = " default '" . $field->default . "'";
        } elseif (trim($field->default) == "CURRENT_TIMESTAMP") {
            $is_default = " default " . $field->default;
        } else {
            $is_default = '';
        }

        $character_set = '';
        if (in_array(strtolower((string)$field->type), ['text', 'longtext', 'mediumtext', 'tinytext'])
            || substr(strtolower((string)$field->type), 0, 7) == 'varchar'
            || in_array(substr(strtolower((string)$field->type), 0, 4), ['char', 'enum'])
        ) {

            $collation = trim((string)$field->collation) != '' ? trim((string)$field->collation) : 'utf8_unicode_ci';

            $collations = array_reverse(explode('_', $collation));
            $code = array_pop($collations);

            $character_set = ' CHARACTER SET ' . $code . ' COLLATE ' . $collation;
        }

        $field_stmt[] = " `" . $field->name . "` " . $field->type . " "
            . $field->extra . " " . $character_set . " "
            . $is_default . " " . $isnull . "";

        return $field_stmt;
    }

    /**
     * @param $index
     * @param $field_stmt
     * @return array
     */
    private function pushIndexStatement($index, $field_stmt)
    {
        switch ($index->type) {
            case "PRIMARY":
                $primary_fields = [];

                foreach ($index->fields->field as $field) {
                    $primary_fields[] = "`" . $field . "`";
                }

                $field_stmt[] = 'PRIMARY KEY (' . implode(',', $primary_fields) . ')';
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

        return $field_stmt;
    }

    /**
     * @param \SimpleXMLElement $table
     * @param $default
     * @param $defaults_stmt
     * @return array
     */
    private function pushDefaultStatement(\SimpleXMLElement $table, $default, $defaults_stmt)
    {
        $params = $dates_values = [];
        $nonce = $this->randomGenerator->generateString(16);

        foreach ($default->data as $data) {
            $k = trim($data['key']);

            if ($k === 'usr_password') {
                $data = $this->passwordEncoder->encodePassword($data, $nonce);
            }

            if ($k === 'nonce') {
                $data = $nonce;
            }

            $v = trim(str_replace(["\r\n", "\r", "\n", "\t"], '', $data));

            if (trim(mb_strtolower($v)) == 'now()') {
                $dates_values [$k] = 'NOW()';
            } else {
                $params[$k] = (trim(mb_strtolower($v)) == 'null' ? null : $v);
            }
        }

        $separator = ((count($params) > 0 && count($dates_values) > 0) ? ', ' : '');

        $defaults_stmt[] = [
            'sql' =>
                'INSERT INTO `' . $table['name'] . '` (' . implode(', ', array_keys($params))
                . $separator . implode(', ', array_keys($dates_values)) . ')
                      VALUES (:' . implode(', :', array_keys($params))
                . $separator . implode(', ', array_values($dates_values)) . ') '
            ,
            'params' => $params
        ];

        return $defaults_stmt;
    }
}
