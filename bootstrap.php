<?php

session_start();

require_once "vendor/autoload.php";

if( ini_get('safe_mode') ){
   die("Cannot run in safe mode");
}

if (!file_exists(__DIR__ . "/.env")) {
    die ("Cannot find config file");
}