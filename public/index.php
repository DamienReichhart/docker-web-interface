<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../vendor/autoload.php';

use App\Router;
use App\Session;
use Dotenv\Dotenv;
use App\HttpRequest;
use App\HttpResponse;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$session = new Session();
$httpRequest = new HttpRequest();
$httpResponse = new HttpResponse();

$router = new Router($httpRequest, $httpResponse, $session);
$router->route();



