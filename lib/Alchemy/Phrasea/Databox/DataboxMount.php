<?php

namespace Alchemy\Phrasea\Databox;

class DataboxMount
{

    private $databoxId;

    private $host;

    private $port;

    private $type = 'MYSQL';

    private $user;

    private $password;

    private $database;

    public function __construct($databoxId, $host, $port, $user, $password, $database)
    {
        $this->databoxId = $databoxId;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
    }

    public function getDataboxId()
    {
        return $this->databoxId;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getDatabase()
    {
        return $this->database;
    }
}
