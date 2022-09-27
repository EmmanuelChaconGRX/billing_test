<?php

include(__DIR__ . '/../env.php');
#print_r ($_SERVER);die();
#Prints debugging information only for the IPs below:

function print_rr($x) {
    $ip = $_SERVER["REMOTE_ADDR"];
    if (($ip == "66.253.54.245") || ($ip == "192.168.0.72")) {
        print "<PRE>\n";
        print_r($x);
        print "</PRE>\n";
    }
}

#Prints only for the IPs below:

function printt($x) {
    $ip = $_SERVER["REMOTE_ADDR"];
    if (($ip == "66.253.54.245") || ($ip == "192.168.0.72")) {
        print $x;
    }
}

function billing_array($plan_id, $doc_qty,$dropship_qty=0) {
    global $f;
    $flat_tier = 0;
    $_count = 0;
    $remanente = $doc_qty;
    $plan_ = mysql_row("SELECT * FROM webedi_tp_billing_schemas WHERE PK_id=$plan_id");

    $plan_schedule_arr = explode(";", $plan_["tiers"]);

    if ($plan_["type"] == "acummulative") {
        $acummulative = 1;
    } else {
        $acummulative = 0;
    }
    if ($plan_["type"] == "flat_tier") {
        $flat_tier = 1;
    }
    $last_qty = 0;

    #$lines[]=array("Description","Qty","Cost","SubTotal");

    if ($acummulative > 0) {
        $lines[] = array("description" => "Basic Account Fee", "qty" => 1, "cost" => $plan_["base_price"], "subtotal" => $plan_["base_price"]);
        $total = $plan_["base_price"];
    }
    if($plan_["dropship"]=="Y") {
            $lines[] = array("description" => "Dropship traffic","qty" =>$dropship_qty,"cost" => $plan_["dropship_base_price"],"subtotal" => n($plan_["dropship_base_price"]*$dropship_qty));
    }else{
        $doc_qty += $dropship_qty;
    }
    #print_rr($plan_schedule_arr);
    foreach ($plan_schedule_arr as $id => $schedule) {
        list($qty, $cost) = explode(":", $schedule);
        ### IF qty == *... it represents the total number of documents (for the final tier on a schedule).
        $_count++;

        if ($qty == "*" || $_count == count($plan_schedule_arr))
            $qty = $doc_qty;
        if ($acummulative) {
            if ($doc_qty > $last_qty) {
                if ($doc_qty > $qty)
                    $this_qty = $qty - $last_qty;
                else
                    $this_qty = $doc_qty - $last_qty;

                $subtotal = $this_qty * $cost;
                $total += $subtotal;
                $lines[] = array("description" => $last_qty + 1 . " - $qty messages", "qty" => $this_qty, "cost" => $cost, "subtotal" => $subtotal);
                $last_qty = $qty;
            }
        } elseif ($flat_tier && !empty($qty)) {
            list($min_qty, $max_qty) = explode('-', $qty);
            if ($doc_qty >= $min_qty && $doc_qty <= $max_qty) {
                $subtotal = $cost;
                $total = $cost;
                $lines[] = array("description" => " $min_qty -  $max_qty messages", "qty" => 1, "cost" => $subtotal, "subtotal" => $subtotal);
            }
        }
        #The calculation for non cummulative schedules will take place outside the for loop.
        #This portion is only to select the rate.
        else {
            if ($qty == "*" || $doc_qty <= $qty) {
                $non_acumm_cost = $cost;
                break;
            }
        }
    }
    #Calculation for non cummulative schedules.
    if (!$acummulative && !$flat_tier) {
        $subtotal = $doc_qty * $non_acumm_cost;
        if ($subtotal <= $plan_["base_price"]) {
            $lines[] = array("description" => "Basic Account Fee", "qty" => 1, "cost" => $plan_["base_price"], "subtotal" => $plan_["base_price"]);
            $total = $plan_["base_price"];
        } else {
            $total += $subtotal;
            $lines[] = array("description" => "1 - $doc_qty messages", "qty" => $doc_qty, "cost" => $non_acumm_cost, "subtotal" => $subtotal);
        }
    }
    if ($flat_tier) {
        if ($subtotal < $plan_["base_price"]) {
            $lines[] = array("description" => "Basic Account Fee", "qty" => n(1), "cost" => $plan_["base_price"], "subtotal" => $plan_["base_price"]);
            $total = $plan_["base_price"];
        }
    }
    
    //DS-4194 add Dropship quantity and total to summary
    if($plan_["dropship"]=="Y") {
        $doc_qty += $dropship_qty;
        $total += ($plan_["dropship_base_price"]*$dropship_qty);	
    }
    
    return(array("lines" => $lines, "totals_docs" => $doc_qty, "total_amount" => $total));
}

