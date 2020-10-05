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

if(file_exists('/tmp/webcron.lock') && file_get_contents('/tmp/webcron.lock') + get_configvalue('master.crashtimeout') > time() )
{
    die('Script is already running');
}
if(file_exists('/tmp/webcron.lock')) unlink('/tmp/webcron.lock');
file_put_contents('/tmp/webcron.lock', time());

/**
 * Reboot finalize
 */
if (file_exists(__DIR__ . "/cache/get-services.trigger")) {
    if (file_exists(__DIR__ . "/cache/reboot-time.trigger") && file_get_contents(__DIR__ . "/cache/reboot-time.trigger") < time()) {
        $rebootjobs = json_decode(file_get_contents(__DIR__ . "/cache/get-services.trigger"), true);
        
        foreach($rebootjobs as $job) {
            $services = array();
            $rebooter = preg_replace("/reboot /", "", $job['url'], 1);
            $rebooter = urlencode($rebooter);
            $rebooter = str_replace("cmd%3D", "cmd=", $rebooter);
            $rebooter = str_replace("services%3D", "services=", $rebooter);
            $rebooter = str_replace("%26", "&", $rebooter);
            parse_str($rebooter, $rebootcommands);
            $cmd = $rebootcommands['services'];

            if ($cmd == '') {
                $cmd = "sudo systemctl list-units | cat";
            }
            $url = "ssh " . $job['host'] . " '" . $cmd . "' 2>&1";
            exec($url, $services);

            $cmd = '';
            $services = implode("\n", $services);

            $stmt = $db->prepare("INSERT INTO runs(job, statuscode, result, timestamp)  VALUES(?, ?, ?, ?)");
            $stmt->execute(array($job['jobID'], '0', $services, time()));
        }
        unlink(__DIR__ . "/cache/get-services.trigger");
        unlink(__DIR__ . "/cache/reboot-time.trigger");
    }
}

$stmt = $db->prepare('SELECT * FROM jobs WHERE nextrun <= ? and (nextrun <= lastrun OR lastrun = -1)');
$stmt->execute(array(time()));
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$client = new \GuzzleHttp\Client();

$rebootjobs = array();
if (file_exists(__DIR__ . "/cache/get-services.trigger")) {
    $rebootjobs = json_decode(file_get_contents(__DIR__ . "/cache/get-services.trigger"), true);
}

foreach ($results as $result) {

    if (filter_var($result["url"], FILTER_VALIDATE_URL)) {
        $res = $client->request('GET', $result['url'], ['http_errors' => false]);
    
        $statuscode = $res->getStatusCode();
        $body = $res->getBody();
    } else {
	    if(strpos($result["url"],"reboot") !== 0) {
            $nosave = false;
            $body = '';
            $statuscode = 0;
            $url = "ssh " . $result['host'] . " '" . $result['url'] . "' 2>&1";
            exec($url, $body, $statuscode);
            $body = implode("\n", $body);
        } else {
 	        $rebootjobs = array();
            if (file_exists(__DIR__ . '/cache/get-services.trigger')) {
                $rebootjobs = json_decode(file_get_contents(__DIR__ . '/cache/get-services.trigger'), true);
            }
            if (!job_in_array($result['jobID'], $rebootjobs)) {
                echo "no hope";
                $rebootjobs[] = $result;
                $rebootser = json_encode($rebootjobs);
                file_put_contents(__DIR__ . "/cache/get-services.trigger", $rebootser);
                touch(__DIR__ . "/cache/reboot.trigger");
                $nosave = true;
            }

        }
    }
    if(!$nosave) {
        $stmt = $db->prepare("INSERT INTO runs(job, statuscode, result, timestamp)  VALUES(?, ?, ?, ?)");
        $stmt->execute(array($result['jobID'], $statuscode, $body, time()));
    }

    $nextrun = $result['nextrun'];
    do {
        $nextrun = $nextrun + $result['delay'];
    } while ($nextrun < time());

    $nexttime = $db->prepare("UPDATE jobs SET nextrun = ? WHERE jobID = ?");
    $nexttime->execute(array($nextrun, $result["jobID"]));
    $nosave = false;
}

if ((get_configvalue('dbclean.enabled') == 'true') && (get_configvalue('dbclean.lastrun') + (60 * 60 * 24 * get_configvalue('dbclean.delay')) < time())) clean_database();

unlink('/tmp/webcron.lock');

if(file_exists(__DIR__ . "/cache/reboot.trigger")) {
    unlink(__DIR__ . "/cache/reboot.trigger");
    $count=0;
    foreach($rebootjobs as $job) {
        print_r($job);
        if (!(isset($job['done']) && $job['done'] == true)) {
            $rebooter = preg_replace("/reboot /", "", $job['url'], 1);
            $rebooter = urlencode($rebooter);
            $rebooter = str_replace("cmd%3D", "cmd=", $rebooter);
            $rebooter = str_replace("services%3D", "services=", $rebooter);
            $rebooter = str_replace("%26", "&", $rebooter);
            parse_str($rebooter, $rebootcommands);
            $cmd = $rebootcommands['cmd'];

            if ($cmd == '') {
                $cmd = 'sudo shutdown -r +{m}+ "A reboot has been scheduled. Please save your work."';
            }

            $cmd = str_replace("{m}+", intdiv(get_configvalue('jobs.rebootwait'), 60), $cmd);
            $cmd = str_replace("{s}+", get_configvalue('jobs.rebootwait'), $cmd);
            $url = "ssh " . $job['host'] . " '" . $cmd . " &'";
            echo $url;
            exec($url);
            $cmd = '';
            $rebootjobs[$count]['done'] = true;
        }
        $count++;
    }

    $rebootser = json_encode($rebootjobs);
    file_put_contents(__DIR__ . "/cache/get-services.trigger", $rebootser);
    file_put_contents(__DIR__ . "/cache/reboot-time.trigger", time() + (get_configvalue('jobs.reboottime') + get_configvalue('jobs.rebootwait')));
}
require_once 'include/finalize.inc.php';
