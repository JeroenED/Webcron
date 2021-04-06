<?php

require_once "include/initialize.inc.php";

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    $message = "";
    if (isset($_GET["message"])) {
        switch ($_GET["message"]) {
            case "edited":
                $message = "The config has been edited"; break;
        }
    }
    
    $error = "";
    if (isset($_GET["error"])) {
        switch ($_GET["error"]) {
            case "emptyfields":
                $error = "Some fields were empty"; break;
        }
    }


    $loader = new Twig_Loader_Filesystem('templates');
    $twig = new Twig_Environment($loader, array('cache' => 'cache', "debug" => true));

    $configs = load_config_categorized();

    $twig_vars = array('config' => $configs, "error" => $error, "message" => $message);

    echo $twig->render('config.html.twig', $twig_vars);
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {

    foreach($_POST as $key => $value) {
        if (empty($value)) {
            header("location:config.php?error=emptyfields"); exit;
        }

        $keydb = str_replace('_', '.', $key);
        $stmt = $db->prepare("UPDATE config SET value = ? WHERE conf = ?");
        $stmt->execute(array($value, $keydb));
    }

    header("location:config.php?message=edited");
    exit;
}

require_once 'include/finalize.inc.php';