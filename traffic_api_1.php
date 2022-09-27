<?php
//error_log("se exe");
include_once(__DIR__ . '/../env.php');
include_once(__DIR__ . '/../includes/timer.php');
include_once(__DIR__ . '/../includes/functions/db.php');
include_once(__DIR__ . '/../includes/vars.php');
include_once(__DIR__ . '/includes/form_functions.php');
include_once(__DIR__ . '/includes/db2.php');
include_once(__DIR__ . '/common3.php');
include_once(__DIR__ . "/edi_traffic_queries_new.php");

ini_set('error_log', '/var/log/php-billing-error.log');
ini_set('memory_limit', '8G');
set_time_limit(0);

if (php_sapi_name() == "cli") {
    $arguments = getopt('', array('start_date:', 'end_date:', 'form_userid:', 'db:', 'filter_dups::'));
    $_REQUEST = $arguments;
    $_GET = $arguments;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_REQUEST['action'] = null;
}
if (!in_array($_SERVER['REMOTE_ADDR'], array('172.18.0.26', '127.0.0.1'))) {
    //die('Unauthorized');
}

$timer->report("FINISHED LOADING libs");


###	Load plans into dropdown
$arr_plans = mysql_rows("SELECT name,PK_id FROM webedi_tp_billing_schemas");

$timer->report("FINISHED SELECTING Schemas");

#	MS SQL
$ms_db_host = MSSQL_HOST;
$ms_db_user = MSSQL_USER;
$ms_db_pass = MSSQL_PASS;
$ms_db_name = request("db", MSSQL_DB_A);

$ms_db = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass) or die("Unable to connect!");
mssql_select_db($ms_db_name) or die("Could not select DB: $ms_db_name");


$start_date = request("start_date", date("Y-m-d", strtotime('-7 days')));
$end_date = request("end_date", date("Y-m-d"));
$userid = request("form_userid", "");
$filterDups = request('filter_dups', 1);

$partner = mysql_row("SELECT * FROM webedi30.Partners WHERE PK_id = $userid");

$timer->report("FINISHED SELECTING * FROM webedi_tp");

### OVERRIDES...
foreach (array("1895" => "1898", "1612" => "1475", "2026" => "1986", "1875" => "1765", "1837" => "1370") as $webtpid => $new_webtpid) {
    if ($partner["webtpid"] == $webtpid) {
        $partner["webtpid"] = $new_webtpid;
    }
}

$mrs = mssql_query("SELECT * FROM Partners Where local_id1 = '{$partner["webtpid"]}'") or die(mssql_message());
$row = mssql_fetch_array($mrs);
$tpid = $row["tpid"];   #Customer's ID in ECS database
error_log(print_r($row,true));
$timer->report("FINISHED SELECTING * FROM Partners (SQL)");

