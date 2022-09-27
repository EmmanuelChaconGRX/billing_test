<?php
	include "includes/db.php";
	include "includes/html_form_functions.php";
?>

<html>
<body>
<style>
.tbl1
{
	font-family: verdana;
	font-size: 12px;
	font-weight:none;
	width:900px;
	border: solid black 2px;
}
.tbl2
{
	font-family: verdana;
	font-size: 14px;
	font-weight:bold;
	background: #DDDDDD;
}
.ebmx
{
	color: red;
}
.total
{
	font-family: verdana;
	font-weight:bold;
	font-size:16px;
	color: navyblue;
	text-align: right;
}
</style>
<?php
	error_reporting(E_ERROR);

	function request($what,$def)
	{
		if(isset($_REQUEST["$what"]))
			return $_REQUEST["$what"];
		else
			return $def;
	}
	
	$year	= request("form_year",date("Y"));
	$month	= request("form_month",date("n"));

	$year = 2008;
	$month = 12;
	
	$query	= "SELECT * FROM EasyLink_Report WHERE month(inserted_datetime)=$month AND year(inserted_datetime)=$year;";
	$rows	= mysql_rows($query);

	if(!$rows)
	{
		die("Sorry no data was found for this year/month ($year/$month)");
	}
	
	print "<table class='tbl1'>
		<tr class='tbl2'>
			<td>Date</TD>
			<td>Client</TD>
			<td>TP</TD>
			<td>Dir</TD>
			<td>Network</TD>
			<td>Size</td>
			<td>Units</td>
			<td>Type</TD>
			<td>Control</td>
		</tr>";
	
	foreach($rows as $id=>$row)
	{
		$date = $row["Inserted_DateTime"];
		$tp = "{$row["TP_ISA_Id"]}/{$row["TP_ISA_Qual"]}";
		$client = "{$row["Client_ISA_Id"]}/{$row["Client_ISA_Qual"]}";
		$dir = $row["Direction"];
		$network = $row["TP_Network_Address"];
		$control = $row["Control_Number"];
		$type = $row["Msg_Type"];
		$size = $row["Msg_Size"];
		
		$units = ceil($size/1000);
		
		if($network=="elsebmx!SynCAccT")
		{
			$network = "<font class='ebmx'>$network</font>";
			$total_units_ebmx += $units;
			$total_size_ebmx += $size;
		}
		
		if($type!="997")
		{
			$total_units += $units;
			$total_size += $size;
		} else
		{
			$total_size_997 += $size;
			$total_units_997 = ceil($total_size_997/1000);
			continue;
		}
		
		print "<tr>
			<td nowrap>$date</td>
			<td>$client</td>
			<td>$tp</td>
			<td>$dir</td>
			<td>$network</td>
			<td>$size</td>
			<td>$units</td>
			<td>$type</td>
			<td>$control</td>
		</tr>";
	}
	print "</tr>
		</table>";
	
	print "<br>";
	
	print "<table class='tbl1'>";
	print "<tr class='total'>
			<td> </td>
			<td>SIZE (Bytes)</td>
			<td>Units</td>
		</tr>";
	print "<tr class='total'>
			<td>Totals Non 997: </td>
			<td>$total_size</td>
			<td>$total_units</td>
		</tr>";
	print "<tr class='total'>
			<td>Totals 997: </td>
			<td>$total_size_997</td>
			<td>$total_units_997</td>
		</tr>";
	print "<tr class='total'>
			<td>Totals EBMX: </td>
			<td>$total_size_ebmx</td>
			<td>$total_units_ebmx</td>
		</tr>";
	print "</table>";
?>

</body>
</html>
