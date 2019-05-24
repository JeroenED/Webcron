<?php

/* 
 * The MIT License
 *
 * Copyright 2017-2018 Jeroen De Meerleer <me@jeroened.be>.
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

$jobID = $_GET['jobID'];
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $jobnameqry = $db->prepare("SELECT * FROM jobs WHERE jobID = ?");
    $jobnameqry->execute(array($_GET['jobID']));
    $jobnameResult = $jobnameqry->fetchAll(PDO::FETCH_ASSOC);
    if ($jobnameResult[0]["user"] != $_SESSION["userID"]) {
        header("location:/overview.php");
        exit;
    }
    $name = $jobnameResult[0]['name'];
    $url = $jobnameResult[0]['url'];
    $host = $jobnameResult[0]['host'];
    $delay = $jobnameResult[0]['delay'];
    $expected = $jobnameResult[0]['expected'];
    $nextrun = date("d/m/Y H:i:s", $jobnameResult[0]['nextrun']);
    $lastrun = ($jobnameResult[0]['lastrun'] == -1) ? -1 : date("d/m/Y H:i:s", $jobnameResult[0]['lastrun']);


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

    
    echo $twig->render('editjob.html.twig', array("name" => $name, "url" => $url, "host" => $host, "delay" => $delay, "expected" => $expected, 'nextrun' => $nextrun, "jobID" => $jobID, "error" => $error));
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (empty($_POST['name']) || empty($_POST['url'] || empty($_POST['delay']))) {
        header("location:editjob.php?error=emptyfields");
        exit;
    }
    
    $url = $_POST['url'];
    $name = $_POST['name'];
    $delay = $_POST['delay'];
    $host = $_POST['host'];
    $expected = $_POST['expected'];
    $eternal = (isset($_POST['eternal']) && $_POST['eternal'] == true) ? true : false;
    $nextrunObj = DateTime::createFromFormat("d/m/Y H:i:s", $_POST['nextrun']);
    $nextrun = $nextrunObj->getTimestamp();

    if (!$eternal) {
        $lastrunObj = DateTime::createFromFormat("d/m/Y H:i:s", $_POST['lastrun']);
        $lastrun = $nextrunObj->getTimestamp();
    } else {
        $lastrun = -1;
    }
    
    if(!is_numeric($delay)) {
        header("location:editjob.php?jobID=" . $jobID . "&error=invaliddelay");
        exit;
    }
    if(!is_numeric($nextrun)) {
        header("location:editjob.php?jobID=" . $jobID . "&error=invalidnextrun");
        exit;
    }
    if(!is_numeric($lastrun)) {
        header("location:editjob.php?jobID=" . $jobID . "&error=invalidlastrun");
        exit;
    }
    
  
    $stmt = $db->prepare("UPDATE jobs SET name = ?, url = ?, host = ?, delay = ?, nextrun = ?, expected = ?, lastrun = ? WHERE jobID = ?");
    $stmt->execute(array($name, $url, $host, $delay, $nextrun, $expected, $lastrun, $jobID));
    
    header("location:overview.php?message=edited");
    exit;
}


require_once 'include/finalize.inc.php';