function calculate_billing($plan_id, $doc_qty, $print = true, $dropship_qty=0) {
    global $f;
    // DO NOT REFERENCE THIS VARIABLE DIRECTLY.
    global $__AllBillingPlans;
    if (!$__AllBillingPlans) {
        $plansResult = mysql_rows("SELECT * FROM webedi_tp_billing_schemas");
        foreach ($plansResult as $row) {
            $__AllBillingPlans[$row['PK_id']] = $row;
        }
    }
    $flat_tier = 0;
    $_count = 0;
    $remanente = $doc_qty;
    $plan_ = $__AllBillingPlans[$plan_id];
    #print_rr($plan_);

    $plan_schedule_arr = explode(";", $plan_["tiers"]);
    #print_rr("$plan_id::");

    if ($plan_["type"] == "acummulative") {
        $acummulative = 1;
    } else {
        $acummulative = 0;
    }
    if ($plan_["type"] == "flat_tier") {
        $flat_tier = 1;
    }
    $last_qty = 0;

    $lines[] = array("Description", "Qty", "Cost", "SubTotal");

    if ($acummulative > 0) {
        $lines[] = array("Basic Account Fee", n(1), n($plan_["base_price"]), n($plan_["base_price"]));
        $total = $plan_["base_price"];
    }
    
    if($plan_["dropship"]=="Y") {
        $lines[] = array("Dropship traffic",n($dropship_qty), n($plan_["dropship_base_price"]), n($plan_["dropship_base_price"]*$dropship_qty));
    }else{
        $doc_qty += $dropship_qty;
    }
    #print_rr($plan_schedule_arr);

    foreach ($plan_schedule_arr as $id => $schedule) {
        list($qty, $cost) = explode(":", $schedule);
        ### IF qty == *... it represents the total number of documents (for the final tier on a schedule).
        $_count++;

        if ($qty == "*" || $_count == count($plan_schedule_arr)) {
            $qty = $doc_qty;
        }

        #print "=========================<PRE>";
        #print var_export($plan_,true);
        #print "</PRE>";
        #print "<pre>";
        #print_r($plan_);
        #print "</pre>";
        #The calculation for acummulative schedules will take place here

        if ($acummulative) {
            if ($doc_qty > $last_qty) {
                #print_rr("$doc_qty | $last_qty | $qty | $cost | $_count<BR>");

                if ($doc_qty > $qty) {
                    $this_qty = $qty - $last_qty;
                } else {
                    $this_qty = $doc_qty - $last_qty;
                }
                $subtotal = $this_qty * $cost;
                $total += $subtotal;
                $lines[] = array(" " . $last_qty + 1 . " -  $qty messages", n($this_qty), n($cost), n($subtotal));
                $last_qty = $qty;
            }
        } elseif ($flat_tier && !empty($qty)) {
            list($min_qty, $max_qty) = explode('-', $qty);
            if (intval($doc_qty) >= intval($min_qty) && intval($doc_qty) <= intval($max_qty)) {
                $subtotal = $cost;
                $total = $cost;
                $lines[] = array(" $min_qty -  $max_qty messages", n($doc_qty), n($cost), n($subtotal));
            }
        }
        #The calculation for non cummulative schedules will take place outside the for loop.
        #This portion is only to select the rate.
        else {
            if ($qty == "*" || $doc_qty <= $qty) {
                $non_acumm_cost = $cost;
                break;
            }
            #print_rr("$doc_qty |  $qty | $cost | $non_acumm_cost");
        }
    }
    #Calculation for non cummulative schedules.
    if (!$acummulative && !$flat_tier) {
        $subtotal = $doc_qty * $non_acumm_cost;
        #print_rr("AAA $subtotal $doc_qty $non_acumm_cost");
        if ($subtotal <= $plan_["base_price"]) {
            $lines[] = array("Basic Account Fee", n(1), n($plan_["base_price"]), n($plan_["base_price"]));
            $total = $plan_["base_price"];
        } else {
            $total += $subtotal;
            $lines[] = array(" 1 -  $qty messages", n($doc_qty), n($non_acumm_cost), n($subtotal));
        }
    }
    if ($flat_tier) {
        if ($subtotal < $plan_["base_price"]) {
            $lines[] = array("Basic Account Fee", n(1), n($plan_["base_price"]), n($plan_["base_price"]));
            $total = $plan_["base_price"];
        }
    }

    //DS-4194 add Dropship quantity and total to summary
    if($plan_["dropship"]=="Y") {
        $doc_qty += $dropship_qty;
        $total += ($plan_["dropship_base_price"]*$dropship_qty);
    }

    
    if ($print) {
        $lines[] = array("<B>TOTAL</B>", n($doc_qty, 0), "", n($total));

        foreach ($lines as $line) {
            $tbl_rows[] = "$line[0]||{ALIGN=\"RIGHT\" WIDTH=\"100\"}$line[1]&nbsp;||{ALIGN=\"RIGHT\" WIDTH=\"100\"}$line[2]&nbsp;||{ALIGN=\"RIGHT\" WIDTH=\"100\"}$line[3]&nbsp;";
        }
        print "$f[20]Billing Summary:$f[0]<BR>\n";
        print array2tbl(join($tbl_rows, "-*-*"), "STYLE='FONT-FAMILY:verdana;FONT-SIZE:10px' CELLSPACING=0 CELLPADDING=2 BORDER=1", "NOWRAP", "STYLE='font-weight:bold'");
    }

    return array("doc_qty" => n($doc_qty, 0), "amount" => n($total));
}

