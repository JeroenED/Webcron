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

if(!isset($_GET['jobID'])) {
    header("location:/overview.php");
    exit;
}
$jobID = $_GET['jobID'];

$jobnameqry = $db->prepare("SELECT * FROM jobs WHERE jobID = ?");
$jobnameqry->execute(array($_GET['jobID']));
$jobnameResult = $jobnameqry->fetchAll(PDO::FETCH_ASSOC);
if ($jobnameResult[0]["user"] != $_SESSION["userID"]) {
    die(json_encode(array("error" => "You dirty hacker!")));
}


$client = new \GuzzleHttp\Client();

$res = $client->request('GET', $jobnameResult[0]['url']);

$stmt = $db->prepare("INSERT INTO runs(job, statuscode, result, timestamp)  VALUES(?, ?, ?, ?)");
$stmt->execute(array($jobID, $statuscode, $body, $timestamp));

echo json_encode(array("message" => "Cronjob succesfully ran"));

require_once 'include/finalize.inc.php';
