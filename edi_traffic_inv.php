<?php

error_reporting(E_ERROR);
ini_set('error_log','/tmp/php-invoices.log');
ini_set('display_errors',1);
include "./includes/session.php";
include "./includes/db.php";
include "./common2.php";

if ($_SESSION["auth"] != "OK") {
    header("location: .");
    die();
}

$mytp = 489; #This is DTS's user on WebEDI.

### Create only invoices for 1018:
#$bills = $_mysql->get_rows("SELECT tp.company_name, trf.* FROM webedi_tp_monthly_traffic trf INNER JOIN webedi_tp tp ON FK_tp = userid WHERE month=$month AND year=$year AND FK_tp!=$mytp AND FK_tp IN (1018) ORDER BY company_name");
#foreach($bills as $bill)
#{
#	create_invoice($bill);
#	sleep(1);
#}
#die("Done creating invoices for 1018");

class WebEDI20Invoicer {
    protected $_mysql;
    protected $mytp;
    protected $year;
    protected $month;
    public function __construct(MySQL $db, $invoiceTp) {
        // Start - Calculate month and year to query based on last month and year. (The calculation on PHP was too compliated and i was in a hurry).
        $date_row = $db->get_row("SELECT YEAR(now() - INTERVAL 1 MONTH) as year, MONTH(now() - INTERVAL 1 MONTH) as month, DAYOFMONTH(now() - INTERVAL 1 MONTH) as day, DAY(now()) as today;");
        if (!$date_row) {
            die("Error calculating month/year");
        }

        $year = $date_row["year"];
        $month = $date_row["month"];
        //$day = $date_row["day"];
        $today = $date_row["today"];
        // End - Calculate month and year

        if ($today > 8) {
            //die("Action not allowed... Contact the system administrator."); // This process should only be ran during the first days of the month.
        }
        $this->_mysql=$db;
        $this->mytp=$invoiceTp;
        $this->month=$month;
        $this->year=$year;
        $this->today=$today;
    }
    
    /*
      #Create ONE invoice for Offline (07/11)
      $bills = $_mysql->get_rows("SELECT tp.company_name, trf.* FROM webedi_tp_monthly_traffic trf INNER JOIN webedi_tp tp ON FK_tp = userid WHERE month=$month AND year=$year AND FK_tp!=$mytp AND FK_tp IN (709) ORDER BY company_name");
      foreach($bills as $bill)
      {
      create_invoice($bill);
      sleep(1);
      }
      die("ONE");
     */
    

    function doAll() {
        // First round, CC invoices 
        $bills = $this->_mysql->get_rows("SELECT tp.name, trf.* FROM webedi.webedi_tp_monthly_traffic trf INNER JOIN webedi30.Partners tp ON FK_tp = tp.PK_id WHERE month={$this->month} AND year={$this->year} AND FK_tp!={$this->mytp} AND FK_tp IN (SELECT FK_client FROM webedi.webedi_tp_params WHERE param LIKE 'payment_method' AND value!='CC') ORDER BY name");
        foreach ($bills as $bill) {
            $this->create_invoice($bill);
            // sleep(1);
        }
	
        // Second round, NON CC invoices	
        $bills = $this->_mysql->get_rows("SELECT tp.name, trf.* FROM webedi.webedi_tp_monthly_traffic trf INNER JOIN webedi30.Partners tp ON FK_tp = tp.PK_id WHERE month={$this->month} AND year={$this->year} AND FK_tp!={$this->mytp} AND FK_tp NOT IN (SELECT FK_client FROM webedi.webedi_tp_params WHERE param LIKE 'payment_method' AND value!='CC') ORDER BY name");
        foreach ($bills as $bill) {
            $this->create_invoice($bill);
            // sleep(1);
        }
    }

    function doByChannel() {
        $array = array(
                2634,
                2609,
                749,
                769,
                1658,
                871,
                44,
                464,
                2693,
                1794,
                2388,
                695,
                1562,
                1485,
                770,
                992,
                1017,
                1791,
                1445,
                590,
                2096,
                3337
        );
        $list = join(',',$array);
        $bills = $this->_mysql->get_rows("SELECT tp.name, trf.* FROM webedi.webedi_tp_monthly_traffic trf INNER JOIN webedi30.Partners tp ON FK_tp = tp.PK_id WHERE month={$this->month} AND year={$this->year} AND FK_tp!={$this->mytp} AND FK_tp IN ($list) ORDER BY name");
        foreach ($bills as $bill) {
            $this->create_invoice($bill);
            // sleep(1);
        }
    }

