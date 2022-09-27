<?php

	### Is user authenticated??
	if($_SESSION["auth"]!="OK")
	{
		header("location: .");
		die();
	}
	
	### If access leven is not greater than 50 redirect to home page.
	if($_SESSION["access_level"]<=50){
		header("location: index.php?action=history");
		die();
	}
	if($_SESSION["access_level"]<=50){
		die("Internal Server Error 560");
	}
	
	#die(var_export($_SESSION,1));
	
	include "../includes/functions/db.php";
	include "../includes/functions/html_form_functions.php";
	include "../includes/vars.php";
	include "common.php";
	error_reporting(E_ERROR);

	#$year = $_REQUEST["year"];
	#$month = $_REQUEST["month"];
	
	#if($year == "") $year = date("Y");
	#if($month == "") $month = date("n");
	
	$year = date("Y");
	$month = date("n") - 1;
	if($month<1)
	{
		$year--;
		$month = 12;
	}
	
	for($y=0;$y<4;$y++)
	    $arr_years[] = array(date("Y")-$y,date("Y")-$y);
	
	for($m=1;$m<13;$m++)
	    $arr_months[] = array(date("F",strtotime("$m/01/2001")),date("n",strtotime("$m/01/2001")));
	
    	#$form_year	= form_select("year",$arr_years,$year);
	#$form_month	= form_select("month",$arr_months,$month);
	#$form_submit	= form_submit("btn_submit","Change Month");

	if(isset($_REQUEST["sort_order"]))
	{
		$sort_order = $_REQUEST["sort_order"];
		$_SESSION["sort_order"]=$sort_order;
	}
	
	$sort_order 	= $_SESSION["sort_order"];
	if($sort_order=="")
		$sort_order = "company_name:asc";

	# Handle order  stuff::::
	list($OBY,$ODIR) = split(":",$sort_order);
	
	$partners = mysql_rows("SELECT * FROM webedi_tp WHERE entity_type IN ('CS','WC','IT','2B','SW','VC') ORDER BY $OBY $ODIR");
	$partners = mysql_rows("SELECT * FROM webedi30.Partners WHERE type IN ('WebEDI', 'Hosted', 'Software') AND status = 'Active' ORDER BY $OBY $ODIR;");

	#print_r($_SESSION);
?>
<HTML>
<HEAD>
<TITLE>
</TITLE>
	<!-- Load the YUI Loader script: -->
	<script src="/webedi20/js/yuloader.js"></script>
	<script>
	// Instantiate and configure Loader:
	var loader = new YAHOO.util.YUILoader({
		require: ["colorpicker", "connection","event","selector","json"],
		loadOptional: true,
		//base: "https://yui.yahooapis.com/2.6.0/build/",
		
		onSuccess: function() {
				YAHOO.util.Event.addListener(window,'focus',body_focus);
			},
		timeout: 10000,
		combine: true
	});
	loader.insert();
	</script>
</HEAD>
<BODY XXONFOCUS='body_focus();'>
	
