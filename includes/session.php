<?php

session_start();

#Session validation.
require_once(__DIR__ ."/db2.php");

if ($_REQUEST["btn_submit"] == "Logout") {
    $_SESSION["auth"] = "";
    header("location: index.php");
} else
if ($_REQUEST["btn_submit"] == "Log in") {
    $username = $_REQUEST["str_username"];
    $password = $_REQUEST["str_password"];
    if ($username != "") {
        if($username === "admin" && sha1($password) === _ADMIN_PASS) {
            $_SESSION["auth"] = "OK";
            $_SESSION["access_level"] = 100;
            $_SESSION["user_id"] = 1;
            $_SESSION["username"] = "admin";
            $_SESSION["firstname"] = "admin";
            $_SESSION["lastname"] = "admin";
            header("location: index.php");
            die();
        }
        $rs = $_mysql->get_row("SELECT * FROM billing_users WHERE username='$username'");
        if ($rs["password"] == sha1($password)) {
            $_SESSION["auth"] = "OK";
            $_SESSION["access_level"] = $rs["access_level"];
            $_SESSION["user_id"] = $rs["PK_id"];
            $_SESSION["username"] = $rs["username"];
            $_SESSION["firstname"] = $rs["firstname"];
            $_SESSION["lastname"] = $rs["lastname"];
            header("location: index.php");
            die();
        } else {
            $login_errors = "<LI><FONT COLOR=\"RED\">Invalid username/password</FONT><BR>";
        }
    }
}
if ($_SESSION["auth"] == "") {
    # Session doesn't exist.
    print "<B>Access to this site is restricted</B><BR>";
    print "<B>Please enter your username/password below:</B><BR>";
    print $login_errors;
    include "./includes/tpl/login.php";
    die();
} else {
    print "<FONT STYLE=\"font-family:verdana;font-size:14\">";
    print "<DIV class='title'>You are logged in as <B>{$_SESSION["username"]}</B></div>";
    print "<DIV class='men'><A HREF='index.php?btn_submit=Logout'>Logout</A></div><div class='men'><A HREF='index.php?action=options'>Options</A></DIV>";
    print "</FONT><BR><BR>";
    print "
		<STYLE>
			.title{
				width: 800px;
				background-color: #DDDDDD;
				border:2px solid #8888FF;
				list-style: none;
				margin:2px 0 2px 1px;
				padding:4px 10px 4px 10px;
				font-weight: bold;
				font-size: 16px;
				position: relative;
			}
			.men{
				width: 100px;
				background-color: #D1E6EC;
				border:2px solid #7EA6B2;
				list-style: none;
				margin:2px 0 2px 1px;
				padding:4px 10px 4px 10px;
				font-weight: bold;
				font-size: 11px;
				position: relative;
				float:left;
			}
			.men a{
				text-decoration:none;
			}
		</STYLE>
		";
}