if ($tpid <> "") {
    $local_id1 = $row['local_id1'];
    $local_id3 = $row['local_id3'];
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


$DATE_W = "(created BETWEEN '$start_date' AND '$end_date')";

$others_ = "";
//error_log(__FILE__ . " :: " . __LINE__ . " :: GOING");
$filterDocsInclusive = getInclusiveDocFilter($userid);
/* * * BEGin QUERIES ** */
// These queries include BatchId field



/* * * END QUERIES ** */
$timer->report("FINISHED LOADING edi_traffic_queries.php");

if ($tpid != '') {
    $no_count = mysql_value("SELECT ignore_size FROM webedi_tp_billing_schemas WHERE PK_id = '$def_schema'");

    $builder = new EcsQueries($tpid, $start_date, $end_date);
    $builder->setDropship($dropship)
            ->setFilterDocsInclusive($filterDocsInclusive)
            ->setPartnerFilter(null)
            ->setUserid($userid);
    // $out = report_tp($tpid, $no3KBlocks, $unit_size, null, $dropship);
    $queries = $builder->buildAllQueries();
error_log(print_r($queries,true));
    $date_q = "(DTSDocuments.[timestamp] BETWEEN '$start_date' AND '$end_date')";
    $allQty = report_tp_get_qty($date_q);

    $out = array();

    //$out .= "<BR>$f[20]Received EDI Messages$f[0]<BR>";
    //var_dump($queries);

    $out[$ms_db_name]['RECV'] = report_query($queries['QUERY_REC_EDI'], false, $unit_size, $allQty, 'RECV');

    #print "<li>$QUERY_REC_EDI<br>";
//		$timer->report("FINISHED report_query(QUERY_REC_EDI)");
    #$out .= "$total_quantity ---<BR>";
    //$out .=  "<BR><BR>$f[20]Sent EDI Messages$f[0]<BR>";
    $out[$ms_db_name]['SENT'] = report_query($queries['QUERY_SENT_EDI'], false, $unit_size, $allQty, 'SENT');
//		$timer->report("FINISHED report_query(QUERY_SENT_EDI)");
    #$out .= "$total_quantity ---<BR>";
    //$out .=  "<BR><BR>$f[20]Data Processed through customer's output queues$f[0]<BR>";
    #die($QUERY_BY_OUTPUTQUEUE);
    $out[$ms_db_name]['SENTQ'] = report_query($queries['QUERY_BY_OUTPUTQUEUE'], false, $unit_size, $allQty, 'SENTQ');
//		$timer->report("FINISHED report_query(QUERY_BY_OUTPUTQUEUE)");
    #$out .= "$total_quantity ---<BR>";
    //$out .=  "<BR><BR>$f[20]Data Processed through customer's input channels$f[0]<BR>";
   #die($queries['QUERY_BY_CHANNEL']);
    $out[$ms_db_name]['RECVQ'] = report_query($queries['QUERY_BY_CHANNEL'], false, $unit_size, $allQty, 'RECVQ');
    #$out .= "$total_quantity ---<BR>";

    $edi_total_quantity = $total_quantity;

    #if($def_billing_m!="webedi")
    #{
    #	$out .= "<BR><BR>$f[20]FTP Outbound Data (Unprocessed):$f[0]<BR>";
    #	$out .= report_query($QUERY_SENT_FTP);
    #	$out .= "$total_quantity ---<BR>";
    #}

    if ($tp == 388) {
        //$out .= "<BR><BR>$f[20]EDI Data from/to FTP:$f[0]<BR>";
        $out[$ms_db_name]['SENTFTP'] = report_query($queries['QUERY_SENT_FTP'], false, $unit_size, $allQty, 'SENTFTP');
        #$edi_total_quantity = $total_quantity;
    }

    if ($tp == 295) {
        $total_quantity = 0; # Doesn't take into account Sent
        //$out .= "<BR><BR>$f[20]EDI Data from/to FTP:$f[0]<BR>";
        $out[$ms_db_name]['RECVFTP'] = report_query($queries['QUERY_SENT_FTP'], false, $unit_size, $allQty, 'RECVFTP');
        #$edi_total_quantity = $total_quantity;
    }


    // @TODO: Fix this case into the EcsQueries class
    if ($tp == 121) {
        //$out .= "<BR><BR>$f[20]POP3 Inbound Data from MIDSHIP (Non EDI):$f[0]<BR>";
        $QUERY_REC_NON_EDI_121 = $rec_121_;
        $out[$ms_db_name]['RECVPOP3'] = report_query($queries['QUERY_REC_NON_EDI_121'], false, $unit_size, $allQty, 'RECVPOP3');
        //$out .= "<BR><BR>$f[20]FTP Outbound Data to MIDSHIP (Non EDI):$f[0]<BR>";
        $QUERY_SENT_NON_EDI_121 = $sent_121_;
        $out[$ms_db_name]['SENTPOP3'] = report_query($queries['QUERY_SENT_NON_EDI_121'], false, $unit_size, $allQty, 'SENTPOP3');
        #$edi_total_quantity = $total_quantity;
    }
    if ($tp == 353) {
        //$out .= "<BR><BR>$f[20]FTP Outbound Data TO AKRON (Duplicate):$f[0]<BR>";
        $out[$ms_db_name]['SENTFDUP'] = report_query($queries['QUERY_SENT_EDI_AKRON'], false, $unit_size, $allQty, 'SENTFDUP');
        #$out .= "$total_quantity ---<BR>";
    }
    if ($tp == 178) {
        //$out .= "<BR><BR>$f[20]EDI Data from HomeDepot and Academy (Duplicate):$f[0]<BR>";
        $out[$ms_db_name]['RECVDUP'] = report_query($queries['QUERY_REC_EDI_FANMATS_HD'], false, $unit_size, $allQty, 'RECVDUP');
        #$out .= "$total_quantity ---<BR>";
    }
    if ($tp == 455) {
        //$out .= "<BR><BR>$f[20]Inbound EDI Data from Office Depot (Duplicate):$f[0]<BR>";
        $out[$ms_db_name]['RECVDUP'] = report_query($queries['QUERY_REC_EDI_INNOVATIVESD'], false, $unit_size, $allQty, 'RECVDUP');
        #$out .= "$total_quantity ---<BR>";
    }
    if ($tp == 770) {
        //$out .= "<BR><BR>$f[20]Inbound EDI Data from PACCAR (Carbon Copy):$f[0]<BR>";
        $out[$ms_db_name]['RECVCC'] = report_query($queries['QUERY_REC_EDI_MMFASTENERS_CC'], false, $unit_size, $allQty, 'RECVCC');
        //$out .= "$total_quantity ---<BR>";
        #$edi_total_quantity = $total_quantity;
    }
    if ($tp == 840) {
        //$out .= "<BR><BR>$f[20]Inbound 850s and 852s (Carbon Copy):$f[0]<BR>";
        $out[$ms_db_name]['RECVCC'] = report_query($queries['QUERY_REC_EDI_NOVA_CC'], false, $unit_size, $allQty, 'RECVCC');
        //$out .= "$total_quantity ---<BR>";
        //$out .= "edi_total_quantity: $edi_total_quantity ---<BR>";
        #$edi_total_quantity = $total_quantity;
    }
    if ($tp == 552 || $tp == 806) { #Thill/Grilldaddy/FitnessIQ/
        //$out .= "<BR><BR>$f[20]Inbound 850s (Carbon Copy):$f[0]<BR>";
        $out[$ms_db_name]['RECVCC'] = report_query($queries['QUERY_REC_EDI_THILL_850s_CC'], false, $unit_size, $allQty, 'RECVCC');
        //$out .= "$total_quantity ---<BR>";
        //$out .= "edi_total_quantity: $edi_total_quantity ---<BR>";
        #$edi_total_quantity = $total_quantity;
    }
}

###
### PRINT DETAIL OF TRANSACTIONS:
###
header('Content-Type: application/json');
echo json_encode($out);
$timer->report("Finished everything");
