<?php
include(__DIR__ . '/../env.php');
include_once("../includes/timer.php");

include_once(__DIR__ . '/includes/template.php');
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
?><script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<SCRIPT LANGUAGE=\"Javascript\" SRC=\"/includes/jscripts/datepicker.js\"></SCRIPT>
<FORM ACTION='edi_traffic_view_tp.php' NAME=\"webedi_reports\" METHOD=\"POST\" xONCLICK=\"document.forms.webedi_reports.form_submit.disabled=true;\">
    <HR><?php
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

$year = request("form_year", date("Y"));
$month = request("form_month", date("n"));
$userid = request("form_userid", "");

#$partner = mysql_row("SELECT * FROM webedi_tp WHERE userid = $userid");
$partner = mysql_row("SELECT * FROM webedi30.Partners WHERE PK_id = $userid");

#print_r($partner);
$timer->report("FINISHED SELECTING * FROM webedi_tp");

### OVERRIDES...
foreach (array("1895" => "1898", "1612" => "1475", "2026" => "1986", "1875" => "1765", "1837" => "1370") as $webtpid => $new_webtpid) {
    if ($partner["webtpid"] == $webtpid)
        $partner["webtpid"] = $new_webtpid;
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

if ($_REQUEST["btn_submit"] == "Add Charge") {
    mysql_query("INSERT INTO webedi_tp_charges (FK_tp,status) VALUES($userid,'pending')");
}
if ($_REQUEST["btn_submit"] == "Save Changes") {
    #UPDATE BILLING INFORMATION
    $updating = true;
    set_param_tp("billing_schema", $_REQUEST["form_schema"], "$userid");
    set_param_tp("payment_method", $_REQUEST["form_payment_method"], "$userid");
    set_param_tp("No3KBlocks", $_REQUEST["form_no_3k"], "$userid");

    $def_schema = $_REQUEST["form_schema"];
    #set_param_tp("billing_mode",$_REQUEST["form_billing_m"],"$local_id3");
    #UPDATE CHARGES
    foreach ($_REQUEST as $name => $value) {
        if (strstr($name, "charge:")) {
            $parts = split(":", $name);
            $id = $parts[2];
            $field = $parts[1];

            mysql_query("UPDATE webedi_tp_charges SET $field = '$value' WHERE PK_id = $id");
        }
    }
}
if ($_REQUEST["btn_submit"] == "Delete Selected Charges") {
    foreach ($_REQUEST as $name => $value) {
        if (strstr($name, "charge:id:")) {
            $parts = split(":", $name);
            $id = $parts[2];
            $field = $parts[1];

            mysql_query("DELETE FROM webedi_tp_charges WHERE PK_id = $id");
        }
    }
}

#if($_REQUEST["form_changesch"]=="Update Billing Information")
#{
#	#MOVED UP
#}
error_log(__FILE__ . " :: " . __LINE__ . " :: GOING");
print "<A NAME='TPID:$tpid'>";

for ($y = 0; $y < 4; $y++)
    $arr_years[] = array(date("Y") - $y, date("Y") - $y);

for ($m = 1; $m < 13; $m++)
    $arr_months[] = array(date("F", strtotime("$m/01/2001")), date("n", strtotime("$m/01/2001")));

$arr_billing_m = array(array("WebEDI", "webedi"), array("Integrated", "integrated"));
$def_schema = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'billing_schema'");
$def_billing_m = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'billing_mode'");
$payment_method = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'payment_method'");
$def_no_3k = mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$userid' AND param = 'No3KBlocks'");

$timer->report("FINISHED SELECTING PARAMS.");

if ($def_no_3k == "")
    $def_no_3k = "no";

$no3KBlocks = $def_no_3k == "yes";

if ($def_billing_m == "") {
    #IF NO billing mode is defined, it is set here to WebeDI -- this can be changed later to integrated through the interface
    #set_param_tp("billing_schema",2,"$userid");
    #set_param_tp("billing_mode","webedi","$userid");
    #$def_billing_m="webedi";
    #$def_schema=2;
}

$arr_payment_method = array(array("Send Invoice", "inv"), array("Credit Card", "CC"));

$form_submit = form_submit("form_submit", "Submit", "");
$form_changesch = form_submit("btn_submit", "Save Changes");
$form_gen_inv = form_submit("form_gen_inv", "Send Invoice");
$form_tpid = "<INPUT TYPE='HIDDEN' NAME='form_tpid' VALUE='$tpid'>";
$form_userid = "<INPUT TYPE='HIDDEN' NAME='form_userid' VALUE='$userid'>";
$form_year = form_select("form_year", $arr_years, $year);
$form_month = form_select("form_month", $arr_months, $month);
$form_schema = form_select("form_schema", $arr_plans, $def_schema);
$form_billing_m = form_select("form_billing_m", $arr_billing_m, $def_billing_m);
$form_no_3k = form_select("form_no_3k", array(array("yes", "yes"), array("no", "no")), $def_no_3k);
$form_payment_method = form_select("form_payment_method", $arr_payment_method, $payment_method);

print "<FONT STYLE='font-family:verdana;font-size:20;font-weight:bold'>Billing Detail for {$partner["name"]}</FONT><BR>";
print "<HR>";

print $form_tpid;
print $form_userid;
print $form_year;
print $form_month;
print $form_submit;


$webedi_ftp = mysql_value("SELECT ftpdir FROM webedi_tp WHERE userid='$userid' ");
$timer->report("FINISHED SELECTING ftpdir");


$DATE_W = "(DATEPART(yyyy, created) = $year) AND (DATEPART(mm, created) = $month)";

$others_ = "";
error_log(__FILE__ . " :: " . __LINE__ . " :: GOING");
$filterDocsInclusive = getInclusiveDocFilter($userid);
include "edi_traffic_queries.php";
$timer->report("FINISHED LOADING edi_traffic_queries.php");

print "<BR>\n";
$f[30] = "<FONT STYLE='FONT-FAMILY:Verdana,Arial;FONT-SIZE:18;FONT-WEIGHT:Bold'>";
$f[0] = "</FONT>";

if ($_REQUEST["form_submit"] == "Submit" || $updating || $userid <> '') {
    if ($_SESSION["access_level"] >= 90) {
        print "<table width='100%' style='background-color: #3af'>";
        print "<tr>";
        print "<td style='font-family:verdana;font-size:12px;font-weight:bold;background-color:#DDDDDD;padding:5px;border: 1px solid black'>";
        print "&nbsp;&nbsp;";
        print "Billing Schedule: $form_schema";
        print "&nbsp;&nbsp;";
        print "&nbsp;&nbsp;";
        print " Invoice method: $form_payment_method &nbsp;&nbsp;";
        print "&nbsp;&nbsp;";
        #print "Billing Mode: $form_billing_m ";
        print " Disable 3K Blocks $form_no_3k";
        print "&nbsp;&nbsp;";
        print "&nbsp;&nbsp;";
        print "$form_changesch ";

        print "</td>";
        print "</tr>";
        print "</table>";
    }

    print "<BR><BR>";
    if (true) { // $tpid!='')
        $no_count = mysql_value("SELECT ignore_size FROM webedi_tp_billing_schemas WHERE PK_id = '$def_schema'");

        $dtlTemplate = new Template(__DIR__ . '/includes/tpl');
        $dtlTemplate->set_file('as2a', 'detailview.tpl');
        $dtlTemplate->set_var(array("NAME" => "WinProd", "DB" => "AS2A", "QSTRING" => $_SERVER['QUERY_STRING']));
        $out = $dtlTemplate->finish($dtlTemplate->parse("OUT", "as2a"));

        $dtlTemplate->set_file('as2b', 'detailview.tpl');
        $dtlTemplate->set_var(array("NAME" => "AS2B", "DB" => "AS2B", "QSTRING" => $_SERVER['QUERY_STRING']));
        $out .= $dtlTemplate->finish($dtlTemplate->parse("OUT", "as2b"));

        $dtlTemplate->set_file('as2c', 'detailview.tpl');
        $dtlTemplate->set_var(array("NAME" => "AS2C", "DB" => "AS2C", "QSTRING" => $_SERVER['QUERY_STRING']));
        $out .= $dtlTemplate->finish($dtlTemplate->parse("OUT", "as2c"));
        /*
          #print_r($tpid);
          $out = "<h1 style='background-color: #3af'>WinProd</h1>";
          $out .= file_get_contents('https://www.datatranswebedi.com/billing2/edi_traffic_subview_tp.php?db=AS2A&'.$_SERVER['QUERY_STRING']); //report_tp($tpid,$no3KBlocks);
          $out .= "<h1 style='background-color: #3af'>AS2B</h1>";
          $out .= file_get_contents('https://www.datatranswebedi.com/billing2/edi_traffic_subview_tp.php?db=AS2B&'.$_SERVER['QUERY_STRING']);
          $out .= "<h1 style='background-color: #3af'>AS2C</h1>";
          $out .= file_get_contents('https://www.datatranswebedi.com/billing2/edi_traffic_subview_tp.php?db=AS2C&'.$_SERVER['QUERY_STRING']);
         */ $timer->report("FINISHED report_tp($tpid,$no3KBlocks);");
        #die("$out");
        if ($def_schema) {
            # AS2A
            report_tp($tpid, $no3KBlocks);
            # AS2B
            $ms_db_name2 = "AS2B";
            $ms_db2 = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass) or die("Unable to connect!");
            mssql_select_db($ms_db_name2, $ms_db2) or die("Could not select DB: $ms_db_name");

            $mrs = mssql_query("SELECT * FROM partners Where local_id1 = '{$partner["webtpid"]}'") or die(mssql_message());
            $row = mssql_fetch_array($mrs);
            $tpid = $row["tpid"];   #Customer's ID in ECS database
            if ($tpid) {
                $filterDocsInclusive = getInclusiveDocFilter($userid);
                include "edi_traffic_queries.php";
                report_tp($tpid, $no3KBlocks);
            }

            # AS2C
            $ms_db_name3 = "AS2C";
            $ms_db3 = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass) or die("Unable to connect!");
            mssql_select_db($ms_db_name3, $ms_db3) or die("Could not select DB: $ms_db_name");
            $mrs = mssql_query("SELECT * FROM partners Where local_id1 = '{$partner["webtpid"]}'") or die(mssql_message());
            $row = mssql_fetch_array($mrs);
            $tpid = $row["tpid"];   #Customer's ID in ECS database
            if ($tpid) {
                $filterDocsInclusive = getInclusiveDocFilter($userid);
                include "edi_traffic_queries.php";
                report_tp($tpid, $no3KBlocks);
            }

            calculate_billing($def_schema, $edi_total_quantity);
            $timer->report("FINISHED calculate_billing($def_schema,$edi_total_quantity);");
        }
    }

    print "<!-- INPUT TYPE='SUBMIT' NAME='btn_submit' VALUE='Recalculate Billing' STYLE='font-size:10' -->";
    print "<BR><BR>";

    if ($_REQUEST["btn_submit"] == "Recalculate Billing") {
        $edi_total_quantity = 0;
        $total_quantity = 0;

        mysql_query("DELETE FROM webedi_tp_monthly_traffic WHERE month=$month AND year=$year AND FK_tp=$userid AND status='pending'");
        $timer->report("FINISHED DELETING webedi_tp_monthly_traffic");

        $count = mysql_value("SELECT count(*) FROM webedi_tp_monthly_traffic WHERE month=$month AND year=$year AND FK_tp=$userid");
        $timer->report("FINISHED SELECT coint(*) FROM webedi_tp_monthly_traffic");

        if ($count == 0) {
            $def_schema = 28;
            $sql33 = "INSERT INTO webedi_tp_monthly_traffic (FK_tp,month,year,traffic,billing_schema,date) VALUES($userid,$month,$year,$edi_total_quantity,$def_schema,now())";

            mysql_query($sql33) or die(mysql_error());
            $url33 = "https://www.datatranswebedi.com/edi_traffic_load.php?userid=$userid";
            print $url33 . "<BR>";
            $x = get_url($url33);
            $timer->report("FINISHED recalculation");
        }
    }

    print "<BR>";
    print "<B>$f[20]Pending Charges:$f[0]</B><BR>";
    print "<TABLE BORDER=1 CELLSPACING=0 CELLPADDING=3 BORDERCOLOR='black' style='background-color: #3af'>";
    print "<TR STYLE='font-familiy:verdana;font-size:12;font-weight:bold'>";
    print "<TD>Date</TD>";
    print "<TD>Description</TD>";
    print "<TD>Amount</TD>";
    print "<TD>Recurring</TD>";
    print "<TD>&nbsp;</TD>";
    print "</TR>";

    $charges = mysql_rows("SELECT * FROM webedi_tp_charges WHERE FK_tp = $userid AND status='pending'");
    $timer->report("FINISHED SELECTING * FROM webedi_tp_charges");

    foreach ($charges as $charge) {
        $form_date = form_date("charge:date:{$charge["PK_id"]}", $charge["date"]);
        $form_description = form_input("charge:description:{$charge["PK_id"]}", $charge["description"], "SIZE=80");
        $form_amount = form_input("charge:amount:{$charge["PK_id"]}", $charge["amount"], "SIZE=8");
        $form_recurring = form_select("charge:recurring:{$charge["PK_id"]}", array(array("yes", 1), array("no", "0")), $charge["recurring"]);
        $form_delete = "<INPUT TYPE='CHECKBOX' NAME='charge:id:{$charge["PK_id"]}' VALUE='{$charge["PK_id"]}'>";

        print "<TR STYLE='font-familiy:verdana;font-size:12;font-weight:none'>";
        print "<TD>$form_date</TD>";
        print "<TD>$form_description</TD>";
        print "<TD>$form_amount</TD>";
        print "<TD>$form_recurring</TD>";
        print "<TD>$form_delete</TD>";
        print "</TR>";
    }
    print "<TR><TD COLSPAN='5'>";
    if ($_SESSION["access_level"] >= 90) {
        print "<INPUT TYPE='SUBMIT' NAME='btn_submit' VALUE='Add Charge' STYLE='font-size:10'>";
        print "<INPUT TYPE='SUBMIT' NAME='btn_submit' VALUE='Delete Selected Charges' STYLE='font-size:10'>";
    }
    #print "<INPUT TYPE='SUBMIT' NAME='btn_submit' VALUE='Save Charges' STYLE='font-size:10'>";
    print "</TD></TR>";
    print "</TABLE>";
}

