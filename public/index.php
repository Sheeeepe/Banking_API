<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(0);
}

require_once __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

use App\Database;
$db_conn = Database::getInstance()->getConnection();

$routes = require __DIR__ . "/../src/routes.php";
$routes($app, $db_conn);

$app->run();