function n($n, $d = 2) {
    return number_format(floatval($n), $d);
}

function request($what, $def) {
    if (isset($_REQUEST["$what"]))
        return $_REQUEST["$what"];
    else
        return $def;
}

function report_tp_get_qty($date_q) {
    // DO NOT REFERENCE THIS VARIABLE NAME DIRECTLY.
    // THIS FUNCTION TO BE TREATED AS A SINGLETON.
    global $__AllMonthQuantities;
    if ($__AllMonthQuantities) {
        return $__AllMonthQuantities;
    }
    $qty_query = "
			SELECT
				count(*),
				DTSDocuments.tpid_recipient,
				DTSEnvelopes.control,
				DTSEnvelopes.id
			FROM DTSDocuments
				INNER JOIN DTSEnvelopes
					ON DTSEnvelopes.id = DTSDocuments.envid
			WHERE
				$date_q
				AND (DTSDocuments.type <> '997')
			GROUP BY
				DTSDocuments.tpid_recipient,
				DTSEnvelopes.control,
				DTSEnvelopes.id";

    #$timer->report("STARTING execution of query... $qty_query");
    $qtyResults = mssql_query($qty_query) or die("Error querying MSQL: <BR>\n<B>$qty_query</B><BR>\n" . mssql_get_last_message());

    $__AllMonthQuantities = array();
    while ($row = mssql_fetch_array($qtyResults)) {
        $indexKey = $row['tpid_recipient'] . '_' . $row['control'] . '_' . $row['id'];
        $__AllMonthQuantities[$indexKey] = $row;
    }
    return $__AllMonthQuantities;
}

