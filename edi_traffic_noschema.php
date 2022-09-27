<?php

#	die(var_export($_REQUEST,true));
include(__DIR__ . '/../env.php');
$auth = false;
$good_ips = ADMIN_IPS;
foreach ($good_ips as $ip) {
    if (substr($_SERVER["REMOTE_ADDR"], 0, strlen($ip)) == $ip)
        $auth = true;
}
if (!$auth) {
    die("Unauthorized access");
}

include "../includes/functions/db.php";
include "../includes/functions/html_form_functions.php";
include "../includes/vars.php";
include "../billing2/common2.php";
error_reporting(E_ERROR);

###	MS SQL CONNECTION
$ms_db_host = MSSQL_HOST;
$ms_db_user = MSSQL_USER;
$ms_db_pass = MSSQL_PASS;
$ms_db_name = MSSQL_DB_A;

$ms_db = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass) or die("Unable to connect!");
mssql_select_db($ms_db_name) or die("Could not select DB: $ms_db_name");

###	IF a user was specified:
if ($_REQUEST["userid"] != "") {
    $wuserid = "AND userid={$_REQUEST["userid"]}";
}

###	LOAD CUSTOMERS
$partners = mysql_rows("SELECT * FROM webedi_tp WHERE entity_type IN ('CS','WC','IT','2B','SW') $wuserid");

foreach ($partners as $partner) {
    $local_id1 = $partner["local_id1"];
    $local_id2 = $partner["local_id2"];
    $webtpid = $partner["webtpid"];
    $userid = $partner["userid"];

    if ($local_id1 == "")
        $local_id1 = $partner["webtpid"];

    $mrs = mssql_query("SELECT * FROM partners Where local_id1 = '$local_id1'") or die(mssql_message());
    $row = mssql_fetch_array($mrs);
    $tpid = $row["tpid"];   #Customer's ID in ECS database

    $year = date("Y");   #Current year
    $month = 4; #date("n");			#Current month

    $webedi_ftp = mysql_value("SELECT ftpdir FROM webedi_tp WHERE userid='$userid' ");
    $def_schema = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'billing_schema'");
    $def_billing_m = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'billing_mode'");
    #print "SCHEMA: $def_schema:$userid<BR>";
    if ($def_schema == "") {
        #IF NO billing mode is defined, it is set here to WebeDI -- this can be changed later to integrated through the interface
        //set_param_tp("billing_schema",2,"$userid");
        //set_param_tp("billing_mode","webedi","$userid");
        //$def_billing_m="webedi";
        //$def_schema=2;
        print "Schema not found for: {$partner["company_name"]}:{$partner["webtpid"]}:{$partner["userid"]}:$tpid:{$row["local_id1"]}<BR>";
    }
}
