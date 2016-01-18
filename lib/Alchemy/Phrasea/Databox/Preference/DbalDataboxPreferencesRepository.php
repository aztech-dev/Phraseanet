<?php

namespace Alchemy\Phrasea\Databox\Preference;

use Alchemy\Phrasea\Databox\DataboxPreferencesRepository;
use Doctrine\DBAL\Connection;

class DbalDataboxPreferencesRepository implements DataboxPreferencesRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return DataboxPreference
     */
    public function findAll()
    {
        $statement = $this->connection->prepare('SELECT * FROM pref');
        $statement->execute();

        $preferences = [];
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $preferences[] = $this->createPreferenceVO($row);
        }

        return $preferences;
    }

    /**
     * @param $propertyName
     * @return mixed
     */
    public function findByProperty($propertyName)
    {
        $statement = $this->connection->prepare('SELECT * FROM pref WHERE prop = :prop');
        $statement->execute([ ':prop' => $propertyName ]);

        $preferences = [];
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $preferences[] = $this->createPreferenceVO($row);
        }

        return $preferences;
    }

    /**
     * @param string $propertyName
     * @return DataboxPreference
     */
    public function findFirstByProperty($propertyName)
    {
        $statement = $this->connection->prepare('SELECT * FROM pref WHERE prop = :prop LIMIT 1');
        $statement->execute([ ':prop' => $propertyName ]);

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            return $this->createPreferenceVO($row);
        }

        return null;
    }

    /**
     * @param DataboxPreference $preference
     * @return mixed
     */
    public function save(DataboxPreference $preference)
    {
        $parameters = [
            ':prop' => $preference->getProperty(),
            ':value' => $preference->getValue(),
            ':locale' => $preference->getLocale(),
            ':updated_on' => $preference->getUpdatedOn()->format('Y-m-d H:i:s'),
            ':created_on' => $preference->getCreatedOn()->format('Y-m-d H:i:s')
        ];

        $query = <<<EOQ
INSERT INTO pref(prop, value, locale, updated_on, created_on)
VALUES (:prop, :value, :locale, :updated_on, :created_on)
EOQ;

        if ($preference->getId() > 0) {
            $query = <<<EOQ
UPDATE pref
SET prop = :prop, value = :value, locale = :locale, updated_on = :updated_on, created_on = :created_on
WHERE id = :id
EOQ;
            $parameters[':id'] = $preference->getId();
        }

        $statement = $this->connection->prepare($query);
        $statement->execute($parameters);

        if (! $preference->getId()) {
            $preference->setId($this->connection->lastInsertId());
        }
    }

    /**
     * @param $row
     * @return DataboxPreference
     */
    private function createPreferenceVO($row)
    {
        $preference = new DataboxPreference(
            $row['id'],
            $row['locale'],
            $row['prop'],
            $row['value'],
            \DateTime::createFromFormat('Y-m-d H:i:s', $row['created_on']),
            \DateTime::createFromFormat('Y-m-d H:i:s', $row['updated_on'])
        );

        return $preference;
    }
}
