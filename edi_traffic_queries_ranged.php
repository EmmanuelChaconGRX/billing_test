<?php

if ($tpid == 194) {
    $edi_dir = "mmscedi";
}
if ($tpid == 388) {
    $edi_dir = "dts1601";
}
if ($tpid == 377) {
    $edi_dir = "dts1567";
}
if ($tpid == 295) {
    $edi_dir = "alldata";
}
if ($tpid == 353) {
    $edi_dir = "dts1545"; #1563 -- Akron
}
if ($edi_dir == "") {
    $edi_dir = mysql_value("SELECT ftp_user FROM webedi_tp WHERE userid='$userid'");
}
if ($tpid == 121) {
    $others_ = "OR BatchTitle = 'FiveRivers POP3 EDI Input Channel'";
    $rec_121_ = "SELECT * FROM Batches WHERE BatchTitle LIKE 'Packing List From MID-SHIP - Five Rivers Distribution, Van Buren, AR%' AND $DATE_W";
    #$sent_121_ = "SELECT * FROM Deliveries WHERE OutputQueue = '1073743018' AND $DATE_W";
    $sent_121_ = "SELECT created,recipients as BatchTitle, size as ContentSize, 'Truck File' as type, 'MIDSHIP' as tpname FROM Deliveries WHERE OutputQueue='1073743018' AND $DATE_W";
    $edi_dir = "dts1312";
    $webedi_ftp = "fiverivers";
}

### QUERY BY Channel ONLY (for non EDI data)
$QUERY_BY_CHANNEL = "
		SELECT
			COUNT(*) AS Qty, 
			ContentSize, 
			BatchTitle, 
			Created
		FROM
			Batches INNER JOIN DTSBilling.dbo.DTS_ECS_Channels c ON DataChannel=c.item_id
		WHERE
			c.tpid = $tpid
			AND c.EDI = 0
			$DATE_W_CREATED 
		GROUP BY ContentSize, Created, BatchTitle
		ORDER BY Created DESC";

### QUERY BY Channel PLUS EDI recipient
$QUERY_BY_CHANNEL_EDI = "
		SELECT
		    count(*) as Qty,
		    Batches.ContentSize, 
		    Batches.BatchTitle,
		    Batches.Created,
		    DTSEnvelopes.tpid_sender as tpid,
		    DTSEnvelopes.id as env_id,
		    FromPartner.name as tpname,
		    DTSEnvelopes.tpid_recipient,
		    ToPartner.name as recipient,
		    type, 
		    DTSEnvelopes.control
		FROM
		    Batches
		    INNER JOIN DTSBilling.dbo.DTS_ECS_Channels c ON DataChannel=c.item_id
		    RIGHT OUTER JOIN DTSEnvelopes
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
			ON FromPartner.tpid = DTSDocuments.tpid_sender
			ON DTSEnvelopes.id = DTSDocuments.envid 
			ON Batches.BatchID = DTSEnvelopes.mid
		WHERE
		    c.tpid = $tpid
		    AND c.EDI = 1
		    AND $DATE_W_TIMESTAMP 
		    AND (DTSDocuments.type <> '997') ";

$QUERY_BY_OUTPUTQUEUE = "
		SELECT 
			COUNT(*) as Qty, Size as ContentSize,BatchTitle, Batches.Created
		FROM 
			Deliveries 
			INNER JOIN DTSBilling.dbo.DTS_ECS_Channels c ON OutputQueue=c.item_id
			INNER JOIN Batches ON Batches.BatchID = Deliveries.BatchID
		WHERE 
			1=1
			AND EDI = 0
			AND tpid = $tpid
			AND $DATE_W_CREATED
			AND transmitted IS NOT NULL
		GROUP BY size, batches.Created, BatchTitle
		ORDER BY batches.Created DESC";

$QUERY_SENT_FTP = "
		SELECT 
			count(*) as Qty, 
			Batches.ContentSize, 
			Batches.BatchTitle, 
			Created
		FROM 
			Batches 
		WHERE 
			$DATE_W_CREATED
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
		DESC";

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
		    DTSEnvelopes.control
		FROM
		    Batches
		    RIGHT OUTER JOIN DTSEnvelopes
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
		    ON FromPartner.tpid = DTSDocuments.tpid_sender
		    ON DTSEnvelopes.id = DTSDocuments.envid 
		    ON Batches.BatchID = DTSEnvelopes.mid
		WHERE
		    $DATE_W_TIMESTAMP 
		    AND (DTSDocuments.type <> '997') ";

$QUERY_REC_EDI = "
		SELECT
		    count(*) as Qty,
		    Batches.ContentSize, 
		    Batches.BatchTitle,
		    Batches.Created,
		    DTSEnvelopes.tpid_sender as tpid,
		    DTSEnvelopes.id as env_id,
		    FromPartner.name as tpname,
		    DTSEnvelopes.tpid_recipient,
		    ToPartner.name as recipient,
		    type, 
		    DTSEnvelopes.control
		FROM
		    Batches 
		    RIGHT OUTER JOIN DTSEnvelopes
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
			ON FromPartner.tpid = DTSDocuments.tpid_sender
			ON DTSEnvelopes.id = DTSDocuments.envid 
			ON Batches.BatchID = DTSEnvelopes.mid
		WHERE
		    $DATE_W_TIMESTAMP 
		    AND (DTSDocuments.type <> '997') ";

