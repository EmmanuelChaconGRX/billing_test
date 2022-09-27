<?php

# WebService that returns previous invoices for a customer
#
#

if (!function_exists("json_encode")) {
    dl("json.so");
}

error_reporting(E_ERROR);

include __DIR__ ."/../includes/db.php";

$f = $_REQUEST["f"];
$t = $_REQUEST["t"];

#Check params
if (!$f || !$t) {
    $out["status"] = "ERR";
    $out["status_code"] = 5;
    $out["description"] = "Missing Arguments";
    print json_encode($out);
    die();
}

$fromdate = date("Y/m/d", strtotime($f));
$todate = date("Y/m/d", strtotime($t));
if (!$fromdate || !$todate) {
    $out["status"] = "ERR";
    $out["status_code"] = 6;
    $out["description"] = "Invalid Arguments";
    print json_encode($out);
    die();
}

$sql = "SELECT PK_id,FK_client_profile,FK_tp_profile,FK_form,msg_type,edi_trace_num FROM webedi_messages WHERE FK_client_profile = 489 AND inserted_datetime>='$fromdate' AND inserted_datetime<'$todate' ";

foreach (mysql_rows($sql) as $message) {
    unset($inv);
    $partner = mysql_row("SELECT userid,webtpid,company_name FROM webedi_tp WHERE userid = {$message["FK_tp_profile"]}");
    $header = mysql_row("SELECT * FROM form_810_header WHERE PK_id = {$message["FK_form"]}");
    $names = mysql_rows("SELECT * FROM form_810_name WHERE FK_header = {$message["FK_form"]}");
    $lines = mysql_rows("SELECT d.*,c.integration_identifier FROM form_810_detail d INNER JOIN webedi_catalog c ON d.upc_code = c.upc_code AND c.FK_tp = 489 WHERE FK_header = {$message["FK_form"]}");
    $summary = mysql_row("SELECT total_lines,total_amount FROM form_810_summary WHERE FK_header = {$message["FK_form"]}");

    $inv["msg_header"] = reduceArray($message);
    $inv["partner"] = reduceArray($partner);
    $inv["inv_header"] = reduceArray($header);

    foreach ($names as $name) {
        $inv["names"][$name["entity_type"]] = reduceArray($name);
    }

    foreach ($lines as $line) {
        $inv["details"][$line["line_num"]] = reduceArray($line);
        foreach (mysql_rows("SELECT * FROM form_810_parts WHERE FK_detail = {$line["PK_id"]}") as $part) {
            $inv["details"][$line["line_num"]]["parts"][] = reduceArray($part);
        }
        foreach (mysql_rows("SELECT * FROM form_810_description WHERE FK_detail = {$line["PK_id"]}") as $desc) {
            $inv["details"][$line["line_num"]]["description"][] = trim($desc["description"]);
        }
    }

    $inv["summary"] = reduceArray($summary);

    $data[] = $inv;
}

#Check valid dates:

$out["status"] = "OK";
$out["status_code"] = 0;
$out["description"] = "$fromdate $todate";
$out["data"] = $data;

print json_encode($out);

function reduceArray($arr) {
    foreach ($arr as $id => $val) {
        if (is_numeric($id))
            unset($arr[$id]);
    }
    return $arr;
}