    function create_invoice($bill) {
        /*
          if($bill["company_name"]=="MMSC (*new pricing)")
          {
          print "<li>{$bill["company_name"]}<br>";
          } else
          {
          return false;
          } */

        // $bt_name = $_mysql->get_row("SELECT * FROM webedi_tp WHERE userid={$bill["FK_tp"]}");
        // $se_name = $_mysql->get_row("SELECT * FROM webedi_tp WHERE userid=$mytp");

        $bt_name = $this->_mysql->get_row("SELECT * FROM webedi30.Partners WHERE PK_id={$bill["FK_tp"]}");
        $se_name = $this->_mysql->get_row("SELECT * FROM webedi30.Partners WHERE PK_id={$this->mytp}");

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
            'co_name' => $bt_name["name"],
            'id_code' => "",
            'id_code_qual' => "92",
            'address1' => $bt_name["address1"],
            'address2' => $bt_name["address2"],
            'city' => $bt_name["city"],
            'state' => $bt_name["state"],
            'zip' => $bt_name["zip"],
            'country' => $bt_name["country"]
        );
        $_inv_se_name = array(
            'entity_code' => "SE",
            'co_name' => $se_name["name"],
            'id_code' => "",
            'id_code_qual' => "92",
            'address1' => $se_name["address1"],
            'address2' => $se_name["address2"],
            'city' => $se_name["city"],
            'state' => $se_name["state"],
            'zip' => $se_name["zip"],
            'country' => $se_name["country"]
        );

        // ALL TIERS IN ONE LINE ITEM BEGIN
        $this_cost = 0;
        $this_description = "";
        //$traffic = $bill["traffic"];

        // This query and code replicates functionality from edi_traffic_view_tp_1.php
        $start_date = date('Ym01', strtotime("{$this->year}-{$this->month}-1"));
        $end_date = date('Ym01', strtotime("{$this->year}-{$this->month}-1 +1 month"));