$GROUPBY_EDI = "
		GROUP BY
		    Batches.ContentSize, 
		    Batches.Created,
		    DTSEnvelopes.tpid_sender, 
		    DTSEnvelopes.tpid_recipient,
		    DTSEnvelopes.id,
		    FromPartner.name,
		    ToPartner.name,
		    type, 
		    DTSEnvelopes.control,
		    BatchTitle
		ORDER BY 
		    Batches.Created DESC";

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
			B.Created DESC";

$QUERY_REC_EDI_THILL_850s_CC = " 
		SELECT
			count(*) as Qty, 
			Batches.DataChannel,
			Batches.ContentSize, 
			Batches.BatchTitle, 
			Batches.Created, 
			DTSEnvelopes.tpid_sender as tpid, 
			DTSEnvelopes.id as env_id, 
			FromPartner.name as tpname, 
			DTSEnvelopes.tpid_recipient, 
			ToPartner.name as recipient, 
			type, 
			DTSEnvelopes.control 
		FROM 
			Batches 
			RIGHT OUTER JOIN DTSEnvelopes 
			RIGHT JOIN Partners AS FromPartner 
			RIGHT JOIN Partners AS ToPartner 
			RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient 
			ON FromPartner.tpid = DTSDocuments.tpid_sender 
			ON DTSEnvelopes.id = DTSDocuments.envid 
			ON Batches.BatchID = DTSEnvelopes.mid 
		WHERE 
			1 = 1
			AND (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
			AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
			AND (DTSDocuments.type = '850')
			AND DTSDocuments.tpid_recipient = {:tp:}
		GROUP BY 
			Batches.DataChannel,
			Batches.ContentSize, 
			Batches.Created, 
			DTSEnvelopes.tpid_sender, 
			DTSEnvelopes.tpid_recipient, 
			DTSEnvelopes.id, 
			FromPartner.name, 
			ToPartner.name, 
			type, 
			DTSEnvelopes.control, 
			BatchTitle 
		ORDER BY 
			Batches.Created DESC";

$QUERY_REC_EDI_NOVA_CC = "
		SELECT
			count(*) as Qty, 
			Batches.DataChannel,
			Batches.ContentSize, 
			Batches.BatchTitle, 
			Batches.Created, 
			DTSEnvelopes.tpid_sender as tpid, 
			DTSEnvelopes.id as env_id, 
			FromPartner.name as tpname, 
			DTSEnvelopes.tpid_recipient, 
			ToPartner.name as recipient, 
			type, 
			DTSEnvelopes.control 
		FROM 
			Batches 
			RIGHT OUTER JOIN DTSEnvelopes 
			RIGHT JOIN Partners AS FromPartner 
			RIGHT JOIN Partners AS ToPartner 
			RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient 
			ON FromPartner.tpid = DTSDocuments.tpid_sender 
			ON DTSEnvelopes.id = DTSDocuments.envid 
			ON Batches.BatchID = DTSEnvelopes.mid 
		WHERE 
			1 = 1
			--AND DataChannel = '1073742975'
			AND (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
			AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
			AND (DTSDocuments.type = '850' OR DTSDocuments.type = '852')
			AND DTSDocuments.tpid_recipient = 840 
		GROUP BY 
			Batches.DataChannel,
			Batches.ContentSize, 
			Batches.Created, 
			DTSEnvelopes.tpid_sender, 
			DTSEnvelopes.tpid_recipient, 
			DTSEnvelopes.id, 
			FromPartner.name, 
			ToPartner.name, 
			type, 
			DTSEnvelopes.control, 
			BatchTitle 
		ORDER BY 
			Batches.Created DESC";

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
		    DTSEnvelopes.control
		FROM
		    Batches 
		    RIGHT OUTER JOIN DTSEnvelopes
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
			ON FromPartner.tpid = DTSDocuments.tpid_sender
			ON DTSEnvelopes.id = DTSDocuments.envid
			ON Batches.BatchID = DTSEnvelopes.mid
		    
		WHERE
		    (FromPartner.name LIKE '%Home Depot%' OR FromPartner.name LIKE '%Academy%')
		    AND (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
		    AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
		    AND (DTSDocuments.type <> '997') ";

$QUERY_REC_EDI_MMFASTENERS_CC = "
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
		    DTSEnvelopes.control
		FROM
		    Batches 
		    RIGHT OUTER JOIN DTSEnvelopes
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
			ON FromPartner.tpid = DTSDocuments.tpid_sender
			ON DTSEnvelopes.id = DTSDocuments.envid
			ON Batches.BatchID = DTSEnvelopes.mid
		    
		WHERE
		    DTSDocuments.tpid_sender = 627
		    AND (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
		    AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
		    AND (DTSDocuments.type <> '997')
		";

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
		    DTSEnvelopes.control
		FROM
		    Batches 
		    RIGHT OUTER JOIN DTSEnvelopes
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments ON ToPartner.tpid = DTSDocuments.tpid_recipient
			ON FromPartner.tpid = DTSDocuments.tpid_sender
			ON DTSEnvelopes.id = DTSDocuments.envid 
			ON Batches.BatchID = DTSEnvelopes.mid
		    
			WHERE
			(
				FromPartner.name LIKE '%Office Depot%'
				OR FromPartner.name LIKE '%Target%'
				OR FromPartner.name LIKE '%Office Max%'
				OR FromPartner.name LIKE '%Staples%'
				OR FromPartner.name LIKE '%X X X X X X X%'
			)
			AND (DATEPART(yyyy, DTSDocuments.[timestamp]) = $year) 
			AND (DATEPART(mm, DTSDocuments.[timestamp]) = $month) 
			AND (DTSDocuments.type <> '997') ";
?>
