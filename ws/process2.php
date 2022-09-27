<?php
// This process all the traffic by Forking a sub process with PHP to run the billing for each customer/server
set_time_limit(0);
ini_set('error_log', '/var/log/php-billing_process.log');
error_reporting(E_ALL);
define('LOG_LEVEL', LOG_INFO);
include __DIR__ . '/../../env.php';
include __DIR__ . '/../includes/Process.php';
include __DIR__ . '/../includes/BillingUtil.php';
include __DIR__ . '/../includes/Ansi.php';
include __DIR__ . '/../includes/Collection.php';
define('POOL_SIZE', 3);
define('CHECK_INTERVAL_SECS', 1);
$arguments = getopt("", array('startat::', 'date::', 'exclude::', 'include::', 'stopat::'));
echo "Arguments:\n";
var_dump($arguments);
$timestamp = strtotime('-7 days');
$year = (isset($arguments['date'])) ? date('Y', strtotime($arguments['date'])) : date("Y", $timestamp);
$month = (isset($arguments['date'])) ? date("n", strtotime($arguments['date'])) : date("n", $timestamp);
$startat = (isset($arguments['startat'])) ? $arguments['startat'] : 0;
$stopat = (isset($arguments['stopat'])) ? $arguments['stopat'] : PHP_INT_MAX;
$exclude = (isset($arguments['exclude'])) ? explode(',', $arguments['exclude']) : array();
$include = (isset($arguments['include'])) ? explode(',', $arguments['include']) : array();

function getObjectPrint($o) {
    ob_start();
    var_dump($o);
    $ret= ob_get_clean();
    return substr($ret,0, strpos($ret,"\n"));
}
var_dump(array(
    'Timestamp'=>$timestamp,
    'year'=>$year,
    'month'=>$month,
    'startat'=>$startat,
    'stopat'=>$stopat,
    'exclude'=>$exclude,
    'include'=>$include,
    'poolsize'=>POOL_SIZE
       
))."\n";
try {
    $poolSize = POOL_SIZE;
    $pool = new Collection();
    $workQueue = new SplQueue();
    $workingPath = realpath(__DIR__ . "/../");
    consoleinfo("Initialize Process Pool");
    for ($i = 0; $i < $poolSize; $i++) {
        $process = new Process('ls');
        $process->setWorkingDirectory($workingPath);
        $process->enableOutput();
        $process->setTimeout(null);
        $process->setOptions(array('suppress_errors' => false));
        $pool[]=$process;
        consoledebug("Attached ".getObjectPrint($process));
    }
    consoleinfo("Getting customer list");
    $options=array('month' => $month, 'year' => $year);
    if(!empty($include)) {
        //$options['tpid']= implode(',', $include);
    }
    $list = BillingUtil::getCustomerList($options);
    consoleinfo(" Found: " . count($list) . " customers");
    $data = array();
    foreach ($list as $i => $entry) {
        if (BillingUtil::isCancelled($entry, $year, $month)) {
            continue;
        }
        if ($i < $startat) {
            continue;
        }
        if ($i > $stopat) {
            continue;
        }
        if (!empty($exclude) && in_array($entry['PK_id'], $exclude)) {
	    consoleinfo(" Skipping {$entry['PK_id']} in exclude list");
            continue; // used i.e to skip Trixie
        }
        if (!empty($include) && !in_array($entry['PK_id'], $include)) {
            consoleinfo(" Skipping {$entry['PK_id']} not in include list");
            continue; // used i.e to process a single customer
        }
        $webid = $entry['webtpid'];
        $data[$webid]['traffic'] = 0;
        
        foreach (array('AS2A', 'AS2B', 'AS2C') as $db) {

            $cmd = "\$(which php) -f $workingPath/traffic_api.php -- --form_month=$month --form_year=$year --form_userid={$entry['PK_id']} --db=$db 2>>/tmp/php-billing-error.log ";
            //$cmd = "\$(which php) -f $workingPath/test.php -- --form_month=$month --form_year=$year --form_userid={$entry['PK_id']} --db=$db";
            $workQueue->enqueue(array(
                'cmd' => $cmd,
                'db' => $db,
                'tpid' => $entry['PK_id'],
                'webtpid' => $entry['webtpid'],
                'month' => $month,
                'year' => $year,
                'name' => $entry['name'],
                'schema' => $entry['billing_schema']));
            consoledebug("Queueing up $cmd");
        }
    }


    consoleinfo("Starting Process Managament");
    while (!$workQueue->isEmpty()) {
        
        consoledebug(__FILE__.'#'.__LINE__);
        
        foreach($pool as $i=>$p) {
            global $data;
            consoledebug("Checking ".getObjectPrint($p));
            if (!$p->isStarted() || $p->isRunning()) {   
                echo ".";
                continue;
            }
            consoledebug(__FILE__.'#'.__LINE__); 
            $output = $p->getOutput();
            $output .= $p->getErrorOutput();
            consoleinfo("OUTPUT: [$output]");
            $json = json_decode($output, true);
            if ($json === null) {
                consoleerr("Process: {$p->getCommandLine()} ({$p->getPid()}) returned bad JSONError - [$output]");
                continue;
            }
            $db = $p->data['db'];
            $tpid = $p->data['tpid'];
            $name = $p->data['name'];
            $webid = $p->data['webtpid'];
            $year = $p->data['year'];
            $month = $p->data['month'];
            $billing_schema = $p->data['schema'];
            $units = array_reduce($json[$db], function($carry, $item) use ($db) {
                return $carry + array_reduce($item, function($scarry, $sitem) {
                            return $scarry + $sitem['Units'];
                        }, 0);
            }, 0);
            consolenoti("\t{$name} ($webid/{$tpid}) sub-traffic $db = $units");
            $data[$webid]['traffic'] += $units;
            BillingUtil::updateCustomerTraffic($tpid, $year, $month, $data[$webid]['traffic'], $billing_schema);
        }
        $available = new Collection();
        foreach($pool as $i=>$p) {
            consoledebug("Check Availability for ".getObjectPrint($p));
            if(!$p->isStarted() || !$p->isRunning()) {
                consoledebug("Available ".getObjectPrint($p));
                $available[]=$p;
            }
        }
        consoleinfo( 'Processes Free:' . $available->count() . ' InUse:' . ($pool->count() - $available->count()) . ' Tasks:' . $workQueue->count() );
        foreach($available as $i=>$ready) {
            if (!$workQueue->isEmpty()) {
                consoledebug("Starting ".getObjectPrint($ready));
                $obj = $workQueue->dequeue();
                $cmd = $obj['cmd'];
                $ready->setCommandLine($cmd);
                $ready->start();
                consolenoti('Running:' . $cmd." (".$ready->getPid().")");
                $ready->data = $obj;
                
            }
        }
        sleep(CHECK_INTERVAL_SECS);
    }
    consoleinfo("Done Process");
} catch (Exception $e) {
    consoleerr("ERROR: " . $e->getMessage());
    consoleerr($e->getTraceAsString());
}