<TEXTAREA STYLE='visibility:hidden;display:none' ID='tarea' COLS=50 ROWS=5></TEXTAREA>
<?
	print "<font style='font-family:verdana;font-size:20;font-weight:bold'>MAIN</font><BR>";
	print "<a href=index.php?action=history&year=$year&month=$month>[click here to view history]</a>";

	print "<FORM ID=\"form1\" NAME=\"form1\" METHOD=\"POST\" ONCLICK=\"\">";
	print "<INPUT TYPE=\"HIDDEN\" ID=\"sort_order\" NAME=\"sort_order\" VALUE=\"$sort_order\">";
	print "$form_year $form_month $form_submit<BR><BR>";

	$inv_order = array("asc"=>"desc","desc"=>"asc");
	$order_imgs = array("asc"=>"/images/arrow_ASC.gif","desc"=>"/images/arrow_DESC.gif");
	
	#Create default order by links
	$company_order = "<A HREF=\"\" ONCLICK=\"return change_order('company_name:{$ODIR}');\">";
	$webtpid_order = "<A HREF=\"\" ONCLICK=\"return change_order('webtpid:{$ODIR}');\">";
	
	#inverts the order for the current selected order:
	if($OBY == "webtpid") 
	{
		$webtpid_order_img = "<IMG SRC='{$order_imgs[$ODIR]}' BORDER='0'>";
		$webtpid_order = "<A HREF=\"\" ONCLICK=\"change_order('webtpid:{$inv_order[$ODIR]}');\">";
	}
	if($OBY == "company_name")
	{
		$company_order_img = "<IMG SRC='{$order_imgs[$ODIR]}' BORDER='0'>";
		$company_order = "<A HREF=\"\" ONCLICK=\"change_order('company_name:{$inv_order[$ODIR]}');\">";
	}

	print "<TABLE BORDER=1 WIDTH=500 CELLSPACING=0 CELLPADDING=3 BORDERCOLOR=\"Black\">";
	print "<TR STYLE='font-family:verdana;font-size:12;font-weight:bold'>";
	print "<TD NOWRAP VALIGN=\"TOP\"><TABLE CELLPADDING=0 CELLSPACING=0 STYLE='font-family:verdana;font-size:12;font-weight:bold'><TR><TD>{$webtpid_order}WebTPId</A>&nbsp</TD><TD>$webtpid_order_img</TD></TR></TABLE></TD>";
	print "<TD NOWRAP VALIGN=\"TOP\"><TABLE CELLPADDING=0 CELLSPACING=0 STYLE='font-family:verdana;font-size:12;font-weight:bold'><TR><TD>{$company_order}Company Name</A>&nbsp</TD><TD>$company_order_img</TD></TR></TABLE></TD>";
	print "<TD NOWRAP VALIGN=\"TOP\">EDI Traffic</TD>";
	print "<TD NOWRAP VALIGN=\"TOP\">EDI Charges</TD>";
	print "<TD NOWRAP VALIGN=\"TOP\">Other Charges</TD>";
	print "<TD NOWRAP VALIGN=\"TOP\">Total</TD>";
	print "<TD NOWRAP VALIGN=\"TOP\">&nbsp;</TD>";
	print "</TR>";
	
	foreach($partners as $partner)
	{
		unset($data);
		
		### IF status='C' Cancelled, then we look at the cancel_date field and see if we still have one left billing cycle left:
		if($partner["status"]=='C')
		{
			### If no date has been entered then we ignore this record altogether
			if($partner["cancel_date"]=="0000-00-00")
				continue;
			
			### Otherwise let's see if one last round of billing is needed:
			$cmonth = date("m",strtotime($partner["cancel_date"]));
			$cyear  = date("Y",strtotime($partner["cancel_date"]));
			$cday  = date("d",strtotime($partner["cancel_date"]));
			
			### Will compare the first of the month of the billing month, and the first of the month of the cancellation month to determine if we are to bill one last time
			### If the dates are the same then we bill, otherwise we don't ;-)
			$cFirstOfTheMonth = strtotime("$cyear-$cmonth-01");			
			$bFirstOfTheMonth = strtotime("$year-$month-01");
			
			#print date("Y-m-d",$cFirstOfTheMonth) . "<br>";
			#print date("Y-m-d",$bFirstOfTheMonth) . "<br>";
			
			if($cFirstOfTheMonth != $bFirstOfTheMonth)
				continue;
		}
		
		$userid = $partner["userid"];

		# Pull totals from monthly traffic table
		$billing = mysql_row("SELECT * FROM webedi_tp_monthly_traffic WHERE FK_tp={$partner["userid"]} AND year=$year AND month=$month");
		if($billing)
			$data = calculate_billing($billing["billing_schema"],$billing["traffic"],false);

		# Pull charges
		$charges = mysql_row("SELECT SUM(amount) AS total FROM webedi_tp_charges WHERE FK_tp={$partner["userid"]} AND status='pending'");
		$charges["total"] = number_format($charges["total"],2);
		
		$total_amount = number_format(str_replace(",","",$data["amount"]) + str_replace(",","",$charges["total"]),2);
		
		$grand_total_qty += str_replace(",","",$data["doc_qty"]);
		$grand_total_amount += str_replace(",","",$data["amount"]);
		$grand_total_charges += str_replace(",","",$charges["total"]);
		
		$links = "<A HREF='edi_traffic_view_tp.php?form_month=$month&form_year=$year&form_userid=$userid' ONCLICK='flag($userid);' TARGET='_new'>[EDIT]</A>";
		#print "Viewing: {$partner["company_name"]}:{$partner["webtpid"]}:{$partner["userid"]}<BR>";
		print "<TR ID='TR:TP:$userid' STYLE='font-family:verdana;font-size:12;font-weight:none'>";
		print "<TD ALIGN=\"CENTER\" NOWRAP>{$partner["webtpid"]}&nbsp;</TD>";
		print "<TD NOWRAP>{$partner["company_name"]} ({$partner["userid"]},{$billing["billing_schema"]})&nbsp;</TD>";
		print "<TD NOWRAP ALIGN=\"RIGHT\">{$data["doc_qty"]}&nbsp;</TD>";
		print "<TD NOWRAP ALIGN=\"RIGHT\">{$data["amount"]}&nbsp;</TD>";
		print "<TD NOWRAP ALIGN=\"RIGHT\">{$charges["total"]}&nbsp;</TD>";
		print "<TD NOWRAP ALIGN=\"RIGHT\">{$total_amount}&nbsp;</TD>";
		print "<TD>&nbsp;$links</TD>";
		print "</TR>";
	}
	
	$grand_total_qty = number_format($grand_total_qty,0);
	$grand_total_total = number_format($grand_total_amount + $grand_total_charges,2);
	$grand_total_amount = number_format($grand_total_amount,2);
	$grand_total_charges = number_format($grand_total_charges,2);

	print "<TR STYLE='font-family:verdana;font-size:10;font-weight:none'>";
	print "<TD COLSPAN='2'>TOTALS</TD>";
	print "<TD NOWRAP ALIGN=\"RIGHT\">{$grand_total_qty}&nbsp;</TD>";
	print "<TD NOWRAP ALIGN=\"RIGHT\">{$grand_total_amount}&nbsp;</TD>";
	print "<TD NOWRAP ALIGN=\"RIGHT\">{$grand_total_charges}&nbsp;</TD>";
	print "<TD NOWRAP ALIGN=\"RIGHT\">{$grand_total_total}&nbsp;</TD>";
	print "<TD>&nbsp;</TD>";
	print "</TR>";
	print "</TABLE>";
