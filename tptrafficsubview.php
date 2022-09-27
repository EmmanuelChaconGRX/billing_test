<?php

include(__DIR__ . '/../env.php');
include_once("../includes/timer.php");

session_start();
/*
  if($_SESSION["auth"]!="OK")
  {
  header("location: .");
  die();
  }

  ### This script is only for SUPER USERS...
  ### Users with lesser access will see this other version of the page:
  if($_SESSION["access_level"]<90){
  include_once("edi_traffic_view_tp_limited.php");
  die();
  }
 */
include_once("../includes/functions/db.php");
include_once("../includes/vars.php");
include_once("includes/form_functions.php");
include_once("includes/url.php");
include_once("includes/db2.php");
include_once("common2.php");

error_reporting(E_ERROR & ~E_NOTICE);
ini_set('display_errors', '1');
ini_set('log_errors', 'On');
ini_set('error_log', '/tmp/php-billing-error.log');
ini_set('memory_limit', '4G');
set_time_limit(7200);

error_log(__FILE__ . " :: " . __LINE__ . " :: GOING");

$timer->report("FINISHED LOADING libs");


###	Load plans into dropdown
$arr_plans = mysql_rows("SELECT name,PK_id FROM webedi_tp_billing_schemas");

$timer->report("FINISHED SELECTING Schemas");

print "<SCRIPT LANGUAGE=\"Javascript\" SRC=\"/includes/jscripts/datepicker.js\"></SCRIPT>";
print "<FORM ACTION='edi_traffic_view_tp.php' NAME=\"webedi_reports\" METHOD=\"POST\" xONCLICK=\"document.forms.webedi_reports.form_submit.disabled=true;\">";
print "<HR>";

#	MS SQL
$ms_db_host = MSSQL_HOST;
$ms_db_user = MSSQL_USER;
$ms_db_pass = MSSQL_PASS;
$ms_db_name = request("db", MSSQL_DB_A);

$ms_db = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass) or die("Unable to connect!");
mssql_select_db($ms_db_name) or die("Could not select DB: $ms_db_name");


$bgcolor1 = "#DDDDDD";
$bgcolor2 = "#FFFFFF";
$bgcolor3 = "#CCCCCC";

$html_form_name = "webedi_reports";
$datepicker = true;

$year = request("form_year", date("Y"));
$month = request("form_month", date("n"));
$userid = request("form_userid", "");

#$partner = mysql_row("SELECT * FROM webedi_tp WHERE userid = $userid");
$partner = mysql_row("SELECT * FROM webedi30.Partners WHERE PK_id = $userid");

#print_r($partner);
$timer->report("FINISHED SELECTING * FROM webedi_tp");

### OVERRIDES...
foreach (array("1895" => "1898", "1612" => "1475", "2026" => "1986", "1875" => "1765", "1837" => "1370") as $webtpid => $new_webtpid) {
    if ($partner["webtpid"] == $webtpid) {
        $partner["webtpid"] = $new_webtpid;
    }
}

#print_r("SELECT * FROM partners Where local_id1 = '{$partner["webtpid"]}'");
$mrs = mssql_query("SELECT * FROM partners Where local_id1 = '{$partner["webtpid"]}'") or die(mssql_message());
$row = mssql_fetch_array($mrs);
$tpid = $row["tpid"];   #Customer's ID in ECS database

$timer->report("FINISHED SELECTING * FROM Partners (SQL)");

if ($tpid <> "") {
    $local_id1 = mssql_value("SELECT local_id1 FROM Partners WHERE tpid = '$tpid'", $ms_db_name);
    $local_id3 = mssql_value("SELECT local_id3 FROM Partners WHERE tpid = '$tpid'", $ms_db_name);
}

#if($_REQUEST["form_changesch"]=="Update Billing Information")
#{
#	#MOVED UP
#}
error_log(__FILE__ . " :: " . __LINE__ . " :: GOING");

for ($y = 0; $y < 4; $y++) {
    $arr_years[] = array(date("Y") - $y, date("Y") - $y);
}

for ($m = 1; $m < 13; $m++) {
    $arr_months[] = array(date("F", strtotime("$m/01/2001")), date("n", strtotime("$m/01/2001")));
}

$arr_billing_m = array(array("WebEDI", "webedi"), array("Integrated", "integrated"));
$def_schema = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'billing_schema'");
$def_billing_m = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'billing_mode'");
$payment_method = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'payment_method'");
$def_no_3k = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'No3KBlocks'");

$billingSchemaResults = mysql_rows("SELECT * FROM webedi_tp_billing_schemas");
$allBillingSchemas = array();
foreach ($billingSchemaResults as $schema) {
    $allBillingSchemas[$schema['PK_id']] = $schema;
}
unset($billingSchemaResults);
$currentSchema = $allBillingSchemas[$def_schema];
$def_billing_m = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'billing_mode'");

$no_count = $currentSchema["ignore_size"];

$unit_size = ($currentSchema['unit_size'] == '3K') ? 3000 : 1000;
$dropship = ($currentSchema['dropship'] == 'Y');
$dropshipBasePrice = $currentSchema['dropship_base_price'];
    
$timer->report("FINISHED SELECTING PARAMS.");

if ($def_no_3k == "") {
    $def_no_3k = "no";
}

$no3KBlocks = $def_no_3k == "yes";


$webedi_ftp = mysql_value("SELECT ftpdir FROM webedi_tp WHERE userid='$userid' ");
$timer->report("FINISHED SELECTING ftpdir");


$DATE_W = "(DATEPART(yyyy, created) = $year) AND (DATEPART(mm, created) = $month)";

$others_ = "";
error_log(__FILE__ . " :: " . __LINE__ . " :: GOING");
if('y' != $_GET['alldocs'])  {
  $filterDocsInclusive = getInclusiveDocFilter($userid);
}
if (!empty($_GET['filterdocs'])) {
    // Hack to get Separated sections for 832s and 867s for Highmark
    $filterDocsInclusive = $_GET['filterdocs'];
}
include "edi_traffic_queries.php";
$timer->report("FINISHED LOADING edi_traffic_queries.php");

$f[30] = "<FONT STYLE='FONT-FAMILY:Verdana,Arial;FONT-SIZE:18;FONT-WEIGHT:Bold'>";
$f[0] = "</FONT>";
if ($tpid != '') {
    $no_count = mysql_value("SELECT ignore_size FROM webedi_tp_billing_schemas WHERE PK_id = '$def_schema'");

    $out = report_tp($tpid, $no3KBlocks);
    $timer->report("FINISHED report_tp($tpid,$no3KBlocks);");
}

###
### PRINT DETAIL OF TRANSACTIONS:
###
print $out;

$timer->report("Finished everything");
