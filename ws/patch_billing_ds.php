<?php
// Calls a Stored procedure to Patch Dropship flagging
set_time_limit(0);
ini_set('error_log', '/var/log/php-billing_process.log');
error_reporting(E_ALL);
define('LOG_LEVEL', LOG_INFO);
include __DIR__ . '/../../env.php';
include __DIR__ . '/../includes/Process.php';
include __DIR__ . '/../includes/BillingUtil.php';
include __DIR__ . '/../includes/Ansi.php';
include __DIR__ . '/../includes/Collection.php';

$pdo = BillingUtil::getConnection();
$pdo->exec('call billing.force_dropship_update();');
