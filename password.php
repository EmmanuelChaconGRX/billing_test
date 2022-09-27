<?php

session_start();

if ($_SESSION["auth"] != "OK") {
    header("location: .");
    die();
}

if ($_REQUEST["btn_submit"] == "Change Password") {
    $password = $_REQUEST["str_password"];
    $newpassword1 = $_REQUEST["str_newpassword1"];
    $newpassword2 = $_REQUEST["str_newpassword2"];

    if ($password == "" || $newpassword1 == "" || $newpassword2 == "") {
        $errors .= "<LI><FONT COLOR='RED'>Password, New Password and Password verification are required</FONT>";
    }

    if ($password != "") {
        $sql = "SELECT * FROM billing_users WHERE username='{$_SESSION["username"]}'";
        $rs = $_mysql->get_row($sql);

        ### Checks old password:
        if (sha1($password) == $rs["password"]) {
            ### Check to see if the new password and the verification match:
            if ($newpassword1 == $newpassword2) {
                ## Everything checks in fine. Let's update the database
                $sql = "UPDATE billing_users SET password=SHA1('$newpassword1') WHERE username='{$_SESSION["username"]}'";
                $rs = $_mysql->query($sql);
                print "<BR><BR><LI>The password was changed successfully, click <a href='./'>here</a> to return to main screen.";
                die();
            } else {
                $errors .= "<LI><FONT COLOR='RED'>New passwords don't match</FONT>";
            }
        } else {
            $errors = "<LI><FONT COLOR=\"RED\">Current password is invalid</FONT><BR>";
        }
    }
}




if ($errors != "") {
    print $errors;
}
include "./includes/tpl/password.php";
