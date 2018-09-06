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

$jobnameqry = $db->prepare("SELECT name, user, url FROM jobs WHERE jobID = ?");
$jobnameqry->execute(array($_GET['jobID']));
$jobnameResult = $jobnameqry->fetchAll(PDO::FETCH_ASSOC);
if ($jobnameResult[0]["user"] != $_SESSION["userID"]) {
    header("location:/overview.php");
    exit;
}
$jobName = $jobnameResult[0]['name'];
$rebootjob = $jobnameResult[0]['name'] ? true : false;

$runsForJobQry = "SELECT runs.*, FROM runs, jobs WHERE runs.job = jobs.jobID AND runs.job = ?";
$allruns = true;
if(!(isset($_GET['allruns']) && $_GET['allruns'] == 1)) {
	$runsForJobQry .= " AND runs.statuscode <> jobs.expected";
	$allruns = false;
}
$runsForJob = $db->prepare($runsForJobQry);
$runsForJob->execute(array($_GET['jobID']));
$runsForJobResult = $runsForJob->fetchAll(PDO::FETCH_ASSOC);

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array('cache' => 'cache', "debug" => true));

//var_dump($alljobsResult);
//exit;

$runsForJobRendered = array();$count = 0;
foreach($runsForJobResult as $key=>$value) {
    $runsForJobRendered[$count]["runID"] = $value["runID"];
    $runsForJobRendered[$count]["statuscode"] = $value["statuscode"];
    $runsForJobRendered[$count]["result"] = $value["result"];
    $runsForJobRendered[$count]["timestamp"] = date("d/m/Y H:i:s", $value["timestamp"]);
    
    $count++;
}

$twig_vars = array('jobID' => $_GET['jobID'], 'rebootjob' => $rebootjob, 'runs' => $runsForJobRendered, 'allruns' => $allruns, "title" => $jobName);

//echo $twig->render('overview.html.twig', array('the' => 'variables', 'go' => 'here'));
echo $twig->render('runs.html.twig', $twig_vars);


require_once 'include/finalize.inc.php';