print "<BR>";
print "<HR>";



###
### PRINT LAST WEBEDI INVOICES:
###

print "<BR>";
print "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 WIDTH=400 style='background-color: #3af'>";

print "<TR STYLE='font-family:verdana;font-weight:bold;font-size:18;BORDER: solid 1px black'>";
print "<TD COLSPAN=3><B>{$f[20]}Last Invoices: {$f[0]}</B></TD>";
print "</TR>";

print "<TR STYLE='font-family:verdana;font-weight:bold;font-size:14;BORDER: solid 1px black'>";
print "<TD>Date</TD>";
print "<TD>Invoice No</TD>";
print "<TD>Amount</TD>";
print "<TD></TD>";
print "</TR>";

$invoices = mysql_rows("SELECT * FROM webedi_messages WHERE FK_tp_profile = $userid AND FK_client_profile = 489 ORDER BY PK_id DESC LIMIT 24");
$timer->report("FINISHED SELECTING * FROM webedi_messages (invoices)");

if ($invoices) {
    #print "<PRE>";
    #print_r($invoices);
    #print "</PRE>";
    foreach ($invoices as $invoice) {
        $tpl_vars["date"] = $invoice["date"];
        $tpl_vars["invoice_no"] = $invoice["edi_trace_num"];
        $tpl_vars["msg_id"] = $invoice["PK_id"];
        $total_amount = mysql_value("SELECT total_amount FROM form_810_summary WHERE FK_header = {$invoice["FK_form"]}");

        $total_amount = number_format($total_amount, 2);

        print "<TR STYLE='font-family:verdana;font-weight:none;font-size:12'>";
        print "<TD>{$invoice["inserted_datetime"]}</TD>";
        print "<TD>{$invoice["PK_id"]}</TD>";
        print "<TD>{$total_amount}</TD>";
        print "<TD><A HREF='view_invoice.php?i={$invoice["PK_id"]}' TARGET='_new'>view</A></TD>";
        print "</TR>";
    }
} else
    print "<TD>No invoices found</TD>";

print "</TABLE>";

print "<HR>";


###
### PRINT DETAIL OF TRANSACTIONS:
###
print $out;

$timer->report("Finished everything");
