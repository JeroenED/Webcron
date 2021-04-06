<?php

require_once "include/initialize.inc.php";

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $loader = new Twig_Loader_Filesystem('templates');
    $twig = new Twig_Environment($loader, array('cache' => 'cache', "debug" => true));
    
    $error = "";
    if (isset($_GET["error"])) {
        switch ($_GET["error"]) {
            case "emptyfields":
                $error = "Some fields were empty"; break;
            case "invalidurl":
                $error = "The URL is invalid"; break;
            case "invaliddelay":
                $error = "The delay is invalid"; break;
        }
    }
    
    $message = "";
    if (isset($_GET["message"])) {
        switch ($_GET["message"]) {
            case "added":
                $message = "The cronjob has been added"; break;
        }
    }
    
    echo $twig->render('addjob.html.twig', array("message" => $message, "error" => $error));
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (empty($_POST['name']) || empty($_POST['url'] || empty($_POST['delay']))) {
        header("location:addjob.php?error=emptyfields");
        exit;
    }
    
    $url = $_POST['url'];
    $host = $_POST['host'];
    $name = $_POST['name'];
    $delay = $_POST['delay'];
    $expected = $_POST['expected'];
    $eternal = (isset($_POST['eternal']) && $_POST['eternal'] == true) ? true : false;
    $nextrunObj = DateTime::createFromFormat("d/m/Y H:i:s", $_POST['nextrun']);
    $nextrun = $nextrunObj->getTimestamp();

    if (!$eternal) {
        $lastrunObj = DateTime::createFromFormat("d/m/Y H:i:s", $_POST['lastrun']);
        $lastrun = $lastrunObj->getTimestamp();
    } else {
        $lastrun = -1;
    }

    if(!is_numeric($delay)) {
        header("location:addjob.php?error=invaliddelay");
        exit;
    }
    if(!is_numeric($nextrun)) {
        header("location:addjob.php?error=invalidnextrun");
        exit;
    }
    if(!is_numeric($lastrun)) {
        header("location:addjob.php?error=invalidlastrun");
        exit;
    }
  
    $stmt = $db->prepare("INSERT INTO jobs(user, name, url, host, delay, nextrun, expected)  VALUES(?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(array($_SESSION["userID"], $name, $url, $host, $delay, $nextrun, $expected));
    
    header("location:addjob.php?message=added");
    exit;
}


require_once 'include/finalize.inc.php';