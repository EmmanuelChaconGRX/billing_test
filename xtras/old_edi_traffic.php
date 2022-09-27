<?php
        include(__DIR__.'/../../env.php');
#	die(var_export($_REQUEST,true));
#	error_reporting(255);

	if($_SERVER["REMOTE_ADDR"]!="216.119.135.3" &&
		$_SERVER["REMOTE_ADDR"]!="24.242.220.181" &&
		$_SERVER["REMOTE_ADDR"]!="201.242.38.143" &&
		$_SERVER["REMOTE_ADDR"]!="201.248.41.53" &&
		substr($_SERVER["REMOTE_ADDR"],0,12)!="216.165.217.")
		die("You are not authorized to see this page.");

	include "../includes/functions/db.php";
	include "../includes/functions/html_form_functions.php";
	include "../includes/vars.php";

        print "<SCRIPT LANGUAGE=\"Javascript\" SRC=\"includes/jscripts/datepicker.js\"></SCRIPT>";
        print "<FORM NAME=\"webedi_reports\" METHOD=\"POST\">";
	
#	MS SQL
	$ms_db_host = MSSQL_HOST;
	$ms_db_user = MSSQL_USER;
	$ms_db_pass = MSSQL_PASS;
	$ms_db_name = MSSQL_DB_A;

	$ms_db = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass) or die ("Unable to connect!");
	mssql_select_db($ms_db_name) or die("Could not select DB: $ms_db_name");
	
	function mssql_value($sql)
	{
#		MS SQL
		error_reporting(E_ALL);
		$ms_db_host = MSSQL_HOST;
		$ms_db_user = MSSQL_USER;
		$ms_db_pass = MSSQL_PASS;
		$ms_db_name = MSSQL_DB_A;

		$ms_db = mssql_connect($ms_db_host, $ms_db_user, $ms_db_pass);# or die ("Unable to connect!");
		mssql_select_db($ms_db_name) or die("Could not select DB: $ms_db_name");
		$result = mssql_query($sql) or die("Error querying MSSQL:<BR>\n<B>$sql</B><BR>\n" . mssql_get_last_message());
		$row = mssql_fetch_array($result);
		
			print_r($row);
			print $row["local_id1"];
			die();
			return $row[0];

		
//		die("xxx");

	}
	
//	print mssql_value("SELECT local_id1 FROM Partners WHERxE tpid = 10");
	
	$bgcolor1="#DDDDDD";
	$bgcolor2="#FFFFFF";
	$bgcolor3="#CCCCCC";

	error_reporting(E_ERROR);
	include "includes/functions/html_form_functions.php";

	$html_form_name	= "webedi_reports";
	$datepicker		= true;

#	$str_fromdate	= $_REQUEST["txt_from_date"];
#	$str_todate	= $_REQUEST["txt_to_date"];
#	$str_tp		= $_REQUEST["txt_tp"];
#	$str_reference	= $_REQUEST["txt_reference"];

#	$frm_fromdate	= form_date("txt_from_date",$str_fromdate);
#	$frm_todate	= form_date("txt_to_date",$str_todate);
#	$frm_reference	= form_input("txt_reference",$str_reference,"SIZE=\"12\"");

#	print "<TABLE WIDTH=\"100%\" CELLSPACING=5>";
#	print "<TR><TD HEIGHT=2 BGCOLOR=\"#CCCCCC\"></TD></TR>";
#	print "<TR><TD>";
#	print "$f[2]";
#	print "From: $frm_fromdate";
#	print "&nbsp;&nbsp;&nbsp;";
#	print "To: $frm_todate";