function report_tp($tp, $no3KBlocks = false, $unit_size = 3000, $partnerFilter = null) {
    global $timer;
    global $f;
    global $QUERY_SENT_FTP, $QUERY_SENT_EDI, $QUERY_REC_EDI, $GROUPBY_EDI, $QUERY_SENT_EDI_AKRON, $QUERY_REC_EDI_FANMATS_HD, $QUERY_REC_EDI_MMFASTENERS_CC;
    global $QUERY_REC_EDI_NOVA_CC, $QUERY_REC_EDI_THILL_850s_CC;
    global $QUERY_BY_CHANNEL, $QUERY_BY_OUTPUTQUEUE, $QUERY_BY_CHANNEL_EDI;
    global $QUERY_REC_EDI_INNOVATIVESD;
    global $webedi_ftp;
    global $rec_121_, $sent_121_;
    global $DATE_W, $DATE_W_TIMESTAMP, $DATE_W_CREATED;
    global $total_quantity;
    global $def_billing_m;
    global $edi_total_quantity;  # THIS IS THE TOTAL UNITS BEFORE EXTRA TRANSACTIONS (CCing, etc)
    global $year, $month;
    global $dropship;
    $filterSent = '';
    $filterRec = '';
    if ($partnerFilter && is_array($partnerFilter) && !empty($partnerFilter)) {
        $convertedIds = array();
        foreach ($partnerFilter as $_id) {
            $_id = intval($_id);
            if ($_id > 0) {
                $convertedIds[] = $_id;
            }
        }
        if (!empty($convertedIds)) {
            $_ids = implode(",", $convertedIds);
            $filterSent = "AND DTSDocuments.tpid_recipient in ($_ids)";
            $filterRec = "AND DTSDocuments.tpid_sender in ($_ids)";
        }
    }

    $QUERY_SENT_FTP = "$QUERY_SENT_FTP";
    $QUERY_SENT_EDI = "$QUERY_SENT_EDI AND DTSDocuments.tpid_sender = $tp $filterSent AND Batches.DataChannel NOT IN (1073742068) $GROUPBY_EDI";
    $QUERY_REC_EDI = "$QUERY_REC_EDI AND DTSDocuments.tpid_recipient = $tp $filterRec $GROUPBY_EDI";
    $QUERY_BY_CHANNEL_EDI = "$QUERY_BY_CHANNEL_EDI AND DTSDocuments.tpid_recipient = $tp $GROUPBY_EDI";

    $QUERY_REC_EDI_FANMATS_HD .= "AND DTSDocuments.tpid_recipient = $tp $GROUPBY_EDI";
    $QUERY_REC_EDI_INNOVATIVESD .= "AND DTSDocuments.tpid_recipient = $tp $GROUPBY_EDI";
    $QUERY_REC_EDI_MMFASTENERS_CC .= "AND DTSDocuments.tpid_recipient = $tp $GROUPBY_EDI";


    if ($DATE_W_TIMESTAMP == '') {
        $date_q = "(DATEPART(yyyy, DTSDocuments.[timestamp]) = $year)
					AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month)";
    } else {
        $date_q = $DATE_W_TIMESTAMP;
    }

    $allQty = report_tp_get_qty($date_q);

echo "\n******HERE*****\n";

    $out = "";

    $out .= "<BR>$f[20]Received EDI Messages$f[0]<BR>";
    $out .= report_query($QUERY_REC_EDI, false, $unit_size, $allQty);

    #print "<li>$QUERY_REC_EDI<br>";
//		$timer->report("FINISHED report_query(QUERY_REC_EDI)");
    #$out .= "$total_quantity ---<BR>";

    $out .= "<BR><BR>$f[20]Sent EDI Messages$f[0]<BR>";
    $out .= report_query($QUERY_SENT_EDI, false, $unit_size, $allQty);
