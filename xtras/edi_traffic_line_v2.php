<?php
	ob_clean();
	
	if($_SESSION["auth"]!="OK")
	{
		//header("location: .");
		die("SESSIONEXPIRED");
	}
	
	include "../includes/functions/db.php";
	include "../includes/functions/html_form_functions.php";
	include "../includes/vars.php";
	include "common.php";
	error_reporting(E_ERROR);

	$year = $_REQUEST["year"];
	$month = $_REQUEST["month"];
	$tp = $_REQUEST["tp"];
	
	$partners = mysql_rows("SELECT * FROM webedi_tp WHERE userid = $tp");

	foreach($partners as $partner)
	{
		unset($data);
		
		$userid = $partner["userid"];

		get_url("https://www.datatranswebedi.com/edi_traffic_load.php?userid=$userid");
		
		# Pull totals from monthly traffic table
		$billing = mysql_row("SELECT * FROM webedi_tp_monthly_traffic WHERE FK_tp=$userid AND year=$year AND month=$month");
		if($billing)
		{
			$data = calculate_billing($billing["billing_schema"],$billing["traffic"],false);
		}

		# Pull charges
		$charges = mysql_row("SELECT SUM(amount) AS total FROM webedi_tp_charges WHERE FK_tp={$partner["userid"]} AND status='pending'");
		$charges["total"] = number_format($charges["total"],2);
		
		$total_amount = number_format(str_replace(",","",$data["amount"]) + str_replace(",","",$charges["total"]),2);
		
		$grand_total_qty += str_replace(",","",$data["doc_qty"]);
		$grand_total_amount += str_replace(",","",$data["amount"]);
		$grand_total_charges += str_replace(",","",$charges["total"]);
		
		$links = "<A HREF='edi_traffic_view_tp.php?form_month=$month&form_year=$year&form_userid=$userid' ONCLICK='flag($userid);' TARGET='_new'>[EDIT]</A>";
		#print "Viewing: {$partner["company_name"]}:{$partner["webtpid"]}:{$partner["userid"]}<BR>";
		#print "<TD ALIGN=\"CENTER\" NOWRAP>{$partner["webtpid"]}&nbsp;</TD>";
		#print "<TD NOWRAP>{$partner["company_name"]} ({$partner["userid"]},{$billing["billing_schema"]})&nbsp;</TD>";
		#print "<TD NOWRAP ALIGN=\"RIGHT\">{$data["doc_qty"]}&nbsp;</TD>";
		#print "<TD NOWRAP ALIGN=\"RIGHT\">{$data["amount"]}&nbsp;</TD>";
		#print "<TD NOWRAP ALIGN=\"RIGHT\">{$charges["total"]}&nbsp;</TD>";
		#print "<TD NOWRAP ALIGN=\"RIGHT\">{$total_amount}&nbsp;</TD>";
		#print "<TD>&nbsp;$links</TD>";
		
		$r["doc_qty"]		= $data["doc_qty"];
		$r["amount"]		= $data["amount"];
		$r["other_charges"]	= $charges["total"];
		$r["total_amount"]	= $total_amount;
		
		dl("json.so");
		print json_encode($r);
	}
?>
