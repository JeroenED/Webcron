<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Yaml\Yaml;

session_start();

require_once "vendor/autoload.php";
require_once "include/functions.php";

if( ini_get('safe_mode') ){
   die("Cannot run in safe mode");
}

if (!file_exists(__DIR__ . "/.env")) {
    die ("Cannot find config file");
}

$path = __DIR__.'/.env';
$dotenv = new Dotenv();
$dotenv->loadEnv($path);

$yaml = Yaml::parseFile('config/routes.yaml');
$routeloader = new YamlFileLoader(new FileLocator([__DIR__]));
$routes = $routeloader->load('config/routes.yaml');

$request = Request::createFromGlobals();
$requestContext = RequestContext::fromUri($request->getUri());
$matcher = new UrlMatcher($routes, $requestContext);
$method = $matcher->match($request->getPathInfo());

$db = DriverManager::getConnection(['url' => $_ENV['DATABASE']]);

$controller = explode('::', $method['_controller']);
$controllerObj = new ('\\' . $controller[0]);
$action = $controller[1];
$response = $controllerObj->$action();

$response->send();