#	$r997 = $_REQUEST["r997"];
#	print "&nbsp;&nbsp;&nbsp;Reference#: $frm_reference";
#	print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Include functional Acknowledgments (997)? ".form_select("r997",$form_arrays["yn"],$r997);
#	print "</TD></TR>";
#	print "<TR><TD HEIGHT=2 BGCOLOR=\"#CCCCCC\"></TD></TR>";
#	print "<TR><TD>";
#	print "<INPUT TYPE=\"SUBMIT\" NAME=\"txt_submit\" VALUE=\"Sent Documents\" STYLE=\"width:110;font-size:10\">";
#	print "<INPUT TYPE=\"SUBMIT\" NAME=\"txt_submit\" VALUE=\"Received Documents\" STYLE=\"width:120;font-size:10\">";
#	print "<INPUT TYPE=\"SUBMIT\" NAME=\"txt_submit\" VALUE=\"Rejected Documents\" STYLE=\"width:120;font-size:10\">";
#	print "<INPUT TYPE=\"SUBMIT\" NAME=\"txt_submit\" VALUE=\"Unacknowledged Documents\" STYLE=\"width:160;font-size:10\">";
#	print "<BR>";
#	print "</TD></TR>";
#	print "<TR><TD HEIGHT=2 BGCOLOR=\"#CCCCCC\"></TD></TR>";
#	print "</TABLE>";


	$year	= request("form_year",date("Y"));
	$month	= request("form_month",date("n"));
	$tpid	= request("form_tpid","ALL");

#	die("$tpid");
	
	$query	= "SELECT * FROM Partners ORDER BY name";
	$mrs	= mssql_query("$query") or die(mssql_message());

	function request($what,$def)
	{
	    if(isset($_REQUEST["$what"]))
		return $_REQUEST["$what"];
	    else
		return $def;
	}

	$arr_tpids[] = array("ALL","ALL");
	while ($row = mssql_fetch_array($mrs))
	{
		if($tpid==$row["tpid"])
			$local_id3=$row["local_id3"];
		$arr_tpids[] = array($row["name"],$row["tpid"]);
	}
	
	for($y=0;$y<4;$y++)
	    $arr_years[] = array(date("Y")-$y,date("Y")-$y);
	
	for($m=1;$m<13;$m++)
	    $arr_months[] = array(date("F",strtotime("$m/01/2001")),date("n",strtotime("$m/01/2001")));
	

	$form_submit	= form_submit("form_submit","Submit");	
	$form_tpid	= form_select("form_tpid",$arr_tpids,$tpid);
	$form_year	= form_select("form_year",$arr_years,$year);
	$form_month	= form_select("form_month",$arr_months,$month);
	
	print $form_tpid;
	print $form_year;
	print $form_month;
	print $form_submit;

#	$WHERE_SENT	= "FromPartner.local_id1 = $tpid ";
#	$WHERE_REC	= "ToPartner.local_id1 = $tpid ";

/*
	$QUERY = "
		SELECT 
		ContentSize,
		DTSDocuments.timestamp, 
		DTSDocuments.tpid_sender,
		DTSDocuments.tpid_recipient,
		DTSDocuments.type AS DocType, 
		DTSDocuments.ack AS ack,
		DTSEnvelopes.control AS EnvControl, 
		DTSGroups.control AS GrpControl,
		FromPartner.name AS SenderName,
		DTSEnvelopes.sender_qual AS SenderIdQual,
		DTSEnvelopes.sender AS SenderId,
		ToPartner.name AS RecipientName,
		DTSEnvelopes.recipient_qual AS RecipientIdQual,
		DTSEnvelopes.recipient AS RecipientId
		FROM (Batches 
			RIGHT JOIN (DTSEnvelopes 
				RIGHT JOIN (DTSGroups 
					RIGHT JOIN (Partners AS FromPartner
						RIGHT JOIN (Partners AS ToPartner
							RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient)
						ON FromPartner.tpid = DTSDocuments.tpid_sender)
					ON DTSGroups.id = DTSDocuments.grpid)
				ON DTSEnvelopes.id = DTSDocuments.envid)
			ON Batches.BatchID = DTSEnvelopes.mid)
		WHERE
		    DATEPART(yyyy,timestamp) = $year
		    AND DATEPART(mm,timestamp) = $month 
		    AND DTSDocuments.type<>'997' ";
*/

