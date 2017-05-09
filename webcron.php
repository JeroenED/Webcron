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

if(file_exists('cache/webcron.lock'))
{
    die('Script is already running');
}
touch('cache/webcron.lock');

/**
 * Reboot finalize
 */
if (file_exists("cache/get-services.trigger")) {
    $rebootjobs = unserialize(file_get_contents("cache/get-services.trigger"));
    $services = array();
    exec("systemctl list-unit-files | cat", $services);;
    $services = implode("\n", $services);
    
    foreach($rebootjobs as $job) {
        $stmt = $db->query("SELECT jobID, delay, nextrun FROM jobs WHERE jobID = " . $job);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
        
        $stmt = $db->prepare("INSERT INTO runs(job, statuscode, result, timestamp)  VALUES(?, ?, ?, ?)");
        $stmt->execute(array($result['jobID'], '200', $services, time()));

        $nextrun = $result['nextrun'] + $result['delay'];
        if ($nextrun < time() ) { $nextrun = time() + $result['delay']; }

        $nexttime = $db->prepare("UPDATE jobs SET nextrun = ? WHERE jobID = ?");
        $nexttime->execute(array($nextrun, $result["jobID"]));
    }
    unlink("cache/get-services.trigger");
}

$stmt = $db->query('SELECT jobID, url, delay, nextrun, type FROM jobs WHERE nextrun < ' . time());
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$client = new \GuzzleHttp\Client();

$rebootjobs = array();
foreach ($results as $result) {

    if($result["type"] == "web") {
        $res = $client->request('GET', $result['url']);
    
        $statuscode = $res->getStatusCode();
        $body = $res->getBody();
    } elseif ($result["type"] == "bash") {
 
        if($result["url"] != "reboot") {
            $body = '';
            $result = 0;
            exec($result["url"], $body, $result);
        } else {
            $rebootjobs[] = $result['jobID'];
            touch("cache/reboot.trigger");
            $nosave = true;
        }
    }
    if($nosave !== true) {
        $stmt = $db->prepare("INSERT INTO runs(job, statuscode, result, timestamp)  VALUES(?, ?, ?, ?)");
        $stmt->execute(array($result['jobID'], $statuscode, $body, time()));

        $nextrun = $result['nextrun'] + $result['delay'];
        if ($nextrun < time() ) { $nextrun = time() + $result['delay']; }

        $nexttime = $db->prepare("UPDATE jobs SET nextrun = ? WHERE jobID = ?");
        $nexttime->execute(array($nextrun, $result["jobID"]));
    }
    $nosave = false;
}

unlink('cache/webcron.lock');

if(file_exists("cache/reboot.trigger")) {
    unlink("cache/reboot.trigger");
    $rebootser = serialize($rebootjobs);
    file_put_contents("cache/get-services.trigger", $rebootser);
    exec("systemctl reboot");
}
require_once 'include/finalize.inc.php';
