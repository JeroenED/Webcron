<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

use JeroenED\Framework\Kernel;

require_once '../bootstrap.php';

$kernel = new Kernel();
chdir(__DIR__ . '/..');
$kernel->setProjectDir(getcwd());
$kernel->setConfigDir(getcwd() . '/config/');
$kernel->setTemplateDir(getcwd() . '/templates/');

$kernel->handle()->send();