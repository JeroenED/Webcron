<?php

/* 
 * The MIT License
 *
 * Copyright 2017 Jeroen De Meerleer <me@jeroened.be>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once "include/initialize.inc.php";

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $loader = new Twig_Loader_Filesystem('templates');
    $twig = new Twig_Environment($loader, array('cache' => 'cache', "debug" => true));
    
    $error = "";
    if ($_GET["error"]) {
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
    if ($_GET["message"]) {
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
    $nextrunObj = new DateTime($_POST['nextrun']);
    $nextrun = $nextrunObj->getTimestamp();
    
    if(!is_numeric($delay)) {
        header("location:addjob.php?error=invaliddelay");
        exit;
    }
    if(!is_numeric($nextrun)) {
        header("location:addjob.php?error=invalidnextrun");
        exit;
    }
    
  
    $stmt = $db->prepare("INSERT INTO jobs(user, name, url, host, delay, nextrun, expected)  VALUES(?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(array($_SESSION["userID"], $name, $url, $host, $delay, $nextrun, $expected));
    
    header("location:addjob.php?message=added");
    exit;
}


require_once 'include/finalize.inc.php';