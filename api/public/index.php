<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(0);
}

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../src/database.php";

use Slim\Factory\AppFactory;
use App\Controllers\AccountController;

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$controller = new AccountController($mysqli);

$routes = require __DIR__ . "/../src/routes.php";
$routes($app, $controller);

$app->run();
