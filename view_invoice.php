<?php

session_start();

if ($_SESSION["auth"] != "OK") {
    header("location: .");
    die();
}

include "../includes/functions/db.php";
include "../includes/vars.php";
include "includes/form_functions.php";
include "includes/url.php";
include "includes/db2.php";
include "common2.php";

$i = $_REQUEST["i"];

if ($i == ""){
    die("");
}

$_REQUEST["id"] = $i;

$invoice = mysql_row("SELECT * FROM webedi_messages WHERE PK_id = $i AND FK_client_profile = 489 ORDER BY PK_id DESC LIMIT 5");
if ($invoice) {
    $invoice_header = mysql_row("SELECT * FROM form_810_header WHERE PK_id = {$invoice["FK_form"]}");
    #PRINT LAST WEBEDI INVOICE DETAIL:
    print "<BR><BR>";
    print "<TABLE BORDER=1 CELLPADDING=4 CELLSPACING=0 WIDTH=600>";

    $customer_name = mysql_value("SELECT company_name FROM webedi_tp WHERE userid = {$invoice["FK_tp_profile"]}");

    print "<TR STYLE='font-family:verdana;font-weight:bold;font-size:14;BORDER: solid 1px black'>";
    print "<TD COLSPAN=3><B>$customer_name</TD>";
    print "</TR>";

    print "<TR STYLE='font-family:verdana;font-weight:bold;font-size:14;BORDER: solid 1px black'>";
    print "<TD>Invoice Date</TD>";
    print "<TD>Invoice No</TD>";
    print "<TD>Total Amount</TD>";
    print "</TR>";

    $tpl_vars["invoice_no"] = $invoice["edi_trace_num"];
    $tpl_vars["msg_id"] = $invoice["PK_id"];
    $total_amount = mysql_value("SELECT total_amount FROM form_810_summary WHERE FK_header = {$invoice["FK_form"]}");
    $total_amount = number_format($total_amount, 2);

    $inv_date = date("Y-m-d", strtotime($invoice["inserted_datetime"]));
    print "<TR STYLE='font-family:verdana;font-weight:none;font-size:12'>";
    print "<TD>{$inv_date}</TD>";
    print "<TD>{$invoice_header["invoice_number"]}</TD>";
    print "<TD >\${$total_amount}</TD>";
    print "</TR>";
}
print "</TABLE>";

print "<BR>";
print "<TABLE BORDER=1 CELLPADDING=4 CELLSPACING=0 WIDTH=800>";


print "<TR STYLE='font-family:verdana;font-weight:bold;font-size:14;BORDER: solid 1px black'>";
print "<TD>Qty</TD>";
print "<TD>Description</TD>";
print "<TD>unit_price</TD>";
print "<TD>SubTotal</TD>";
print "</TR>";

$invoice_lines = mysql_rows("SELECT * FROM form_810_detail WHERE FK_header = {$invoice["FK_form"]}");
if ($invoice_lines) {
    foreach ($invoice_lines as $invoice_line) {
        $str_description = "";
        $descriptions = mysql_rows("SELECT * FROM form_810_description WHERE FK_detail = {$invoice_line["PK_id"]}");
        foreach ($descriptions as $d)
            $str_description .= $d["description"];

        $invoice_line["description"] = $str_description;

        $subtotal = $invoice_line["quantity"] * $invoice_line["unit_price"];
        $subtotal = number_format($subtotal, 2);

        $unit_price = number_format($invoice_line["unit_price"], 2);

        print "<TR STYLE='font-family:verdana;font-weight:none;font-size:12'>";
        print "<TD>{$invoice_line["quantity"]}</TD>";
        print "<TD>{$invoice_line["description"]}</TD>";
        print "<TD ALIGN='RIGHT'>\${$unit_price}</TD>";
        print "<TD ALIGN='RIGHT'>\${$subtotal}</TD>";
        print "</TR>";
    }
}
print "</TABLE>";

#print invoice detail lines:


print "</TABLE>";


#PRINT DETAIL:
print $out;
