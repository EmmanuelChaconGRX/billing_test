<?php

error_reporting(E_ERRORS);

include "./includes/session.php";
include "./includes/db.php";
include "./common2.php";

if ($_SESSION["auth"] != "OK") {
    header("location: .");
    die();
}

$mytp = 489; #This is DTS's user on WebEDI.
### Start - Calculate month and year to query based on last month and year. (The calculation on PHP was too compliated and i was in a hurry).
##$date_row = mysql_row("SELECT YEAR(now() - INTERVAL 1 MONTH) as year, MONTH(now() - INTERVAL 1 MONTH) as month, DAYOFMONTH(now() - INTERVAL 1 MONTH) as day, DAY(now()) as today;");
$date_row = mysql_row("SELECT YEAR(now() - INTERVAL 0 MONTH) as year, MONTH(now() - INTERVAL 0 MONTH) as month, DAYOFMONTH(now() - INTERVAL 0 MONTH) as day, DAY(now()) as today;");
if (!$date_row) {
    die("Error calculating month/year");
}

$year = $date_row["year"];
$month = $date_row["month"];
$day = $date_row["day"];
$today = $date_row["today"];

if ($today > 15) {
    die("Action not allowed... Contact the system administrator.");  // This process should only be ran during the first days of the month.
}
### End - Calculate month and year
### Let's try to combine invoices together into a single one:::
$hubs = $_mysql->get_rows("SELECT DISTINCT(FK_client_profile) FROM billing_hubs");

foreach ($hubs as $hub) {
    process_hub($hub["FK_client_profile"]);
}

die(".");

function process_hub($hub) {
    global $_mysql, $month, $year;
    global $mytp;

    print "### <b>Processing hub: $hub<br><br></b>";
    $tp_arr = $_mysql->get_rows("SELECT * FROM billing_hubs WHERE FK_client_profile = $hub");

    ### Here is where the fun begins... 
    foreach ($tp_arr as $tp) {
        #if($hub == 489)
        #$mytp = 4890; ###Temporary setting

        print "##### Processing invoices for tp: {$tp["FK_sub_account"]}<br>";

        ### Pull invoices from table.
        $sql = "SELECT * FROM webedi_messages WHERE MONTH(inserted_datetime)=$month AND YEAR(inserted_datetime)=$year AND FK_client_profile = $mytp AND FK_tp_profile = {$tp["FK_sub_account"]}";
        $inv_arr = $_mysql->get_rows($sql);
        foreach ($inv_arr as $inv) {
            print "####### Found invoice {$inv["PK_id"]} -- Processing<br>";

            ### Ok lets'try collecting the line items information in an array:

            $this_details = $_mysql->get_rows("SELECT * FROM form_810_detail WHERE FK_header = {$inv["FK_form"]}");
            foreach ($this_details as $detail) {
                ### Descriptions and parts as well:
                $detail["subaccount"] = $tp["FK_sub_account"];
                $detail["description"] = $_mysql->get_rows("SELECT * FROM form_810_description WHERE FK_detail = {$detail["PK_id"]}");
                $detail["parts"] = $_mysql->get_rows("SELECT * FROM form_810_parts WHERE FK_detail = {$detail["PK_id"]}");

                print "<PRE>";
                print_r($detail);

                $details[] = $detail;
            }

            ##Mark the invoice so that it is not processed again:
            $sql = "UPDATE webedi_messages SET FK_client_profile = FK_client_profile * 100 WHERE MONTH(inserted_datetime)=$month AND YEAR(inserted_datetime)=$year AND FK_client_profile = $mytp AND FK_tp_profile = {$tp["FK_sub_account"]} AND PK_id = {$inv["PK_id"]}";
            print "$sql<BR>";
            $_mysql->query($sql);
        }

        ### Process the data we gathered above:
        $bill["FK_tp"] = $hub;
        print "<hr>";
    }

    ### All data has been gathered above... now will create a consolidated invoice:
    create_consolidated_invoice($hub, $details);
}

