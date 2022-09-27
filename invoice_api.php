<?php
if(!in_array($_SERVER['REMOTE_ADDR'],array('172.18.0.26','127.0.0.1') )) {
//    die('Unauthorized');
}
include "../includes/functions/db.php";
include "../includes/vars.php";
include "includes/form_functions.php";
include "includes/url.php";
include "includes/db2.php";
include "common2.php";
error_reporting(E_ERROR & ~E_NOTICE);
ini_set('display_errors', '1');
ini_set('log_errors', 'On');
ini_set('error_log', '/tmp/php-billing-error.log');
ini_set('memory_limit', '4G');
set_time_limit(7200);

error_log(__FILE__ . " :: " . __LINE__ . " :: GOING");
$userid = intval(request("form_userid", ""));
$out = array();
// TODO: Strong candidate for migration to  2.0
$invoices = mysql_rows("SELECT * FROM webedi_messages WHERE FK_tp_profile = $userid AND FK_client_profile = 489 ORDER BY PK_id DESC LIMIT 24");

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
        $out[] = array(
            'date' => $invoice["inserted_datetime"],
            'invoice_no' => "{$invoice["PK_id"]}-{$invoice["edi_trace_num"]}",
            'msg_id' => $invoice["PK_id"],
            'amount' => $total_amount,
            'details' => getInvoiceDetail($invoice["PK_id"])
        );
    }
}
header('Content-Type: application/json');
echo json_encode($out);

function getInvoiceDetail($i) {
    $out = array();
    $invoice = mysql_row("SELECT * FROM webedi_messages WHERE PK_id = $i AND FK_client_profile = 489 ORDER BY PK_id DESC LIMIT 5");
    if ($invoice) {
        $invoice_header = mysql_row("SELECT * FROM form_810_header WHERE PK_id = {$invoice["FK_form"]}");
        $customer_name = mysql_value("SELECT company_name FROM webedi_tp WHERE userid = {$invoice["FK_tp_profile"]}");
        $out = array();
        $total_amount = mysql_value("SELECT total_amount FROM form_810_summary WHERE FK_header = {$invoice["FK_form"]}");
        $total_amount = number_format($total_amount, 2);
        $inv_date = date("Y-m-d", strtotime($invoice["inserted_datetime"]));
        $out['customer_name'] = $customer_name;
        $out['invoice_date'] = $inv_date;
        $out['invoice_no'] = "{$invoice["PK_id"]}-{$invoice["edi_trace_num"]}";
        $out['total_amount'] = $total_amount;
    }

    $out['lines'] = array();


    $invoice_lines = mysql_rows("SELECT * FROM form_810_detail WHERE FK_header = {$invoice["FK_form"]}");
    if ($invoice_lines) {
        foreach ($invoice_lines as $invoice_line) {
            $line = array();

            $str_description = "";
            $descriptions = mysql_rows("SELECT * FROM form_810_description WHERE FK_detail = {$invoice_line["PK_id"]}");
            foreach ($descriptions as $d) {
		$str_description .= implode("\n",explode("\r",trim($d["description"])))."\n";
            }

            $invoice_line["description"] = $str_description;

            $subtotal = $invoice_line["quantity"] * $invoice_line["unit_price"];
            $subtotal = number_format($subtotal, 2);

            $unit_price = number_format($invoice_line["unit_price"], 2);
            $line['qty'] = $invoice_line["quantity"];
            $line['description'] = $invoice_line["description"];
            $line['unit_price'] = "\$$unit_price";
            $line['subtotal'] = "\$$subtotal";
            $out['lines'][] = $line;
        }
    }


    #PRINT DETAIL:
    return $out;
}

