<?php
// This version calls URLs with the API to run the billing process.

set_time_limit(0);
ini_set('error_log', '/var/log/php-billing_process.log');
error_reporting(E_ALL);
include __DIR__ . '/../../env.php';
include __DIR__ . '/../includes/CurlMulti.php';
include __DIR__ . '/../includes/BillingUtil.php';

$arguments=getopt("", array('startat::','date::','exclude::','include::','stopat::'));
echo "Arguments:\n";
var_dump($arguments);
$timestamp = strtotime('-27 days');
$year = (isset($arguments['date']))?date('Y', strtotime($arguments['date'])):date("Y", $timestamp);
$month = (isset($arguments['date']))?date("n",strtotime($arguments['date'])):date("n", $timestamp);
$startat = (isset($arguments['startat']))?$arguments['startat']:0;
$stopat = (isset($arguments['stopat']))?$arguments['stopat']:PHP_INT_MAX;
$exclude = (isset($arguments['exclude']))?explode(',',$arguments['exclude']):array();
$include = (isset($arguments['include']))?explode(',',$arguments['include']):array();

$baseUrl1 = "https://127.0.0.1/billing2/total_api.php?form_month=$month&form_year=$year&form_userid";
$baseUrl2 = "https://127.0.0.1/billing2/traffic_api.php?form_month=$month&form_year=$year&form_userid";
try {
    echo "Getting customer list\n";
    $list = BillingUtil::getCustomerList(array('month' => $month, 'year' => $year));
    echo " Found: ".count($list)." customers\n";
    $data = array();
    foreach ($list as $i => $entry) {
        if(BillingUtil::isCancelled($entry, $year, $month)) {
            continue;
        }
        if($i<$startat) {
            continue;
        }
        if($i>$stopat) {
            continue;
        }
        if( in_array($entry['PK_id'],$exclude)) {
            continue; // used i.e to skip Trixie
        }
        if(!empty($include) && !in_array($entry['PK_id'],$include)) {
            continue; // used i.e to process a single customer
        }
        $webid = $entry['webtpid'];
        $data[$webid]['total'] = 0;
        $data[$webid]['traffic'] = 0;
        //$json = Curl::request("$baseUrl1={$entry['PK_id']}");
        $data[$webid]['total'] += $json['total'];
        foreach (array('AS2A', 'AS2B', 'AS2C') as $db) {
            $json = Curl::request("$baseUrl2={$entry['PK_id']}&db=$db",null,7200);
            if ($json === null) {
                continue;
            }
            $units = array_reduce($json[$db], function($carry, $item) use ($db) {
                return $carry + array_reduce($item, function($scarry, $sitem) {
                            return $scarry + $sitem['Units'];
                        }, 0);
            }, 0);
            echo "\t{$entry['name']} ($webid/{$entry['PK_id']}) sub-traffic $db = $units\n";
            $data[$webid]['traffic'] += $units;
        }
        $pct = number_format($i / count($list) * 100) . '%';
        echo "#$i:($pct) {$entry['name']} ($webid/{$entry['PK_id']})  Traffic: {$data[$webid]['traffic']}\n";
        BillingUtil::updateCustomerTraffic($entry['PK_id'], $year, $month, $data[$webid]['traffic'], $entry['billing_schema']);
        $currentSchema = BillingUtil::getBillingSchema($entry['billing_schema']);
        $dropship = ($currentSchema['dropship'] == 'Y');
        $dropshipBasePrice = $currentSchema['dropship_base_price'];
        if ($dropship) {
            $adjustment = new InvoiceAdjustment($month, $year);
            $adjustment->applyAdjustment($userid, 'Dropship charges', $dropshipBasePrice);
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
} 

try {
    $adjustment = new InvoiceAdjustment($month, $year);
    $adjustment->applyReferralBilledDiscount(2825, BillingUtil::$partnerBilledAmounts, 'fixed', -10.00);
} catch (Exception $e) {
    print($e->getMessage());
    error_log(basename(__FILE__) . " :: " . __LINE__ . " :: " . $e->getMessage());
}
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
