<?php

require_once "include/initialize.inc.php";

$jobnameqry = $db->prepare("SELECT name, user, url FROM jobs WHERE jobID = ?");
$jobnameqry->execute(array($_GET['jobID']));
$jobnameResult = $jobnameqry->fetchAll(PDO::FETCH_ASSOC);
if ($jobnameResult[0]["user"] != $_SESSION["userID"]) {
    header("location:/overview.php");
    exit;
}
$jobName = $jobnameResult[0]['name'];
$rebootjob = strpos($jobnameResult[0]["url"],"reboot") === 0 ? true : false;

$runsForJobQry = "SELECT runs.*, jobs.jobID FROM runs, jobs WHERE runs.job = jobs.jobID AND runs.job = ?";
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