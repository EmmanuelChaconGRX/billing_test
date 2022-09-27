<?php
	$auth = false;
	$good_ips = array("216.165.217.","98.200.186.","98.200.148.","98.200.204.","69.15.118.");
	foreach($good_ips as $ip)
	{
		if(substr($_SERVER["REMOTE_ADDR"],0,strlen($ip))==$ip)
			$auth = true;
	}
	if(!$auth)
		die("Unauthorized access");
	
	include "../includes/functions/db.php";
	include "../includes/functions/html_form_functions.php";
	include "../includes/vars.php";
	include "common.php";
	error_reporting(E_ERROR);

	$year		= date("Y");			#Current year
	$month		= 3;#date("n");			#Current month
	
	$bills = mysql_rows("SELECT * FROM webedi_tp_monthly_traffic WHERE month=$month AND year=$year");
	
	foreach($bills as $bill)
	{
		$partner = mysql_row("SELECT * FROM webedi_tp WHERE userid={$bill["FK_tp"]}");
		print "Viewing: {$partner["company_name"]}:{$partner["webtpid"]}:{$partner["userid"]}<BR>";
		calculate_billing($bill["billing_schema"],$bill["traffic"]);
		print "<HR><BR>";
	}
?>