        $ds_traffic = mysql_value("SELECT 
                SUM(qty1)
            FROM
                (SELECT 
                    wi.*,
                        MAX(IF(wp.prop_name = 'sender', wp.prop_value, NULL)) `sender`,
                        MAX(IF(wp.prop_name = 'tpname', wp.prop_value, NULL)) `tpname`,
                        MAX(IF(wp.prop_name = 'control', wp.prop_value, NULL)) `control`,
                        MAX(IF(wp.prop_name = 'ContentSize', wp.prop_value, NULL)) `size`,
                        MAX(IF(wp.prop_name = 'type', wp.prop_value, NULL)) `doctype`,
                        MAX(IF(wp.prop_name = 'Duplicate', wp.prop_value, 0)) `dup`,
                        MAX(IF(wp.prop_name = 'Dropship', wp.prop_value, 0)) `dropship`
                FROM
                    billing.billing_workitems wi
                INNER JOIN billing.billing_workitem_props wp ON wi.id = wp.workitem_id
                WHERE
                    wi.customer_reference = '{$bill["FK_tp"]}'
                        AND wi.time_stamp BETWEEN '$start_date' AND '$end_date'
                GROUP BY wi.id
                HAVING `dup` = 0 AND `dropship` = 1) t;");

        $start_date1 =date("Ymd",strtotime($end_date."- 1 days"));//DS-4816
        $billingC=0;
        if( $this->today=='1'){
            $billingC = mysql_value("SELECT SUM(g.qty1)
                FROM (SELECT COUNT(*),t.control,t.server,t.qty1 as qty1 
                        FROM (SELECT wi.*,
                                    MAX(IF(wp.prop_name = 'sender', wp.prop_value, NULL)) `sender`,
                                    MAX(IF(wp.prop_name = 'tpname', wp.prop_value, NULL)) `tpname`,
                                    MAX(IF(wp.prop_name = 'control', wp.prop_value, NULL)) `control`,
                                    MAX(IF(wp.prop_name = 'ContentSize', wp.prop_value, NULL)) `size`,
                                    MAX(IF(wp.prop_name = 'type', wp.prop_value, NULL)) `doctype`,
                                    MAX(IF(wp.prop_name = 'Duplicate', wp.prop_value, 0)) `dup`
                              FROM  billing.billing_workitems wi
                                INNER JOIN billing.billing_workitem_props wp ON wi.id = wp.workitem_id
                              WHERE wi.customer_reference = '{$bill["FK_tp"]}' AND wi.time_stamp BETWEEN '$start_date1' AND '$end_date'
                              GROUP BY wi.id
                              HAVING `dup` = 0 ORDER BY wi.server DESC
                              ) as t
                        GROUP BY t.control,t.doctype,t.size
                        HAVING COUNT(*) > 0 
                      ) as g;");
            $bill["traffic"] = $bill["traffic"] + $billingC;
        }

        $non_ds_traffic = $bill["traffic"] - $ds_traffic;
        $lines = billing_array($bill["billing_schema"], $non_ds_traffic, $ds_traffic);
	
        foreach ($lines["lines"] as $line) {
            $this_cost += $line["qty"] * $line["cost"];
            $total_qty += $line["qty"];
            $this_description .= "{$line["description"]}, {$line["qty"] } @ {$line["cost"]}\r";
            #print "Total amount:::: $total_amount ($this_cost += {$line["qty"]} * {$line["cost"]}<BR>";
        }

        if ($this_cost > 0) {
            $this_description .= "Total number of messages: {$bill["traffic"]}\r";

            $total_amount += $this_cost;

            $line_count++;
            $_inv_lines[] = array(
                'line_num' => "$line_count",
                'quantity' => 1,
                'unit_of_measure' => "EA",
                'unit_price' => $this_cost,
                'upc_code' => "111222333444",
                'pn2' => "",
                'description' => trim($this_description));
        }
        // ALL TIERS IN ONE LINE ITEM END

        $charges = $this->_mysql->get_rows("SELECT * FROM webedi_tp_charges WHERE FK_tp = {$bill["FK_tp"]} AND status = 'pending'");
        foreach ($charges as $charge) {
            $line_count++;
            $_inv_lines[] = array(
                'line_num' => "$line_count",
                'quantity' => 1,
                'unit_of_measure' => "EA",
                'unit_price' => $charge["amount"],
                'upc_code' => "111222333445",
                'pn2' => "",
                'description' => $charge["description"]);
            $total_amount += $charge["amount"];
            #print "Total amount:::: $total_amount ({$charge["amount"]})<BR>";

            $this->_mysql->query("UPDATE webedi_tp_charges SET billed_on_month = {$this->month}, billed_on_year={$this->year}, status='invoiced' WHERE PK_id = {$charge["PK_id"]} AND recurring = 0");
            if ($this->_mysql->error)
                die($this->_mysql->error);
        }

        $invoice["partner"] = array("FK_tp" => $bill["FK_tp"]);
        $invoice["header"] = $_inv_head;
        $invoice["name"][] = $_inv_bt_name;
        $invoice["name"][] = $_inv_se_name;
        $invoice["line"] = $_inv_lines;
        $invoice["total_amount"] = $total_amount;

        if ($total_amount > 0) {
            print $this->save_invoice($invoice);
	}
    }

    function save_invoice($invoice) {
        global $fk_client;

        foreach ($invoice as $obj_type => $obj) {
            if ($obj_type == "header") {
                $invoice_number = $obj["invoice_number"];
                $fk_header = $this->_mysql->insert_from_array("form_810_header", $obj);
            }
            if ($obj_type == "name") {
                foreach ($obj as $obj1) {
                    $obj1["FK_header"] = $fk_header;
                    $nm_fk = $this->_mysql->insert_from_array("form_810_name", $obj1);
                }
            }
            if ($obj_type == "line") {
                foreach ($obj as $_line) {
                    $_line["FK_header"] = $fk_header;
                    $_line["invoice_date"] = $now = date("Y-m-d H:i:s");

                    $desc = $_line["description"];
                    $qbitem_no = $_line["qbitem_no"];
                    $_line["description"] = "";  # Removes the data from the array so that the function below doesn't try to insert it.

                    $li_fk = $this->_mysql->insert_from_array("form_810_detail", $_line);
                    $lid_fk = $this->_mysql->insert_from_array("form_810_description", array("FK_detail" => "$li_fk", "segment" => "PID", "description" => "$desc"));

                    #PARTS:
                    #$p_row = $_mysql->get_row("SELECT * FROM webedi_integration_parts WHERE FK_client='$fk_client' AND FK_tp='$FK_tp' AND code='$desc'");
                    $p_row = $this->_mysql->get_row("SELECT * FROM webedi_catalog WHERE FK_tp='$fk_client' AND integration_identifier='$qbitem_no'");

                    if ($p_row) {
                        # Inserts part numbers
                        if ($p_row["upc_code"] != "") {
                            $this->_mysql->update_from_array("form_810_detail", array("PK_id" => "$li_fk"), array("upc_code" => $p_row["upc_code"]));
                            $this->_mysql->insert_from_array("form_810_parts", array("FK_detail" => "$li_fk", "segment" => "PO1", "qualifier" => "UP", "id" => "{$p_row["upc_code"]}"));
                        }

                        if ($_line["pn1"] <> "") {
                            $p = split(":", $_line["pn1"]);
                            $this->_mysql->insert_from_array("form_810_parts", array("FK_detail" => "$li_fk", "segment" => "PO1", "qualifier" => $p[0], "id" => $p[1]));
                        }
                        if ($_line["pn2"] <> "") {
                            $p = split(":", $_line["pn2"]);
                            $this->_mysql->insert_from_array("form_810_parts", array("FK_detail" => "$li_fk", "segment" => "PO1", "qualifier" => $p[0], "id" => $p[1]));
                        }
                    }
                }
            }
        }

        $now = date("Y-m-d H:i:s");

        $message_fk = $this->_mysql->insert_from_array("form_810_summary", array("FK_header" => "$fk_header", "total_amount" => $invoice["total_amount"]));
        $message_fk = $this->_mysql->insert_from_array("webedi_messages", array("msg_type" => "810", "edi_trace_num" => "$invoice_number", "FK_form" => "$fk_header", "message_dir" => "out", "FK_client_profile" => $this->mytp, "FK_tp_profile" => $invoice["partner"]["FK_tp"], "inserted_datetime" => "$now"));

        return "Invoice $invoice_number has been imported successfully as MsgID $fk_header.<BR>";
    }

}

$invoicer =new WebEDI20Invoicer($_mysql, $mytp);
$invoicer->doAll();
