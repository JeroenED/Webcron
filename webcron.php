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

$stmt = $db->query('SELECT jobID, url, delay, nextrun FROM jobs WHERE nextrun < ' . time());
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$client = new \GuzzleHttp\Client();

foreach ($results as $result) {
    
    $res = $client->request('GET', $result['url']);
    
    $statuscode = $res->getStatusCode();
    $body = $res->getBody();
    
    $stmt = $db->prepare("INSERT INTO runs(job, statuscode, result, timestamp)  VALUES(?, ?, ?, ?)");
    $stmt->execute(array($result['jobID'], $statuscode, $body, time()));
    
    $nextrun = $result['nextrun'] + $result['delay'];
    if ($nextrun < time() ) { $nextrun = time() + $result['delay']; }

    $nexttime = $db->prepare("UPDATE jobs SET nextrun = ? WHERE jobID = ?");
    $nexttime->execute(array($nextrun, $result["jobID"]));

}

unlink('cache/webcron.lock');

require_once 'include/finalize.inc.php';