?>


<SCRIPT LANGUAGE="Javascript">
	function change_order(what)
	{
		var obj = document.getElementById("sort_order");
		if(obj)
		{
			obj.value=what;
		}
		var form = document.getElementById("form1");
		if(form)
		{
			form1.submit();
		}
		return false;
	}
	
	function flag(uid)
	{
		var obj = document.getElementById("TR:TP:"+uid);
		if(obj)
		{
			obj.style.backgroundColor="#FFCC00";
		}
		return true;
	}
	
	function body_focus()
	{
		var tarea = document.getElementById("tarea");
		tarea.value += ":::";		
		var objs = document.getElementsByTagName("tr");
		var cou = 0;
		if(objs)
		{
			for (i=0;i<objs.length;i++)
			{
				if(objs[i].id.substr(0,6) == "TR:TP:")
				{
					tarea = document.getElementById("tarea");
					var bgc = YAHOO.util.Dom.getStyle(objs[i], 'backgroundColor') ;
					
					if((bgc=="rgb(255, 204, 0)") || (bgc=="#ffcc00"))
					{
						var callback =
						{
							cache:false,
							success:function(resp)
							{
								YAHOO.util.Dom.setStyle(objs[i], 'backgroundColor', '#FFFFFF') ;
								if(objs[i])
								{
									var jsondata = YAHOO.lang.JSON.parse(resp.responseText);
									var node = YAHOO.util.Selector.query('td', objs[i]);
									node[2].innerHTML = jsondata.doc_qty + '&nbsp;';	// EDI Traffic
									node[3].innerHTML = jsondata.amount + '&nbsp;';		// EDI Charges
									node[4].innerHTML = jsondata.other_charges + '&nbsp;';	// Other Charges
									node[5].innerHTML = jsondata.total_amount + '&nbsp;';	// Total
								}
							}
						};
						tpid = objs[i].id.substr(6)
						tarea.value += ":::" + tpid;
						
						var node = YAHOO.util.Selector.query('td', objs[i]);
						node[2].innerHTML = "loading";		// EDI Traffic
						node[3].innerHTML = "loading";		// EDI Charges
						node[4].innerHTML = "loading";		// Other Charges
						node[5].innerHTML = "loading"; 		// Total
						
						YAHOO.util.Connect.asyncRequest('POST', '/billing2/', callback, "action=line&month=<?=$month?>&year=<?=$year?>&tp="+tpid); 
						return 0;
					}
				}
			}
		}
	}
	
</SCRIPT>
