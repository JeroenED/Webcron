<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

use JeroenED\Framework\Kernel;

require_once '../bootstrap.php';

$kernel = new Kernel();
$kernel->setProjectDir(__DIR__ . '/..');
$kernel->setConfigDir(__DIR__ . '/../config/');
$kernel->setTemplateDir(__DIR__ . '/../templates/');

$kernel->handle()->send();