//try {
//    $adjustment = new InvoiceAdjustment($month, $year);
//    $adjustment->applyReferralBilledDiscount(2825, BillingUtil::$partnerBilledAmounts, 'fixed', -10.00);
//} catch (Exception $e) {
//    print($e->getMessage());
//    error_log(basename(__FILE__) . " :: " . __LINE__ . " :: " . $e->getMessage());
//}
require_once(__DIR__ . '/includes/emailer.php');

### PARSE emailAddress string, set first address as the TO address in the email, any others as CC.
$emailAddressList = "sysadmin@datatrans-inc.com;accounting@datatrans-inc.com";
foreach (preg_split("/[\s,;]+/", $emailAddressList) as $emailAddress) {
    $emailAddress = trim($emailAddress);
    if ($emailAddress == "") {
        continue;
    }

    if (!is_a($email, "Emailer")) {
        $email = new Emailer("edi_traffic_load@datatrans-inc.com", $emailAddress, "127.0.0.1");
    } else {
        $email->addAddress($emailAddress, _EMAILER_CC);
    }
}

$email->setSubject("The traffic calculation for $month/$year has run successfully.");
$email->setMessage("The traffic calculation for $month/$year has run successfully.");

if (ENVIRONMENT !== 'DEVELOPMENT' && !$email->send()) {
    header("HTTP/1.0 507 Internal Server Error. $body.");
    header("Content-Type: text/plain");
    die("Email could not be delivered: \r\n $body");
}

die("Email sent successfully. $body");