//		$timer->report("FINISHED report_query(QUERY_SENT_EDI)");
    #$out .= "$total_quantity ---<BR>";

    $out .= "<BR><BR>$f[20]Data Processed through customer's output queues$f[0]<BR>";
    error_log($QUERY_BY_OUTPUTQUEUE);
    $out .= report_query($QUERY_BY_OUTPUTQUEUE, false, $unit_size, $allQty);
//		$timer->report("FINISHED report_query(QUERY_BY_OUTPUTQUEUE)");
    #$out .= "$total_quantity ---<BR>";

    $out .= "<BR><BR>$f[20]Data Processed through customer's input channels$f[0]<BR>";
    #die("$QUERY_BY_CHANNEL");
    $out .= report_query($QUERY_BY_CHANNEL, false, $unit_size, $allQty);
    #$out .= "$total_quantity ---<BR>";

    $edi_total_quantity = $total_quantity;

    #if($def_billing_m!="webedi")
    #{
    #	$out .= "<BR><BR>$f[20]FTP Outbound Data (Unprocessed):$f[0]<BR>";
    #	$out .= report_query($QUERY_SENT_FTP);
    #	$out .= "$total_quantity ---<BR>";
    #}

    if ($tp == 388) {
        $out .= "<BR><BR>$f[20]EDI Data from/to FTP:$f[0]<BR>";
        $out .= report_query($QUERY_SENT_FTP, false, $unit_size, $allQty);
        #$edi_total_quantity = $total_quantity;
    }

    if ($tp == 295) {
        $total_quantity = 0; # Doesn't take into account Sent
        $out .= "<BR><BR>$f[20]EDI Data from/to FTP:$f[0]<BR>";
        $out .= report_query($QUERY_SENT_FTP, false, $unit_size, $allQty);
        #$edi_total_quantity = $total_quantity;
    }

    if ($tp == 121) {
        $out .= "<BR><BR>$f[20]POP3 Inbound Data from MIDSHIP (Non EDI):$f[0]<BR>";
        $QUERY_REC_NON_EDI_121 = $rec_121_;
        $out .= report_query($QUERY_REC_NON_EDI_121, false, $unit_size, $allQty);
        $out .= "<BR><BR>$f[20]FTP Outbound Data to MIDSHIP (Non EDI):$f[0]<BR>";
        $QUERY_SENT_NON_EDI_121 = $sent_121_;
        $out .= report_query($QUERY_SENT_NON_EDI_121, false, $unit_size, $allQty);
        #$edi_total_quantity = $total_quantity;
    }
    if ($tp == 353) {
        $out .= "<BR><BR>$f[20]FTP Outbound Data TO AKRON (Duplicate):$f[0]<BR>";
        $out .= report_query($QUERY_SENT_EDI_AKRON, false, $unit_size, $allQty);
        #$out .= "$total_quantity ---<BR>";
    }
    if ($tp == 178) {
        $out .= "<BR><BR>$f[20]EDI Data from HomeDepot and Academy (Duplicate):$f[0]<BR>";
        $out .= report_query($QUERY_REC_EDI_FANMATS_HD, false, $unit_size, $allQty);
        #$out .= "$total_quantity ---<BR>";
    }
    if ($tp == 455) {
        $out .= "<BR><BR>$f[20]Inbound EDI Data from Office Depot (Duplicate):$f[0]<BR>";
        $out .= report_query($QUERY_REC_EDI_INNOVATIVESD, false, $unit_size, $allQty);
        #$out .= "$total_quantity ---<BR>";
    }
    if ($tp == 770) {
        $out .= "<BR><BR>$f[20]Inbound EDI Data from PACCAR (Carbon Copy):$f[0]<BR>";
        $out .= report_query($QUERY_REC_EDI_MMFASTENERS_CC, false, $unit_size, $allQty);
        $out .= "$total_quantity ---<BR>";
        #$edi_total_quantity = $total_quantity;
    }
    if ($tp == 840) {
        $out .= "<BR><BR>$f[20]Inbound 850s and 852s (Carbon Copy):$f[0]<BR>";
        $out .= report_query($QUERY_REC_EDI_NOVA_CC, false, $unit_size, $allQty);
        $out .= "$total_quantity ---<BR>";
        $out .= "edi_total_quantity: $edi_total_quantity ---<BR>";
        #$edi_total_quantity = $total_quantity;
    }
    if ($tp == 552 || $tp == 806) { #Thill/Grilldaddy/FitnessIQ/
        $QUERY_REC_EDI_THILL_850s_CC = str_replace("{:tp:}", "$tp", $QUERY_REC_EDI_THILL_850s_CC);
        $out .= "<BR><BR>$f[20]Inbound 850s (Carbon Copy):$f[0]<BR>";
        $out .= report_query($QUERY_REC_EDI_THILL_850s_CC, false, $unit_size, $allQty);
        $out .= "$total_quantity ---<BR>";
        $out .= "edi_total_quantity: $edi_total_quantity ---<BR>";
        #$edi_total_quantity = $total_quantity;
    }

    return $out;
}