function create_consolidated_invoice($hub, $details) {
    global $_mysql;
    global $mytp;
    global $year, $month;

    $bt_name = $_mysql->get_row("SELECT * FROM webedi_tp WHERE userid=$hub");
    $se_name = $_mysql->get_row("SELECT * FROM webedi_tp WHERE userid=$mytp");

    $head_invoice_date = date("Y-m-d"); #,strtotime($head_invoice_date));

    $_inv_head = array(
        'PO_num' => "",
        'order_date' => "",
        'release_number' => "",
        'ref_qual' => "",
        'ref_num' => "",
        'ref2_qual' => "",
        'ref2_num' => "",
        'terms_date_code' => "",
        'shipment_method_of_payment' => "",
        'location_qual' => "",
        'req_delivery_date' => "",
        'terms_disc' => "",
        'terms_disc_days' => "",
        'terms_net_days' => "30",
        'terms_other' => "",
        'terms_disc_percent' => "",
        'trans_carr' => "",
        'carrier_name' => "",
        'trans_mode_detail' => "",
        'trans_detail_name' => "",
        'invoice_number' => "0",
        'invoice_date' => "$head_invoice_date",
        'ship_ref_qual' => "",
        'ship_ref_no' => "",
        'packs_shipped' => "",
        'ship_weight' => "",
        'ship_date' => "",
        'invoice_type' => ""
    );
    $_inv_bt_name = array(
        'entity_code' => "BT",
        'co_name' => $bt_name["company_name"],
        'id_code' => "",
        'id_code_qual' => "92",
        'address1' => $bt_name["address1"],
        'address2' => $bt_name["address2"],
        'city' => $bt_name["address_city"],
        'state' => $bt_name["address_state"],
        'zip' => $bt_name["address_postalcode"],
        'country' => $bt_name["address_country"]
    );
    $_inv_se_name = array(
        'entity_code' => "SE",
        'co_name' => $se_name["company_name"],
        'id_code' => "",
        'id_code_qual' => "92",
        'address1' => $se_name["address1"],
        'address2' => $se_name["address2"],
        'city' => $se_name["address_city"],
        'state' => $se_name["address_state"],
        'zip' => $se_name["address_postalcode"],
        'country' => $se_name["address_country"]
    );

    #ALL TIERS IN ONE LINE ITEM BEGIN
    $this_cost = 0;
    $this_description = "";

    foreach ($details as $detail) {
        $unit_price = $detail["unit_price"];
        $quantity = $detail["quantity"];
        $description = $detail["description"];
        $sub_total = $unit_price * $quantity;

        if ($sub_total > 0) {
            $total_amount += $sub_total;
            $subaccount_name = $_mysql->get_value("SELECT company_name FROM webedi_tp WHERE userid={$detail["subaccount"]}");
            $line_count++;
            $_inv_lines[] = array(
                'line_num' => "$line_count",
                'quantity' => 1,
                'unit_of_measure' => "EA",
                'unit_price' => $sub_total,
                'upc_code' => "111222333444",
                'pn2' => "",
                'description' => "Traffic for $subaccount_name");
        }
    }
    #ALL TIERS IN ONE LINE ITEM END

    $invoice["partner"] = array("FK_tp" => $hub);
    $invoice["header"] = $_inv_head;
    $invoice["name"][] = $_inv_bt_name;
    $invoice["name"][] = $_inv_se_name;
    $invoice["line"] = $_inv_lines;
    $invoice["total_amount"] = $total_amount;

    if ($total_amount > 0)
        print save_invoice($invoice);

    if (++$this_count > 10)
        die("END");
}

function save_invoice($invoice) {
    global $_mysql;
    global $fk_client;
    global $mytp;

    foreach ($invoice as $obj_type => $obj) {
        if ($obj_type == "header") {
            $invoice_number = $obj["invoice_number"];
            $fk_header = $_mysql->insert_from_array("form_810_header", $obj);
        }
        if ($obj_type == "name") {
            foreach ($obj as $obj1) {
                $obj1["FK_header"] = $fk_header;
                $nm_fk = $_mysql->insert_from_array("form_810_name", $obj1);
            }
        }
        if ($obj_type == "line") {
            foreach ($obj as $_line) {
                $_line["FK_header"] = $fk_header;
                $_line["invoice_date"] = $now = date("Y-m-d H:i:s");

                $desc = $_line["description"];
                $qbitem_no = $_line["qbitem_no"];
                $_line["description"] = "";  # Removes the data from the array so that the function below doesn't try to insert it.

                $li_fk = $_mysql->insert_from_array("form_810_detail", $_line);
                $lid_fk = $_mysql->insert_from_array("form_810_description", array("FK_detail" => "$li_fk", "segment" => "PID", "description" => "$desc"));

                #PARTS:
                #$p_row = $_mysql->get_row("SELECT * FROM webedi_integration_parts WHERE FK_client='$fk_client' AND FK_tp='$FK_tp' AND code='$desc'");
                $p_row = $_mysql->get_row("SELECT * FROM webedi_catalog WHERE FK_tp='$fk_client' AND integration_identifier='$qbitem_no'");
                if ($p_row) {
                    # Inserts part numbers
                    if ($p_row["upc_code"] != "") {
                        $_mysql->update_from_array("form_810_detail", array("PK_id" => "$li_fk"), array("upc_code" => $p_row["upc_code"]));
                        $_mysql->insert_from_array("form_810_parts", array("FK_detail" => "$li_fk", "segment" => "PO1", "qualifier" => "UP", "id" => "{$p_row["upc_code"]}"));
                    }

                    if ($_line["pn1"] <> "") {
                        $p = split(":", $_line["pn1"]);
                        $_mysql->insert_from_array("form_810_parts", array("FK_detail" => "$li_fk", "segment" => "PO1", "qualifier" => $p[0], "id" => $p[1]));
                    }
                    if ($_line["pn2"] <> "") {
                        $p = split(":", $_line["pn2"]);
                        $_mysql->insert_from_array("form_810_parts", array("FK_detail" => "$li_fk", "segment" => "PO1", "qualifier" => $p[0], "id" => $p[1]));
                    }
                }
            }
        }
    }

    $now = date("Y-m-d H:i:s");

    $message_fk = $_mysql->insert_from_array("form_810_summary", array("FK_header" => "$fk_header", "total_amount" => $invoice["total_amount"]));
    $message_fk = $_mysql->insert_from_array("webedi_messages", array("msg_type" => "810", "edi_trace_num" => "$invoice_number", "FK_form" => "$fk_header", "message_dir" => "out", "FK_client_profile" => $mytp, "FK_tp_profile" => $invoice["partner"]["FK_tp"], "inserted_datetime" => "$now"));

    return "Invoice $invoice_number has been imported successfully as MsgID $fk_header.<BR>";
}

?>
