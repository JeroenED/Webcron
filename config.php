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
 * all copies or substantia²l portions of the Software.
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