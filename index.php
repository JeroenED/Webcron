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
    if(isset($_COOKIE["secure_auth"]) && isset($_COOKIE["secure_auth_name"])) {
        $userQry = $db->prepare("SELECT * FROM users WHERE name = ?");
        $userQry->execute(array($_COOKIE["secure_auth_name"]));
        $user = $userQry->fetchAll(PDO::FETCH_ASSOC);
        
        if (in_array($_COOKIE["secure_auth"], unserialize($user[0]["autologin"]))) {
            $_SESSION['userID'] = $user[0]['userID'];
            header("location:overview.php");
            exit;
        }
    }
    $loader = new Twig_Loader_Filesystem('templates');
    $twig = new Twig_Environment($loader, array('cache' => 'cache', "debug" => true));
    
    $error = "";
    if (isset($_GET["error"])) {
        switch ($_GET["error"]) {
            case "emptyfields":
                $error = "Some fields were empty"; break;
            case "invalidcredentials":
                $error = "The credentials were invalid"; break;
        }
    }
    
  
    echo $twig->render('index.html.twig', array("error" => $error));
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (empty($_POST['name']) || empty($_POST['passwd'])) {
        header("location:index.php?error=emptyfields");
        exit;
    }
    
    $passwd = $_POST['passwd'];
    $name = $_POST['name'];
    $autologin = $_POST["autologin"];
       
  
    $userQry = $db->prepare("SELECT * FROM users WHERE name = ?");
    $userQry->execute(array($name));
    $user = $userQry->fetchAll(PDO::FETCH_ASSOC);
    
    if ( password_verify($passwd, $user[0]['password']) ) {
        
        $_SESSION['userID'] = $user[0]['userID'];
        
        if ($autologin = "autologin") {
            $autologin = hash("sha512", time() . $user[0]["name"] . $user[0]["password"] . session_id());
            setcookie("secure_auth", $autologin, time() + (60 * 60 * 24 * 365));
            setcookie("secure_auth_name", $user[0]["name"] , time() + (60 * 60 * 24 * 365));
        
            $autologin_array = array();
            if (!empty($user[0]["autologin"])) $autologin_array = unserialize($user[0]["autologin"]);
            $autologin_array[] = $autologin;
            
            /*var_dump($autologin_array);
            exit;*/

            $loginQry = $db->prepare("UPDATE users SET autologin = ? WHERE userID = ?");
            $loginQry->execute(array(serialize($autologin_array), $_SESSION["userID"]));
        }
       
        
        header("location:overview.php");
        exit;
       
    } else {
        header("location:index.php?error=invalidcredentials");
        exit;
    }
}


require_once 'include/finalize.inc.php';