$webedi_ftp = mysql_value("SELECT ftpdir FROM webedi_tp WHERE userid='$local_id3' ");

$DATE_W = "(DATEPART(yyyy, created) = $year) AND (DATEPART(mm, created) = $month)";

print "<A $tpid>";
$others_ = "";
if($tpid==194)
	$edi_dir = "mmscedi";
if($tpid==388)
	$edi_dir = "dts1601";
if($tpid==377)
	$edi_dir = "dts1567";
if($tpid==353)
	$edi_dir = "dts1545"; #1563 -- Akron
if($edi_dir=="")
	$edi_dir=mysql_value("SELECT ftp_user FROM webedi_tp WHERE userid='$local_id3'");
if($tpid==121){
	$others_ = "OR BatchTitle = 'FiveRivers POP3 EDI Input Channel'";
	$rec_121_ = "SELECT * FROM Batches WHERE BatchTitle LIKE 'Packing List From MID-SHIP - Five Rivers Distribution, Van Buren, AR%' AND $DATE_W";
	#$sent_121_ = "SELECT * FROM Deliveries WHERE OutputQueue = '1073743018' AND $DATE_W";
	$sent_121_ = "SELECT created,recipients as BatchTitle, size as ContentSize, 'Truck File' as type, 'MIDSHIP' as tpname FROM Deliveries WHERE OutputQueue='1073743018' AND $DATE_W";
	$edi_dir = "dts1312";
	$webedi_ftp = "fiverivers";
}

#print "SELECT ftp_user FROM webedi_tp WHERE userid='$tpid'";
#if($tpid==178) #fanmats
#	$edi_dir = "fanmats";

		$QUERY_SENT_FTP = "
		SELECT 
			count(*) as Qty, 
			Batches.ContentSize, 
			Batches.BatchTitle, 
			Created
		FROM 
			Batches 
		WHERE 
			DATEPART(yyyy, [Created]) = $year
			AND (DATEPART(mm, [Created]) = $month)
			AND 	( BatchTitle LIKE '%/home/webedi/$webedi_ftp/%'
				OR BatchTitle LIKE '%/home/$edi_dir/%'
				OR BatchTitle LIKE '%./todts/$edi_dir.%' 
				$others_)
		GROUP BY 
			Batches.ContentSize, 
			Batches.Created, 
			BatchTitle
		ORDER BY 
			Created 
		DESC
		";
