<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

use JeroenED\Framework\Kernel;
chdir(__DIR__ . '/..');
require_once 'bootstrap.php';

$kernel = new Kernel();
$kernel->setProjectDir(getcwd());
$kernel->setConfigDir(getcwd() . '/config/');
$kernel->setTemplateDir(getcwd() . '/templates/');
$kernel->setCacheDir(getcwd() . '/cache/');
$kernel->parseDotEnv($kernel->getProjectDir() . '/.env');

ini_set('date.timezone', $_ENV['TZ']);

$kernel->handle()->send();