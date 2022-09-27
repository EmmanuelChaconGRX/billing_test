<?php

ob_start();

include "./includes/db.php";
include "./includes/url.php";
include "./includes/session.php";

$action = $_REQUEST["action"];

if ($action == "") {
    $action = "main";
}
switch ($action) {
    case "":
    case "main":
        include "edi_traffic_main_v3.php";
        break;
    case "line":
        include "edi_traffic_line.php";
        break;
    case "main2":
        include "edi_traffic_main_v3.php";
        break;
    case "history":
        include "edi_traffic_history.php";
        break;
    case "password":
        include "password.php";
        break;
    case "options":
        include "options.php";
        break;
}
