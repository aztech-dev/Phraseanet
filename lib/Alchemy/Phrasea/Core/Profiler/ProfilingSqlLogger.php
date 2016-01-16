<?php

namespace Alchemy\Phrasea\Core\Profiler;

use Doctrine\DBAL\Logging\SQLLogger;

class ProfilingSqlLogger implements SQLLogger
{

    private $profiler;

    /**
     * @var null|SqlProfile
     */
    private $profile = null;

    /**
     * @param SqlProfiler $profiler
     */
    public function __construct(SqlProfiler $profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * Logs a SQL statement somewhere.
     *
     * @param string $sql The SQL to be executed.
     * @param array|null $params The SQL parameters.
     * @param array|null $types The SQL parameter types.
     *
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->profile = $this->profiler->startProfile($sql, $params ?: [], $types ?: []);

        $this->profile->recordQueryStart();
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery()
    {
        if (! $this->profile) {
            return;
        }

        $this->profile->recordQueryDone();
        $this->profiler->saveProfile($this->profile);

        $this->profile = null;
    }
}
