<?php

namespace App;

class Database
{
    private static ?Database $instance = null;
    private \mysqli $connection;

    private function __construct()
    {
        $host     = 'my_mariadb';
        $port     = (int)(getenv('DB_PORT') ?: 3306);
        $dbname   = getenv('DB_NAME')     ?: 'banking_db';
        $username = getenv('DB_USER')     ?: 'banking_user';
        $password = getenv('DB_PASSWORD') ?: '';

        $this->connection = new \mysqli($host, $username, $password, $dbname, $port);

        if ($this->connection->connect_error) {
            throw new \RuntimeException('DB connection failed: ' . $this->connection->connect_error);
        }

        $this->connection->set_charset('utf8mb4');
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \mysqli
    {
        return $this->connection;
    }
}