function report_query($query, $no3KBlocks = false, $unit_size = 3000, $allQty) {
    global $timer;
    global $f, $year, $month, $tpid;
    global $total_quantity;
    global $DATE_W_TIMESTAMP;
    global $userid;
    $total_size = 0;
    $total = 0;
    $total_docs = 0;

    #print "<PRE><HR>## Query:<br> $query <HR>";
    #$timer->report("STARTING execution of query...");
    $result = mssql_query($query) or die("Error querying MSSQL:<BR>\n<B>$query</B><BR>\n" . mssql_get_last_message());

    $outstream = fopen("php://temp", 'r+');

    #$timer->report("FINISHED executing query...");
    if (mssql_num_rows($result) > 0) {
        $tbl = "$f[3]Title||$f[3]Size{style='width:100px'}||$f[3]Date{style='width:250px'}||$f[3]TP ID{style='width:80px'}||$f[3]TP Name{style='width:200px'}||$f[3]Type{style='width:80px'}||$f[3]Control{style='width:80px'}||$f[3]Units{style='width:50px'}-*-*";
        while ($row = mssql_fetch_array($result)) {
            ### After deleting batches from DataAdmin, they continue to show up here. SOOOOO:
            if (trim($row["BatchTitle"]) == "" && trim($row["ContentSize"]) == "")
                continue;
            if (foundDuplicate($userid, $row))
                continue;
            if (strpos($row["BatchTitle"], "/Home/DataTrans/DataTrans_850") === false) {
                //*********************************************************************************
                //Calculate number of documents contained in a batch
                //*********************************************************************************
                $indexKey = $tpid . '_' . $row['control'] . '_' . $row['env_id'];
                $qty_result = $allQty[$indexKey]; // get result from cache
                #$timer->report("DONE...");
                /* if(++$count==5000)
                  {
                  print "<PRE>";
                  print_r($timer->history);
                  die();
                  } */
                if (isset($qty_result) && !empty($qty_result)) {
//						$tmp_row=mssql_fetch_array($qty_result);
                    if ($qty_result[0] > 0) {
                        $qty_docs = $qty_result[0];
                        $row["Qty"] = $qty_docs;
                    }
                } else
                    $qty_docs = 0;
                //*********************************************************************************

                $size = $row["ContentSize"];
                $units = ceil($size / $unit_size);

                #die("Size: $size, Units; $units, Qty: {$row["Qty"]} no3KBlocks: $no3KBlocks");
                if ($row["Qty"] > $units) # If there are more docs than units... then we use the doc count instead
                    $units = $row["Qty"];

                if ($no3KBlocks) # Overrides unit count... only use doc count in query
                    $units = $row["Qty"];

                global $no_count;
                if ($no_count == "Y") # Overrides unit count... only use doc count in query
                    $units = $row["Qty"];

                #print "Size: $size, Units; $units, Qty: {$row["Qty"]} no3KBlocks: $no3KBlocks, no_Count: $no_count<br>";
                #die();

                $total_size += $size;
                $total += $units;
                $total_docs += $qty_docs;
                #$row["Qty"]=1;
                #$tbl .= "$f[2]{$row["OutputQueue"]}$f[0]||$f[2]{$row["BatchTitle"]} $f[0]||$f[2]{$row["ContentSize"]} ($qty_docs)$f[0]||$f[2]{$row["Created"]}$f[0]||$f[2]{$row["tpid"]}$f[0]||$f[2]{$row["tpname"]}$f[0]||$f[2]{$row["type"]}$f[0]||$f[2]{$row["control"]}$f[0]||{ALIGN=\"RIGHT\"}$f[2]$units$f[0]--";
                $tbl = "$f[2]{$row["BatchTitle"]} $f[0]||{STYLE='text-align:right;padding-right:40px'} $f[2]{$row["ContentSize"]} $f[0]||$f[2]{$row["Created"]}$f[0]||$f[2]{$row["tpid"]}[$tpid]$f[0]||$f[2]{$row["tpname"]}$f[0]||$f[2]{$row["type"]}$f[0]||$f[2]{$row["control"]}$f[0]||{ALIGN=\"RIGHT\"}$f[2]$units$f[0]-*-*";
                fputs($outstream, $tbl);

                $total_quantity += $units;
            }
        }
        $tbl = "{COLSPAN=\"2\" ALIGN=\"RIGHT\"}$f[3]Total:$f[0]||$f[3]$total_size $f[0]||{COLSPAN=\"4\" ALIGN=\"RIGHT\"}$f[3]Total Units:$f[0]||{ALIGN=\"RIGHT\"}$f[3]$total$f[0]";
        fputs($outstream, $tbl);

        rewind($outstream);
        $tbl = stream_get_contents($outstream);
        fclose($outstream);

        return array2tbl($tbl, "WIDTH='100%' STYLE='BORDER: 2PX SOLID BLACK'");
    }
    unset($result);
}