#	print "$QUERY_SENT_FTP";

	$QUERY_SENT_EDI = "
	SELECT
	    count(*) as Qty,
	    Batches.ContentSize,
	    Batches.BatchTitle,
	    Batches.Created,
	    DTSEnvelopes.tpid_sender, 
	    FromPartner.name as sender,    
	    DTSEnvelopes.tpid_recipient as tpid,
	    ToPartner.name as tpname,
	    type, 
	    DTSEnvelopes.control,
	    OutputQueue
	FROM
	    Batches
	    JOIN Deliveries
	    RIGHT OUTER JOIN DTSEnvelopes
	    RIGHT JOIN Partners AS FromPartner
	    RIGHT JOIN Partners AS ToPartner
	    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
	    ON FromPartner.tpid = DTSDocuments.tpid_sender
	    ON DTSEnvelopes.id = DTSDocuments.envid 
	    ON Deliveries.BatchID = DTSEnvelopes.mid
	    ON Batches.BatchID = Deliveries.BatchID
	WHERE
	    (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
	    AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
	    AND (DTSDocuments.type <> '997') 
	    ";

	$QUERY_REC_EDI = "
	SELECT
	    count(*) as Qty,
	    Batches.ContentSize, 
	    Batches.BatchTitle,
	    Batches.Created,
	    DTSEnvelopes.tpid_sender as tpid, 
	    FromPartner.name as tpname,
	    DTSEnvelopes.tpid_recipient,
	    ToPartner.name as recipient,
	    type, 
	    DTSEnvelopes.control,
	    OutputQueue
	FROM
	    Batches 
	    JOIN Deliveries
	    RIGHT OUTER JOIN DTSEnvelopes
	    RIGHT JOIN Partners AS FromPartner
	    RIGHT JOIN Partners AS ToPartner
	    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
	    	ON FromPartner.tpid = DTSDocuments.tpid_sender
		ON DTSEnvelopes.id = DTSDocuments.envid 
		ON Deliveries.BatchID = DTSEnvelopes.mid
		ON Batches.BatchID = Deliveries.BatchID
	    
	WHERE
	    (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
	    AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
	    AND (DTSDocuments.type <> '997') ";
	
	$GROUPBY_EDI = "
	GROUP BY
	    Batches.ContentSize, 
	    Batches.Created,
	    DTSEnvelopes.tpid_sender, 
	    DTSEnvelopes.tpid_recipient,
	    FromPartner.name,
	    ToPartner.name,
	    type, 
	    DTSEnvelopes.control,
	    BatchTitle,
	    OutputQueue
	ORDER BY 
	    Batches.Created DESC
	";
	    
	$QUERY_SENT_EDI_AKRON = "
		SELECT 
			DISTINCT B.BatchId, B.StatusFlags,
			ConfigItems.Item_Type , 
			B.Created, 
			ConfigItems.Item_Name, 
			B.BatchTitle, 
			B.ContentType, 
			B.ContentSize, 
			B.StatusText, 
			P1.name, 
			P2.name, 
			B.BatchID
		FROM 
			Batches AS B JOIN ConfigItems ON B.DataChannel = ConfigItems.Item_ID left 
			JOIN Partners AS P1 ON B.tpid_sender = P1.tpid left 
			JOIN Partners AS P2 ON B.tpid_receiver = P2.tpid  
			JOIN Deliveries AS D WITH ( INDEX(Deliveries_BatchID) ) ON B.BatchID = D.BatchID
		WHERE
			D.OutputQueue = 1073742718
			AND BatchTitle NOT LIKE '%997%'
			AND (DATEPART(yyyy, B.created) = $year) 
			AND (DATEPART(mm, B.created) = $month) 
		ORDER BY 
			B.Created DESC
	";
	
	$QUERY_REC_EDI_FANMATS_HD = "
	SELECT
	    count(*) as Qty,
	    Batches.ContentSize, 
	    Batches.BatchTitle,
	    Batches.Created,
	    DTSEnvelopes.tpid_sender as tpid, 
	    FromPartner.name as tpname,
	    DTSEnvelopes.tpid_recipient,
	    ToPartner.name as recipient,
	    type, 
	    DTSEnvelopes.control,
	    OutputQueue
	FROM
	    Batches 
	    JOIN Deliveries
	    RIGHT OUTER JOIN DTSEnvelopes
	    RIGHT JOIN Partners AS FromPartner
	    RIGHT JOIN Partners AS ToPartner
	    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
	    	ON FromPartner.tpid = DTSDocuments.tpid_sender
		ON DTSEnvelopes.id = DTSDocuments.envid 
		ON Deliveries.BatchID = DTSEnvelopes.mid
		ON Batches.BatchID = Deliveries.BatchID
	    
	WHERE
	    (FromPartner.name LIKE '%Home Depot%' OR FromPartner.name LIKE '%Academy%')
	    AND (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
	    AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
	    AND (DTSDocuments.type <> '997') ";
	
	$QUERY_REC_EDI_INNOVATIVESD = "
	SELECT
	    count(*) as Qty,
	    Batches.ContentSize, 
	    Batches.BatchTitle,
	    Batches.Created,
	    DTSEnvelopes.tpid_sender as tpid, 
	    FromPartner.name as tpname,
	    DTSEnvelopes.tpid_recipient,
	    ToPartner.name as recipient,
	    type, 
	    DTSEnvelopes.control,
	    OutputQueue
	FROM
	    Batches 
	    JOIN Deliveries
	    RIGHT OUTER JOIN DTSEnvelopes
	    RIGHT JOIN Partners AS FromPartner
	    RIGHT JOIN Partners AS ToPartner
	    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
	    	ON FromPartner.tpid = DTSDocuments.tpid_sender
		ON DTSEnvelopes.id = DTSDocuments.envid 
		ON Deliveries.BatchID = DTSEnvelopes.mid
		ON Batches.BatchID = Deliveries.BatchID
	    
	WHERE
	    (FromPartner.name LIKE '%Office Depot%' OR FromPartner.name LIKE '%X X X X X X X%')
	    AND (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
	    AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
	    AND (DTSDocuments.type <> '997') ";

#	$QUERY_SENT	= "$QUERY_SENT AND $WHERE_SENT ORDER BY DTSDocuments.timestamp DESC";
#	$QUERY_REC	= "$QUERY_SENT AND $WHERE_REC ORDER BY DTSDocuments.timestamp DESC";

	print "<BR>\n";
	$f[30]="<FONT STYLE=\"FONT-FAMILY:Verdana,Arial;FONT-SIZE:18;FONT-WEIGHT:Bold\">";
	$f[0] ="</FONT>";
	if($_REQUEST["form_submit"]=="Submit")
	{
	    if($tpid=="ALL")
	    {
		foreach($arr_tpids as $tp)
		    if($tp[0]!="ALL")
		{
			print "<HR>";
			print "<BR>$f[30]$tp[0]($tp[1])$f[0]";
			report_tp($tp[1]);
		}
	    }
	    else
		report_tp($tpid);
	}

    
function report_tp($tp)
{
	global $f;
	global $QUERY_SENT_FTP,$QUERY_SENT_EDI,$QUERY_REC_EDI,$GROUPBY_EDI,$QUERY_SENT_EDI_AKRON,$QUERY_REC_EDI_FANMATS_HD;
	global $QUERY_REC_EDI_INNOVATIVESD;
	global $webedi_ftp;
	global $rec_121_,$sent_121_;
	global $DATE_W;

	$QUERY_SENT_FTP	= "$QUERY_SENT_FTP";
	
	$QUERY_SENT_EDI	= "$QUERY_SENT_EDI AND DTSDocuments.tpid_sender = $tp $GROUPBY_EDI";
	
	$QUERY_REC_EDI	= "$QUERY_REC_EDI AND DTSDocuments.tpid_recipient = $tp $GROUPBY_EDI";
	
	$QUERY_REC_EDI_FANMATS_HD .= "AND DTSDocuments.tpid_recipient = $tp $GROUPBY_EDI";
	$QUERY_REC_EDI_INNOVATIVESD .= "AND DTSDocuments.tpid_recipient = $tp $GROUPBY_EDI";

	print "<BR>$f[20]Received EDI Messages$f[0]<BR>";
	$rec  = report_query($QUERY_REC_EDI);
	print "<BR><BR>$f[20]Sent EDI Messages$f[0]<BR>";
	$sent = report_query($QUERY_SENT_EDI);
	print "<BR><BR>$f[20]FTP Outbound Data (Unprocessed):$f[0]<BR>";
	$sent = report_query($QUERY_SENT_FTP);

	if($tp==121)
	{
		print "<BR><BR>$f[20]POP3 Inbound Data from MIDSHIP (Non EDI):$f[0]<BR>";
		$QUERY_REC_NON_EDI_121 = $rec_121_;
		$sent = report_query($QUERY_REC_NON_EDI_121);
		print "<BR><BR>$f[20]FTP Outbound Data to MIDSHIP (Non EDI):$f[0]<BR>";
		$QUERY_SENT_NON_EDI_121 = $sent_121_;
		$sent = report_query($QUERY_SENT_NON_EDI_121);
	}
	if($tp==353)
	{
		print "<BR><BR>$f[20]FTP Outbound Data TO AKRON (Duplicate):$f[0]<BR>";
		$sent = report_query($QUERY_SENT_EDI_AKRON);	
	}
	if($tp==178)
	{
		print "<BR><BR>$f[20]EDI Data from HomeDepot and Academy (Duplicate):$f[0]<BR>";
		$sent = report_query($QUERY_REC_EDI_FANMATS_HD);	
	}
	if($tp==455)
	{
		print "<BR><BR>$f[20]Inbound EDI Data from Office Depot (Duplicate):$f[0]<BR>";
		$sent = report_query($QUERY_REC_EDI_INNOVATIVESD);	
	}
}

function report_query($query)
{
	global $f,$year,$month,$tpid;
#	print "$query<BR>";

	$result = mssql_query($query) or die("Error querying MSSQL:<BR>\n<B>$query</B><BR>\n" . mssql_get_last_message());
	if(mssql_num_rows($result)>0)
	{
#		print "H";
		$tbl = "$f[3]Messages||$f[3]Title||$f[3]Size||$f[3]Date||$f[3]TP ID||$f[3]TP Name||$f[3]Type||$f[3]Control||$f[3]Units--";
		while ($row = mssql_fetch_array($result))
		{
			if(strpos($row["BatchTitle"],"/Home/DataTrans/DataTrans_850") === false){
			//*********************************************************************************
			//Calculate number of documents contained in a batch
			//*********************************************************************************
				$qty_query="	SELECT count(*)
						FROM DTSDocuments
							INNER JOIN DTSEnvelopes 
								ON DTSEnvelopes.id = DTSDocuments.envid
						WHERE  
							(DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
							AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
							AND (DTSDocuments.type <> '997')
							AND DTSDocuments.tpid_recipient = $tpid
							AND DTSEnvelopes.control='{$row["control"]}'";
				$qty_result = mssql_query($qty_query) or die("Error querying MSQL: BR>\n<B>$qty_result</B><BR>\n" . mssql_get_last_message());
				if(mssql_num_rows($qty_result)>0)
				{
					$tmp_row=mssql_fetch_array($qty_result);
					$qty_docs = $tmp_row[0];
				}
				else
					$qty_docs = 0;
	//*********************************************************************************
	
				$size = $row["ContentSize"];
				$units = ceil($size/3000) ;
				if($row["Qty"]>$units)
					$units = $row["Qty"];
	#			if($units*3000 < $size)
	#				$units++;
				$total_size += $size;
				$total += $units;
				$total_docs += $qty_docs;
				$row["Qty"]=1;
				$tbl .= "$f[2]{$row["Qty"]}:{$row["OutputQueue"]}$f[0]||$f[2]{$row["BatchTitle"]}$f[0]||$f[2]{$row["ContentSize"]} ($qty_docs)$f[0]||$f[2]{$row["Created"]}$f[0]||$f[2]{$row["tpid"]}$f[0]||$f[2]{$row["tpname"]}$f[0]||$f[2]{$row["type"]}$f[0]||$f[2]{$row["control"]}$f[0]||{ALIGN=\"RIGHT\"}$f[2]$units$f[0]--";
		
			}
		}
		$tbl .= "{COLSPAN=\"2\" ALIGN=\"RIGHT\"}$f[3]Total:$f[0]||$f[3]$total_size ($total_docs)$f[0]||{COLSPAN=\"4\" ALIGN=\"RIGHT\"}$f[3]Total Units:$f[0]||{ALIGN=\"RIGHT\"}$f[3]$total$f[0]";
		print array2tbl($tbl,"WIDTH=\"100%\"");
	}
	unset($result);
}

#				$date			= $row["timestamp"];
#				$date 			= date("d M Y g:m:sa",strtotime($date));
?>
</FORM>
