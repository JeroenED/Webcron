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

if(file_exists('/tmp/webcron.lock'))
{
    die('Script is already running');
}
touch('/tmp/webcron.lock');

/**
 * Reboot finalize
 */
if (file_exists("cache/get-services.trigger")) {
    if (file_exists("cache/reboot-time.trigger") && file_get_contents("cache/reboot-time.trigger") < time()) { 
        $rebootjobs = unserialize(file_get_contents("cache/get-services.trigger"));
        
        foreach($rebootjobs as $job) {
            if($job['expected'] != 0) {
                $services = array();
                $url = "ssh " . $job['host'] . " '" . "sudo systemctl list-units | cat" . "' 2>&1";
                exec($url, $services);
                $services = implode("\n", $services);

                $stmt = $db->prepare("INSERT INTO runs(job, statuscode, result, timestamp)  VALUES(?, ?, ?, ?)");
                $stmt->execute(array($job['jobID'], '200', $services, time()));
            }
        }
        unlink("cache/get-services.trigger");
        unlink("cache/reboot-time.trigger");
    }
}

$stmt = $db->query('SELECT jobID, url, host, delay, nextrun, expected FROM jobs WHERE nextrun < ' . time());
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$client = new \GuzzleHttp\Client();

$rebootjobs = array();
if (file_exists("cache/get-services.trigger")) {
    $rebootjobs = unserialize(file_get_contents("cache/get-services.trigger"));
}

foreach ($results as $result) {

    if (filter_var($result["url"], FILTER_VALIDATE_URL)) {
        $res = $client->request('GET', $result['url']);
    
        $statuscode = $res->getStatusCode();
        $body = $res->getBody();
    } else {
 
        if($result["url"] != "reboot") {
            $body = '';
            $statuscode = 0;
            $url = "ssh " . $result['host'] . " '" . $result['url'] . "' 2>&1";
            exec($url, $body, $statuscode);
            $body = implode("\n", $body);
        } else {
            $rebootjobs = array();
            if (file_exists('cache/get-services.trigger')) {
                $rebootjobs = unserialize(file_get_contents('cache/get-services.trigger'));
            }
            if (!job_in_array($result['jobID'], $rebootjobs)) {
                $rebootjobs[] = $result;
                touch("cache/reboot.trigger");
                $nosave = true;
            }

        }
    }
    if($nosave !== true && $statuscode != $result["expected"]) {
        $stmt = $db->prepare("INSERT INTO runs(job, statuscode, result, timestamp)  VALUES(?, ?, ?, ?)");
        $stmt->execute(array($result['jobID'], $statuscode, $body, time()));
    }
    $nextrun = $result['nextrun'] + $result['delay'];
    if ($nextrun < time() ) { $nextrun = time() + $result['delay']; }

    $nexttime = $db->prepare("UPDATE jobs SET nextrun = ? WHERE jobID = ?");
    $nexttime->execute(array($nextrun, $result["jobID"]));
    $nosave = false;
}

unlink('/tmp/webcron.lock');

if(file_exists("cache/reboot.trigger")) {
    unlink("cache/reboot.trigger");
    $rebootser = serialize($rebootjobs);
    file_put_contents("cache/get-services.trigger", $rebootser);
    file_put_contents("cache/reboot-time.trigger", time() + (5 * 60));
    $rebooted_hosts = array();
    foreach($rebootjobs as $job) {
        
        $url = "ssh " . $job['host'] . " '" . 'sudo shutdown -r +5 "A reboot has been scheduled. Please save your work."' . "'";
        exec($url);
    }
}
require_once 'include/finalize.inc.php';
