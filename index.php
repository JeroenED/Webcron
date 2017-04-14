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
            case "invalidcredentials":
                $error = "The credentials were invalid"; break;
        }
    }
    
  
    echo $twig->render('index.html.twig', array("message" => $message, "error" => $error));
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (empty($_POST['name']) || empty($_POST['passwd'])) {
        header("location:index.php?error=emptyfields");
        exit;
    }
    
    $passwd = $_POST['passwd'];
    $name = $_POST['name'];
       
  
    $userQry = $db->prepare("SELECT * FROM users WHERE name = ?");
    $userQry->execute(array($name));
    $user = $userQry->fetchAll(PDO::FETCH_ASSOC);
    
    if ( password_verify($passwd, $user[0]['password']) ) {
        $_SESSION['userID'] = $user[0]['userID'];
        header("location:overview.php");
        exit;
       
    } else {
        header("location:index.php?error=invalidcredentials");
        exit;
    }
}


require_once 'include/finalize.inc.php';