function createDabatase() {
    global $pdo_db;
    error_log('Connecting to' . _MYSQL_HOST);
    $pdo_db = new PDO('mysql:host=' . _MYSQL_HOST . ';', _MYSQL_USER, _MYSQL_PASS);
}

function cleanDuplicates() {
    global $pdo_db;
    error_log('cleaning dup checks');
    if ($pdo_db == null) {
        createDabatase();
    }
    $ret = $pdo_db->exec("DELETE FROM webedi30.HashTable WHERE hash like 'dup_%';");
    if ($ret === false) {
        die(print_r($pdo_db->errorInfo(), true));
    }
    return true;
}

function foundDuplicate($custid, $row) {
    global $pdo_db;
    error_log('Checking Dup for:' . $custid . ' ' . print_r($row['BatchTitle'], true));
    if (!isset($row['control']) || !isset($row['type']) || $row['type']=='FLATFILE' || $row['control']=='N/A') {
        return false;
    }
    $hash = "dup_" . md5(print_r(array(
                $custid,
                $row['control'],
                //    $row['env_id'],
                $row['type'],
                $row['ContentSize'],
                session_id()
                            ), true));
    error_log('Checking Dup for' . print_r($row, true));
    $query1 = "SELECT COUNT(*) FROM webedi30.HashTable WHERE hash='$hash';";
    error_log($query1);
    if ($pdo_db == null) {
        createDabatase();
    }
    $rs = $pdo_db->query($query1) or die("Error querying MSQL: $query1");
    if ($rs->rowCount() > 0) {
        $data = $rs->fetch();
        error_log('found' . print_r($data, true));
        if ($data[0] > 0) {
            return true;
        }
    }
    error_log('Inserting into table');
    $query2 = "INSERT INTO webedi30.HashTable (hash,value) VALUES ('$hash',1);";
    $ret = $pdo_db->exec($query2);

    if ($ret === false) {
        die(print_r($pdo_db->errorInfo(), true));
    }
    //insertDetail($custid, $row);
    return false;
}

