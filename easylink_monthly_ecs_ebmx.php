<?php
set_time_limit(0);

include "includes/db.php";
include "includes/html_form_functions.php";
?>

<html>
    <title></title>
    <style>
        .tbl1{
            width: 100%;
            border: solid 1px black;
            font-family: verdana;
            font-size: 12px;
        }
        .total_tbl{
            width: 400;
            border: solid 1px black;
            font-family: verdana;
            font-size: 12px;
            font-weight: bold;
        }
        .row1 td{
            border: solid 1px black;
            background: #DDDDDD;
            font-family: verdana;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }
        .ebmx{
            color: blue;
            background: #F0F0F0;
        }
        .nine97{
            color: green;
            background: #F0F0F0;
        }
        .normal{
        }
        .total
        {
            font-family: verdana;
            font-weight:bold;
            font-size:16px;
            color: navyblue;
            text-align: right;
        }
        <?php
        include(__DIR__ . '/../env.php');
        #$ms_db_host = "babelfish";
        $ms_db_host = MSSQL_HOST;
        $ms_db_user = MSSQL_USER;
        $ms_db_pass = MSSQL_PASS;
        $ms_db_name = MSSQL_DB_A;

        $ms_db = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass) or die("Unable to connect!");
        mssql_select_db($ms_db_name) or die("Could not select DB: $ms_db_name");

        error_reporting(E_ERROR);

        $year = request("form_year", date("Y"));
        $month = request("form_month", date("n"));

        function request($what, $def) {
            if (isset($_REQUEST["$what"]))
                return $_REQUEST["$what"];
            else
                return $def;
        }

        for ($y = 0; $y < 4; $y++)
            $arr_years[] = array(date("Y") - $y, date("Y") - $y);

        for ($m = 1; $m < 13; $m++)
            $arr_months[] = array(date("F", strtotime("$m/01/2001")), date("n", strtotime("$m/01/2001")));

        $form_submit = form_submit("form_submit", "Submit");
        $form_year = form_select("form_year", $arr_years, $year);
        $form_month = form_select("form_month", $arr_months, $month);
        ?>

    </style>
    <body>

        <form name="webedi_reports" method="post">
            <?php
            print $form_year;
            print $form_month;
            print $form_submit;

            $easylink_outbound = "1073742972"; # Easylink Outbound
            $easylink_inbound = "1073742975"; # Easylink Inbound

            $QUERY_EDI = "SELECT
		count(*) as Qty,
		B.ContentSize,
		B.BatchTitle,
		B.DataChannel,
		B.Created,
		E.tpid_sender,
		FromPartner.name as sender_name,
		ToPartner.name as recipient_name,
		E.tpid_recipient as tpid,
		type,
		E.control,
		sender_qual,
		sender,
		recipient_qual,
		recipient
	FROM
		Batches B
		RIGHT OUTER JOIN DTSEnvelopes E ON B.BatchID = E.mid
		RIGHT JOIN DTSDocuments ON E.id = DTSDocuments.envid
		RIGHT JOIN Partners AS FromPartner ON FromPartner.tpid = DTSDocuments.tpid_sender
		RIGHT JOIN Partners AS ToPartner ON ToPartner.tpid = DTSDocuments.tpid_recipient
	WHERE
		(
			B.DataChannel = $easylink_inbound OR
			B.BatchID IN (SELECT BatchID FROM Deliveries WHERE OutputQueue = $easylink_outbound AND DATEPART(yyyy, transmitted) = $year AND DATEPART(mm, transmitted) = $month )
		)
		AND (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year)
		AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month)
	GROUP BY
		B.ContentSize,
		B.Created,
		E.tpid_sender,
		E.tpid_recipient,
		FromPartner.name,
		ToPartner.name,
		type,
		E.control,
		BatchTitle,
		DataChannel,
		sender_qual,
		sender,
		recipient_qual,
		recipient
	ORDER BY
		B.Created";

            report_query($QUERY_EDI);

            function report_query($query) {
                $result = mssql_query($query) or die("Error querying MSSQL:<BR>\n<B>$query</B><BR>\n" . mssql_get_last_message());

                if (mssql_num_rows($result) > 0) {
                    ?>
                    <table class='tbl1'>
                        <tr class='row1'>
                            <td>Messages</td>
                            <td>Date</td>
                            <td>Sender</td>
                            <td>Sender ID</td>
                            <td>Receiver</td>
                            <td>Receiver ID</td>
                            <td>Type</td>
                            <td>Size</td>
                            <td>Units</td>
                            <td>Control</td>
                        </tr>
        <?php
        while ($row = mssql_fetch_array($result)) {
            if (strpos($row["BatchTitle"], "/Home/DataTrans/DataTrans_850") === false)
                if (false || $row["sender"] == "FL856F" || $row["sender"] == "FL862F" || $row["recipient"] == "FL856F" || $row["recipient"] == "FL862F") {
                    $size = $row["ContentSize"];
                    $units = ceil($size / 1000);
                    if ($row["Qty"] > $units)
                        $units = $row["Qty"];

                    $total_units += $units;
                    $total_size += $row["ContentSize"];

                    $rowclass = "normal";

                    if (false || $row["sender"] == "FL856F" || $row["sender"] == "FL862F" || $row["recipient"] == "FL856F" || $row["recipient"] == "FL862F") {
                        $rowclass = 'ebmx';
                        $total_size_ebmx += $row["ContentSize"];
                        $total_units_ebmx += $units;
                    }
                    if ($row["type"] == "997") {
                        $rowclass = 'nine97';
                        $total_size_997 += $row["ContentSize"];
                        #continue;
                    }

                    print "
				<tr class='$rowclass'>
					<td>{$row["Qty"]}</td>
					<td>{$row["Created"]}</td>
					<td>{$row["sender_name"]}</td>
					<td>{$row["sender_qual"]}/{$row["sender"]}</td>
					<td>{$row["recipient_name"]}</td>
					<td>{$row["recipient_qual"]}/{$row["recipient"]}</td>
					<td>{$row["type"]}</td>
					<td>{$row["ContentSize"]}</td>
					<td>{$units}</td>
					<td>{$row["control"]}</td>
				<tr>";
                }
        }

        print "
			<tr>
				<td colspan='8' align='right'>Total Units:</td>
				<td align='right'>$total_units</td>
			</tr>
		</table>";

        $total_units_997 = ceil($total_size_997 / 1000);

        print "<br>";

        print "<table class='total_tbl'>";
        print "<tr class='row1'>
				<td> </td>
				<td>SIZE (Bytes)</td>
				<td>Units</td>
			</tr>";
        print "<tr class='normal'>
				<td>Totals Non 997: </td>
				<td align='right'>$total_size</td>
				<td align='right'>$total_units</td>
			</tr>";
        print "<tr class='nine97'>
				<td>Totals 997: </td>
				<td align='right'>$total_size_997</td>
				<td align='right'>$total_units_997</td>
			</tr>";
        print "<tr class='embx'>
				<td>Totals EBMX: </td>
				<td align='right'>$total_size_ebmx</td>
				<td align='right'>$total_units_ebmx</td>
			</tr>";
        print "</table>";
    }
    unset($result);
}
?>
    </body>
</html>
