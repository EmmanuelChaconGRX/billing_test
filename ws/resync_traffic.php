<?php
// Script that Updates the Totals on webedi.webedi_tp_monthly_traffic table and emails Accounting at the start of the month.

set_time_limit(0);
ini_set('error_log', '/var/log/php-billing_process.log');
error_reporting(E_ALL);
include __DIR__ . '/../../env.php';
include __DIR__ . '/../includes/CurlMulti.php';
include __DIR__ . '/../includes/BillingUtil.php';

$arguments=getopt("", array('startat::','date::','exclude::','stopat::'));
echo "Arguments:\n";
var_dump($arguments);
$timestamp = strtotime('-7 days');
$year = (isset($arguments['date']))?date('Y', strtotime($arguments['date'])):date("Y", $timestamp);
$month = (isset($arguments['date']))?date("n",strtotime($arguments['date'])):date("n", $timestamp);
$startat = (isset($arguments['startat']))?$arguments['startat']:0;
$stopat = (isset($arguments['stopat']))?$arguments['stopat']:PHP_INT_MAX;
$exclude = (isset($arguments['exclude']))?explode(',',$arguments['exclude']):array();
$pdo = BillingUtil::getConnection();
$stmt = $pdo->prepare("SELECT 
    SUM(qty1)
FROM
    (SELECT 
        wi.*,
            MAX(IF(wp.prop_name = 'sender', wp.prop_value, NULL)) `sender`,
            MAX(IF(wp.prop_name = 'tpname', wp.prop_value, NULL)) `tpname`,
            MAX(IF(wp.prop_name = 'control', wp.prop_value, NULL)) `control`,
            MAX(IF(wp.prop_name = 'ContentSize', wp.prop_value, NULL)) `size`,
            MAX(IF(wp.prop_name = 'type', wp.prop_value, NULL)) `doctype`,
            MAX(IF(wp.prop_name = 'Duplicate', wp.prop_value, 0)) `dup`
    FROM
        billing.billing_workitems wi
    INNER JOIN billing.billing_workitem_props wp ON wi.id = wp.workitem_id
    WHERE
        wi.customer_reference = :tpid
            AND wi.time_stamp BETWEEN :start_date AND :end_date
    GROUP BY wi.id
    HAVING `dup` = 0) t;");

$stmt2 = $pdo->prepare("SELECT value FROM webedi.webedi_tp_params WHERE FK_client=:tpid AND param = 'billing_mode';");
$stmt3 = $pdo->prepare("SELECT value FROM webedi.webedi_tp_params WHERE FK_client=:tpid AND param = 'billing_schema';");
// TODO: Move this to Some kind of config. Reporting account traffic
$pdo->exec("UPDATE billing.billing_workitems SET customer_reference=464 WHERE customer_reference=580;");
$pdo->exec("UPDATE billing.billing_workitems SET customer_reference=174 WHERE customer_reference=300;");


$start_date = date('Ym01', strtotime("$year-$month-1"));
//$end_date = date('Ymd', strtotime("$year-$month-11"));//
$end_date = date('Ym01', strtotime("$year-$month-1 +1 month"));
try {
    echo "Getting customer list in Mon: $month, Year: $year\n";
    $list = BillingUtil::getCustomerList(array('month' => $month, 'year' => $year));
    echo " Found: ".count($list)." customers\n";
foreach ($list as $i => $entry) { echo "$i => {$entry['name']}({$entry['PK_id']}/{$entry['webtpid']})\n"; }
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
            continue; // skip Trixie
        }

        $webid = $entry['webtpid'];
        $data[$webid]['total'] = 0;
        $data[$webid]['traffic'] = 0;
        $stmt->execute(array(
           'tpid'=>$entry['PK_id'],
            'start_date'=>$start_date,
            'end_date'=>$end_date
        ));
        $traffic = $stmt->fetchColumn();
        $stmt->closeCursor();
        $stmt3->execute(array('tpid'=>$entry['PK_id']));
        $entry['billing_schema'] = $stmt3->fetchColumn();
        $stmt3->closeCursor();
        $pct = number_format($i / count($list) * 100) . '%';
        echo "#$i:($pct) {$entry['name']} ($webid/{$entry['PK_id']})  Traffic: {$data[$webid]['traffic']}\n";
        BillingUtil::updateCustomerTraffic($entry['PK_id'], $year, $month, $traffic, $entry['billing_schema'],true);
    } 
    /*foreach (array("1895" => "1898", "1612" => "1475", "2026" => "1986", "1875" => "1765", "1837" => "1370") as $webtpid => $new_webtpid) {
        if ($partner["webtpid"] == $webtpid) {//not partner
            $partner["webtpid"] = $new_webtpid;
        }
    }*/
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
} 

try {
    require_once(__DIR__ . '/../../InvoiceAdjustment.php');
    $adjustment = new InvoiceAdjustment($month, $year);
    $adjustment->applyReferralBilledDiscount(2825, BillingUtil::$partnerBilledAmounts, 'fixed', -10.00);
} catch (Exception $e) {
    print($e->getMessage());
    error_log(basename(__FILE__) . " :: " . __LINE__ . " :: " . $e->getMessage());
}
//require_once(__DIR__ . '/includes/emailer.php');
require_once(__DIR__ . '/../../includes/emailer.php');
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