function insertDetail($custid, $row) {
    global $pdo_db;
    if ($pdo_db == null) {
        createDabatase();
    }
    $hash = md5(print_r(array(
        $custid,
        $row['control'],
        $row['type'],
        $row['ContentSize']), true));
    $query3 = "INSERT INTO billing.billing_workitems (foreign_reference,server,time_stamp,customer_reference,billing_code_id,title,qty1,qty2,qty3) VALUES (:hash,:server,:time,:customer,0,:title,:qty,:size,0);";
    $query4 = "INSERT INTO billing.billing_workitem_props (workitem_id,prop_name,prop_value) VALUES (:id,:name,:value);";
    $stmt = $pdo_db->prepare($query3);
    $params3 = array(
        'hash' => $hash,
        'server' => 'legacy',
        'time' => date("YmdHis", strtotime($row['Created'])),
        'customer' => $custid,
        'title' => $row['BatchTitle'],
        'qty' => $row['Qty'],
        'size' => $row['ContentSize']
    );
    $ret2 = $stmt->execute($params3);
    if ($ret2 !== false) {
        $id = $pdo_db->lastInsertId();
        $stmt2 = $pdo_db->prepare($query4);
        foreach ($row as $k => $v) {
            if (!is_numeric($k)) {
                $stmt2->execute(array('id' => $id, 'name' => $k, 'value' => $v));
            }
        }
    }
}

function mssql_value($sql, $dbname = 'AS2A') {
#		MS SQL
    $ms_db_host = MSSQL_HOST;
    $ms_db_user = MSSQL_USER;
    $ms_db_pass = MSSQL_PASS;
    $ms_db_name = $dbname;

    $ms_db = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass); # or die ("Unable to connect!");
    mssql_select_db($ms_db_name) or die("Could not select DB: $ms_db_name");
    $rs = mssql_query($sql) or die("Error querying MSQL: <BR>\n<B>$sql</B><BR>\n" . mssql_get_last_message());
    if (mssql_num_rows($rs) > 0) {
        $row = mssql_fetch_array($rs);
        return $row[0];
    }
    return false;
}

function getInclusiveDocFilter($userid) {
    // $filter must be a single-quote enclosed value, like "'856'" or
    // multiple single-quote values separated by commas, like "'850','810'"
    $filter = "";
    switch ($userid) {
        case '2825':
            $filter = "'850'";
            break;

        default:
            break;
    }
    return $filter;
}

### For formatting...
$f[0] = "</FONT>";
$f[1] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:11;font-weight:b old;color:darkblue\">";
$f[2] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:11;\">";
$f[3] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:12;font-weight:bold\">";
$f[4] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:12;font-weight:bold;color:000000\">";
$f[5] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:12;font-weight:bold;color:C0C0C0\">";
$f[6] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:20;color:A0A0F0\">";
$f[7] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:20;color:DarkGray\">";
$f[10] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:7;color:black\">";

$f[11] = "<FONT FACE=\"Arial\" STYLE=\"font-size:12;font-weight:;color:#000000\">";
$f[12] = "<FONT FACE=\"Arial\" STYLE=\"font-size:10;font-weight:;color:#808080\">";
$f[13] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:12;color:000000\">";
$f[14] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:20;color:DarkGray\">";
$f[15] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"color:$A0A0A0;font-size:12;font-weight:bold\">";

$f[20] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"color:$A0A0A0;font-size:16;font-weight:bold\">";
$f[21] = "<FONT FACE=\"Arial\" STYLE=\"font-size:12;font-weight:;color:#000000\">";
$f[22] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:24;color:000000\">";

$f[31] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:9;font-weight:bold;color:darkblue\">";

$f[41] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:15;font-weight:bold;color:707070\">";
$f[42] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:12;color:000000\">";
$f[43] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:12;font-weight:bold;color:000000\">";
$f[44] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:20;color:003366\">";
$f[45] = "<FONT FACE=\"Verdana,Arial\" STYLE=\"font-size:17;font-weight:bold;color:000000\">";
