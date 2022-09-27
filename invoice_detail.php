<?php

session_start();

if ($_SESSION["auth"] != "OK") {
    header("location: .");
    die();
}

$i = $_REQUEST["i"];

include "../includes/functions/db.php";
include "../includes/vars.php";
include "includes/form_functions.php";
include "includes/url.php";
include "includes/db2.php";
include "common2.php";

error_reporting(E_ERROR);

#PRINT WEBEDI INVOICE DETAIL:
print "<BR><BR>";
print "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 WIDTH=400>";

print "<TR STYLE='font-family:verdana;font-weight:bold;font-size:18;BORDER: solid 1px black'>";
print "<TD COLSPAN=3>Last Invoices</TD>";
print "</TR>";

print "<TR STYLE='font-family:verdana;font-weight:bold;font-size:14;BORDER: solid 1px black'>";
print "<TD>Date</TD>";
print "<TD>Invoice No</TD>";
print "<TD>Amount</TD>";
print "<TD></TD>";
print "</TR>";
$invoices = mysql_rows("SELECT * FROM webedi_messages WHERE FK_tp_profile = $userid AND FK_client_profile = 489 ORDER BY PK_id DESC LIMIT 5");
if ($invoices) {
    #print "<PRE>";
    #print_r($invoices);
    #print "</PRE>";
    foreach ($invoices as $invoice) {
        $tpl_vars["date"] = $invoice["date"];
        $tpl_vars["invoice_no"] = $invoice["edi_trace_num"];
        $tpl_vars["msg_id"] = $invoice["PK_id"];
        $total_amount = mysql_rows("SELECT total_amount FROM form_810_summary WHERE FK_header = {$invoice["FK_form"]}");
        $total_amount = number_format($total_amount, 2);

        print "<TR STYLE='font-family:verdana;font-weight:none;font-size:12'>";
        print "<TD>{$invoice["inserted_datetime"]}</TD>";
        print "<TD>{$invoice["PK_id"]}</TD>";
        print "<TD>{$total_amount}</TD>";
        print "<TD><A HREF='view_invoice.php?i={$invoice["FK_form"]}' TARGET='_new'>view</A></TD>";
        print "</TR>";
    }
} else
    print "<TD>No invoices found</TD>";

print "</TABLE>";


#PRINT DETAIL:
print $out;
