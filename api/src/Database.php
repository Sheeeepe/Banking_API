<?php

namespace App;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    public static function setup(): void
    {
        $capsule = new Capsule;

        $host     = getenv('DB_HOST')     ?: 'my_mariadb';
        $port     = getenv('DB_PORT')     ?: '3306';
        $dbname   = getenv('DB_NAME')     ?: 'banking_db';
        $username = getenv('DB_USER')     ?: 'banking_user';
        $password = getenv('DB_PASSWORD') ?: '';

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $host,
            'port'      => $port,
            'database'  => $dbname,
            'username'  => $username,
            'password'  => $password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}