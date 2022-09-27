<?php

session_start();

if ($_SESSION["auth"] != "OK") {
    header("location: .");
    die();
}

die("H");

if ($_REQUEST["btn_submit"] == "Change Password") {
    $password = $_REQUEST["str_password"];
    $newpassword1 = $_REQUEST["str_newpassword1"];
    $newpassword2 = $_REQUEST["str_newpassword2"];

    if ($password == "" || $newpassword1 == "" || $newpassword2 == "") {
        $login_errors .= "<LI><FONT COLOR='RED'>Password, New Password and Password verification are required</FONT>";
    }

    if ($password != "") {
        $rs = $_mysql->get_row("SELECT * FROM billing_users WHERE username='{$_SESSION["username"]}'");
        print_r($rs);
        die();
        if (sha1($password) == $rs["password"]) {
            ### Check to see if the new password and the verification match:
            if ($newpassword1 == $newpassword2) {
                
            }
        }
        /*
          if($rs["password"]==$password)
          {
          $_SESSION["auth"]="OK";
          $_SESSION["access_level"]=$rs["access_level"];
          $_SESSION["username"]=$rs["username"];
          $_SESSION["firstname"]=$rs["firstname"];
          $_SESSION["lastname"]=$rs["lastname"];
          header("location: index.php");
          die();
          }
         */ else {
            $login_errors = "<LI><FONT COLOR=\"RED\">Current password is invalid</FONT><BR>";
        }
    }
}

#include "./includes/tpl/password.php";
#print "<FONT STYLE=\"font-family:verdana;font-size:14\">";
#print "You are logged in as <B>{$_SESSION["username"]}</B>, 1click <A HREF='index.php?btn_submit=Logout'>here</A> to log out.";
#print "</FONT><BR><BR>";
