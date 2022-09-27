<?php

include(__DIR__ . '/../env.php');
#	die(var_export($_REQUEST,true));
#	error_reporting(255);

$auth = false;
$good_ips = ADMIN_IPS;
foreach ($good_ips as $ip) {
    if (substr($_SERVER["REMOTE_ADDR"], 0, strlen($ip)) == $ip)
        $auth = true;
}
if (!$auth) {
    die("Unauthorized access");
}

include "includes/db.php";
include "includes/html_form_functions.php";
include "../includes/timer.php";
#include "includes/vars.php";
include "common2.php";
error_reporting(E_ERROR);

###	Load plans
$arr_plans = mysql_rows("SELECT name,PK_id FROM webedi_tp_billing_schemas");

print "<HTML>";
print "<BODY>";
print "<FORM NAME=\"webedi_reports\" METHOD=\"POST\" xONCLICK=\"document.forms.webedi_reports.form_submit.disabled=true;\">";
print "<HR>";

#	MS SQL
$ms_db_host = MSSQL_HOST;
$ms_db_user = MSSQL_USER;
$ms_db_pass = MSSQL_PASS;
$ms_db_name = MSSQL_DB_A;

$ms_db = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass) or die("Unable to connect!");
mssql_select_db($ms_db_name) or die("Could not select DB: $ms_db_name");

$bgcolor1 = "#DDDDDD";
$bgcolor2 = "#FFFFFF";
$bgcolor3 = "#CCCCCC";

$html_form_name = "webedi_reports";
$datepicker = true;

$fr_day = request("form_fr_day", date("d"));
$fr_year = request("form_fr_year", date("Y"));
$fr_month = request("form_fr_month", date("n"));

$to_day = request("form_to_day", date("d"));
$to_year = request("form_to_year", date("Y"));
$to_month = request("form_to_month", date("n"));

if (!is_numeric($to_day))
    $to_day = 1;
if ($to_day < 0 or $to_day > 31)
    $to_day = 1;

if (!is_numeric($fr_day))
    $fr_day = 1;
if ($fr_day < 0 or $fr_day > 31)
    $fr_day = 1;

$tpid = request("form_tpid", "");

if ($tpid <> "") {
    $local_id1 = mssql_value("SELECT local_id1 FROM Partners WHERE tpid = '$tpid'");
    $local_id3 = mssql_value("SELECT local_id3 FROM Partners WHERE tpid = '$tpid'");
}

if ($_REQUEST["form_changesch"] == "Update Billing Schedule") {
    $updating = true;
    set_param_tp("billing_schema", $_REQUEST["form_schema"], "$local_id3");
    $def_schema = $_REQUEST["form_schema"];
    #set_param_tp("billing_mode",$_REQUEST["form_billing_m"],"$local_id3");
}

print "<A NAME='TPID:$tpid'>";

$query = "SELECT * FROM Partners ORDER BY name";
$mrs = mssql_query("$query") or die(mssql_message());

$arr_tpids[] = array("Select a customer from the list", "");
while ($row = mssql_fetch_array($mrs)) {
    if ($tpid == $row["tpid"])
        $local_id3 = $row["local_id3"];
    $arr_tpids[] = array($row["name"], "{$row["tpid"]}");
}

for ($y = 0; $y < 4; $y++) {
    $arr_years[] = array(date("Y") - $y, date("Y") - $y);
}

for ($m = 1; $m < 13; $m++) {
    $arr_months[] = array(date("F", strtotime("$m/01/2001")), date("n", strtotime("$m/01/2001")));
}

$arr_billing_m = array(array("WebEDI", "webedi"), array("Integrated", "integrated"));

## Retrieves the billing schema (plan) assigned to the customer.
$def_schema = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$local_id3' AND param = 'billing_schema'");
## Retrieves the billing mode.
$def_billing_m = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$local_id3' AND param = 'billing_mode'");

## If billing mode not set, then we set it here to the default value.
if ($def_billing_m == "") {
    #IF NO billing mode is defined, it is set here to WebeDI -- this can be changed later to integrated through the interface
    set_param_tp("billing_schema", 2, "$local_id3");
    set_param_tp("billing_mode", "webedi", "$local_id3");
    $def_billing_m = "webedi";
    $def_schema = 2;
}

## Create form inputs...
$form_submit = form_submit("form_submit", "Submit", "");
$form_changesch = form_submit("form_changesch", "Update Billing Schedule");
$form_gen_inv = form_submit("form_gen_inv", "Send Invoice");
$form_tpid = form_select("form_tpid", $arr_tpids, $tpid, "ONCHANGE='document.forms.webedi_reports.form_changesch.disabled=true;'");

$form_fr_year = form_select("form_fr_year", $arr_years, $fr_year);
$form_fr_month = form_select("form_fr_month", $arr_months, $fr_month);
$form_fr_day = form_input("form_fr_day", $fr_day, "SIZE=1 MAXLENGTH=2 STYLE='font-size:10px;font-family:verdana'");

$form_to_year = form_select("form_to_year", $arr_years, $to_year);
$form_to_month = form_select("form_to_month", $arr_months, $to_month);
$form_to_day = form_input("form_to_day", $to_day, "SIZE=1 MAXLENGTH=2 STYLE='font-size:10px;font-family:verdana'");

$form_schema = form_select("form_schema", $arr_plans, $def_schema);
$form_billing_m = form_select("form_billing_m", $arr_billing_m, $def_billing_m);

print "
	<TABLE style='font-family:verdana;font-size:10px;font-weight:bold'>
	<TR>
		<TD>Customer:</TD><TD>$form_tpid</TD>
	</TR>
	<TR>
		<TD>From Date:</TD>
		<TD>$form_fr_month/$form_fr_day/$form_fr_year</TD>
	</TR>
	<TR>
		<TD>To Date:</TD>
		<TD>$form_to_month/$form_to_day/$form_to_year</TD>
	</TR>
	<TR>
		<TD COLSPAN='2'>
		$form_submit
		</TD>
	</TR>
	</TABLE>";

$others_ = "";
$userid = $local_id3;
$webedi_ftp = mysql_value("SELECT ftpdir FROM webedi_tp WHERE userid='$userid' ");
$DATE_W = "(DATEPART(yyyy, batches.created) = $year) AND (DATEPART(mm, batches.created) = $month)";

$fr_date = "$fr_month/$fr_day/$fr_year";
$to_date = "$to_month/$to_day/$to_year 23:59:59";

$DATE_W_CREATED = " (batches.created >= '$fr_date' AND batches.created <= '$to_date')";    ## This is for the Batches.created field
$DATE_W_TIMESTAMP = " (DTSDocuments.[timestamp] >= '$fr_date' AND DTSDocuments.[timestamp] <= '$to_date')"; ## This is for the DTSDocuments.timestamp field.


print "<A NAME='$tpid'><br>";

## Includes queries and exceptions
include "edi_traffic_queries_ranged.php";

$f[30] = "<FONT STYLE='FONT-FAMILY:Verdana,Arial;FONT-SIZE:18;FONT-WEIGHT:Bold'>";
$f[0] = "</FONT>";

if ($_REQUEST["form_submit"] == "Submit") {
    if ($tpid != '') {
        print "<HR>";
        $out = report_tp($tpid);
        print $out;
    } else {
        print "<b>Error:</b> Please specify a trading partner from the drop down above.";
    }
}
