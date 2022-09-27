/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  javier
 * Created: Apr 13, 2020
 */

-- <PRE><HR>## Query:<br>
		SELECT
ID                    b.BatchId,
Flag                    b.UserCreated,
QTY		    count(*) as Qty,
SIZE		    b.ContentSize,
TITLE		    b.BatchTitle,
DATE		    b.Created,
SENDER		    DTSEnvelopes.tpid_sender as tpid,
ENVELOPE		    DTSEnvelopes.id as env_id,
TPNAME1		    FromPartner.name as tpname,
RECEIVER		    DTSEnvelopes.tpid_recipient,
TPNAME2		    ToPartner.name as recipient,
TYPE		    d.type,
CONTROL		    DTSEnvelopes.control
		FROM
		    Batches b WITH (NOLOCK)
		    RIGHT OUTER JOIN DTSEnvelopes WITH (NOLOCK)
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments d WITH (NOLOCK) ON ToPartner.tpid = d.tpid_recipient
			ON FromPartner.tpid = d.tpid_sender
			ON DTSEnvelopes.id = d.envid
			ON b.BatchID = DTSEnvelopes.mid
		WHERE
		    (DATEPART(yyyy, d.[timestamp]) = 2020)
		    AND (DATEPART(mm, d.[timestamp]) = 3)

		    AND (d.type <> '997')  AND d.tpid_recipient = 1105
		GROUP BY
                    b.BatchId,
                    b.UserCreated,
		    b.ContentSize,
		    b.Created,
		    DTSEnvelopes.tpid_sender,
		    DTSEnvelopes.tpid_recipient,
		    DTSEnvelopes.id,
		    FromPartner.name,
		    ToPartner.name,
		    d.type,
		    DTSEnvelopes.control,
		    b.BatchTitle
		ORDER BY
		    b.BatchId DESC;

-- <HR><PRE><HR>## Query:<br>
		SELECT
ID                    b.BatchId,
FLAG                    b.UserCreated,
QTY		    count(*) as Qty,
SIZE		    b.ContentSize,
TITLE		    b.BatchTitle,
DATE		    b.Created,
SENDER		    DTSEnvelopes.tpid_sender,
TPNAME1		    FromPartner.name as sender,
RECEV		    DTSEnvelopes.tpid_recipient as tpid,
TPNAME2		    ToPartner.name as tpname,
TYPE		    d.type,
CONTROL		    DTSEnvelopes.control
		FROM
		    Batches b WITH (NOLOCK)
		    RIGHT OUTER JOIN DTSEnvelopes WITH (NOLOCK)
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments d WITH (NOLOCK) ON ToPartner.tpid = d.tpid_recipient
		    ON FromPartner.tpid = d.tpid_sender
		    ON DTSEnvelopes.id = d.envid
		    ON b.BatchID = DTSEnvelopes.mid
		WHERE
		    (DATEPART(yyyy, d.[timestamp]) = 2020)
		    AND (DATEPART(mm, d.[timestamp]) = 3)

		    AND (d.type <> '997')  AND d.tpid_sender = 1105  AND b.DataChannel NOT IN (1073742068)
		GROUP BY
                    b.BatchId,
                    b.UserCreated,
		    b.ContentSize,
		    b.Created,
		    DTSEnvelopes.tpid_sender,
		    DTSEnvelopes.tpid_recipient,
		    DTSEnvelopes.id,
		    FromPartner.name,
		    ToPartner.name,
		    d.type,
		    DTSEnvelopes.control,
		    b.BatchTitle
		ORDER BY
		    b.BatchId DESC;
-- <HR><PRE><HR>## Query:<br>
		SELECT
ID                     b.BatchId,
FLAG                    b.UserCreated,
QTY			COUNT(*) as Qty,
SIZE                    b.Size as ContentSize,
TITLE                    b.BatchTitle, 
DATE                    b.Created

		FROM
			Deliveries de WITH (NOLOCK)
			INNER JOIN DTSBilling.dbo.DTS_ECS_Channels c ON de.OutputQueue=c.item_id
			INNER JOIN Batches b WITH (NOLOCK) ON b.BatchID = de.BatchID
		WHERE
			1=1
			AND EDI = 0
			AND tpid = 1105
			AND (DATEPART(yyyy, b.Created) = 2020) AND (DATEPART(mm, b.Created) = 3)
			AND transmitted IS NOT NULL
		GROUP BY b.BatchId,size, b.Created, b.BatchTitle, b.UserCreated
		ORDER BY b.Created DESC;
-- <HR><PRE><HR>## Query:<br>
		SELECT
ID                        b.BatchId,
FLAG                        b.UserCreated,
QTY			COUNT(*) AS Qty,
SIZE			b.ContentSize,
TITLE			b.BatchTitle,
DATE			b.Created

		FROM
			Batches b WITH (NOLOCK) INNER JOIN DTSBilling.dbo.DTS_ECS_Channels c ON b.DataChannel=c.item_id
		WHERE
			c.tpid = 1105
			AND c.EDI = 0
			AND (DATEPART(yyyy, b.Created) = 2020) AND (DATEPART(mm, b.Created) = 3)
			AND b.ContentSize >0
		GROUP BY b.BatchId,b.ContentSize, b.Created, b.BatchTitle, b.UserCreated
		ORDER BY b.Created DESC;
-- <HR><PRE><HR>## Query:<br>
		SELECT b.BatchId,
                        b.UserCreated,
			count(*) as Qty,
			b.ContentSize,
			b.BatchTitle,
			b.Created
		FROM
			Batches b WITH (NOLOCK)
		WHERE
			DATEPART(yyyy, b.[Created]) = 2020
			AND (DATEPART(mm, b.[Created]) = 3)
			AND 	( b.BatchTitle LIKE '%/home/webedi//%'
				OR b.BatchTitle LIKE '%/home//%'
				OR b.BatchTitle LIKE '%./todts/.%'
				)
		GROUP BY
			b.BatchId,
                        b.ContentSize, 
                        b.UserCreated,
			b.Created,
			b.BatchTitle
		ORDER BY
			b.Created
		DESC;
-- <HR><PRE><HR>## Query:<br>
		SELECT 
ID                        b.BatchId,
FLAG                        b.UserCreated,
QTY			count(*) as Qty,
SIZE			b.ContentSize,
TITLE			b.BatchTitle,
DATE			b.Created
		FROM
			Batches b WITH (NOLOCK)
		WHERE
			DATEPART(yyyy, b.[Created]) = 2020
			AND (DATEPART(mm, b.[Created]) = 3)
			AND 	( b.BatchTitle LIKE '%/home/webedi//%'
				OR b.BatchTitle LIKE '%/home//%'
				OR b.BatchTitle LIKE '%./todts/.%'
				)
		GROUP BY
			b.BatchId,b.ContentSize, b.UserCreated,
			b.Created,
			b.BatchTitle
		ORDER BY
			b.Created
		DESC;
-- <HR><PRE><HR>## Query:<br>
-- <HR><PRE><HR>## Query:<br>
-- <HR><PRE><HR>## Query:<br>
		SELECT
			DISTINCT 
                        B.BatchId as [ID], 
                        B.StatusFlags,B.UserCreated as [FLAG],
			ConfigItems.Item_Type as [CHANTYPE],
			B.Created as [DATE],
			ConfigItems.Item_Name as [CHANNAME],
			B.BatchTitle as [TITLE],
			B.ContentType as [CHANMIME],
			B.ContentSize as [SIZE],
			B.StatusText as [STATUS],
			P1.name as [TPNAME1],
			P2.name as [TPNAME2]
		FROM
			Batches AS B JOIN ConfigItems ON B.DataChannel = ConfigItems.Item_ID left
			JOIN Partners AS P1 ON B.tpid_sender = P1.tpid left
			JOIN Partners AS P2 ON B.tpid_receiver = P2.tpid
			JOIN Deliveries AS D WITH ( INDEX(Deliveries_BatchID) ) ON B.BatchID = D.BatchID
		WHERE
			D.OutputQueue = 1073742718
			AND BatchTitle NOT LIKE '%997%'
			AND (DATEPART(yyyy, B.created) = 2020)
			AND (DATEPART(mm, B.created) = 3)
		ORDER BY
			B.Created DESC;
-- <HR><PRE><HR>## Query:<br>
		SELECT
ID                    b.BatchId,
FLAG                    b.UserCreated,
QTY		    count(*) as Qty,
SIZE		    b.ContentSize,
TITLE		    b.BatchTitle,
DATE		    b.Created,
SENDER		    DTSEnvelopes.tpid_sender as tpid,
TPNAME1		    FromPartner.name as tpname,
RECEV		    DTSEnvelopes.tpid_recipient,
TPNAME2		    ToPartner.name as recipient,
TYPE		    d.type,
CONTROL		    DTSEnvelopes.control
		FROM
		    Batches AS b
		    RIGHT OUTER JOIN DTSEnvelopes
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments AS d ON ToPartner.tpid = d.tpid_recipient
			ON FromPartner.tpid = d.tpid_sender
			ON DTSEnvelopes.id = d.envid
			ON b.BatchID = DTSEnvelopes.mid

		WHERE
		    (FromPartner.name LIKE '%Home Depot%' OR FromPartner.name LIKE '%Academy%')
		    AND (DATEPART(yyyy, d.[timestamp]) = 2020)
		    AND (DATEPART(mm, d.[timestamp]) = 3)
		    AND (d.type <> '997') AND d.tpid_recipient = 1105
		GROUP BY
                    b.BatchId,
                    b.UserCreated,
		    b.ContentSize,
		    b.Created,
		    DTSEnvelopes.tpid_sender,
		    DTSEnvelopes.tpid_recipient,
		    DTSEnvelopes.id,
		    FromPartner.name,
		    ToPartner.name,
		    d.type,
		    DTSEnvelopes.control,
		    b.BatchTitle
		ORDER BY
		    b.Created DESC;
-- <HR><PRE><HR>## Query:<br>
		SELECT
ID                    b.BatchId,
FLAG                    b.UserCreated,
QTY		    count(*) as Qty,
SIZE		    b.ContentSize,
TITLE		    b.BatchTitle,
DATE		    b.Created,
SENDER		    DTSEnvelopes.tpid_sender as tpid,
TPNAME1		    FromPartner.name as tpname,
RECEV		    DTSEnvelopes.tpid_recipient,
TPNAME2		    ToPartner.name as recipient,
TYPE		    d.type,
CONTROL		    DTSEnvelopes.control
		FROM
		    Batches AS b
		    RIGHT OUTER JOIN DTSEnvelopes
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments AS d ON ToPartner.tpid = d.tpid_recipient
			ON FromPartner.tpid = d.tpid_sender
			ON DTSEnvelopes.id = d.envid
			ON b.BatchID = DTSEnvelopes.mid

			WHERE
			(
				FromPartner.name LIKE '%Office Depot%'
				OR FromPartner.name LIKE '%Target%'
				OR FromPartner.name LIKE '%Office Max%'
				OR FromPartner.name LIKE '%Staples%'
				OR FromPartner.name LIKE '%X X X X X X X%'
			)
			AND (DATEPART(yyyy, d.[timestamp]) = 2020)
			AND (DATEPART(mm, d.[timestamp]) = 3)
			AND (DTSDocuments.type <> '997') AND d.tpid_recipient = 1105
		GROUP BY
                    b.BatchId,
                    b.UserCreated,
		    b.ContentSize,
		    b.Created,
		    DTSEnvelopes.tpid_sender,
		    DTSEnvelopes.tpid_recipient,
		    DTSEnvelopes.id,
		    FromPartner.name,
		    ToPartner.name,
		    d.type,
		    DTSEnvelopes.control,
		    b.BatchTitle
		ORDER BY
		    Batches.Created DESC;
-- <HR><PRE><HR>## Query:<br>
		SELECT
ID                    b.BatchId,
FLAG                    b.UserCreated,
QTY		    count(*) as Qty,
SIZE		    b.ContentSize,
TITLE		    b.BatchTitle,
DATE		    b.Created,
SENDER		    DTSEnvelopes.tpid_sender as tpid,
TPNAME1		    FromPartner.name as tpname,
RECV		    DTSEnvelopes.tpid_recipient,
TPNAME2		    ToPartner.name as recipient,
TYPE		    d.type,
CONTROL		    DTSEnvelopes.control
		FROM
		    Batches AS b
		    RIGHT OUTER JOIN DTSEnvelopes
		    RIGHT JOIN Partners AS FromPartner
		    RIGHT JOIN Partners AS ToPartner
		    RIGHT JOIN DTSDocuments AS d ON ToPartner.tpid = d.tpid_recipient
			ON FromPartner.tpid = d.tpid_sender
			ON DTSEnvelopes.id = d.envid
			ON b.BatchID = DTSEnvelopes.mid

		WHERE
		    d.tpid_sender = 627
                    AND b.UserCreated IS NULL
		    AND (DATEPART(yyyy, d.[timestamp]) = 2020)
		    AND (DATEPART(mm, d.[timestamp]) = 3)
		    AND (d.type <> '997')
		AND d.tpid_recipient = 1105
		GROUP BY
                    b.BatchId,
                    b.UserCreated,
		    b.ContentSize,
		    b.Created,
		    DTSEnvelopes.tpid_sender,
		    DTSEnvelopes.tpid_recipient,
		    DTSEnvelopes.id,
		    FromPartner.name,
		    ToPartner.name,
		    d.type,
		    DTSEnvelopes.control,
		    b.BatchTitle
		ORDER BY
		    b.Created DESC;
-- <HR><PRE><HR>## Query:<br>
		SELECT
ID                        b.BatchId,
FLAG                        b.UserCreated,
QTY			count(*) as Qty,
CHAN			b.DataChannel,
SIZE			b.ContentSize,
TITLE			b.BatchTitle,
DATE			b.Created,
SENDER			DTSEnvelopes.tpid_sender as tpid,
ENVELOPE			DTSEnvelopes.id as env_id,
TPNAME1			FromPartner.name as tpname,
RECV			DTSEnvelopes.tpid_recipient,
TPNAME2			ToPartner.name as recipient,
TYPE			d.type,
CONTROL			DTSEnvelopes.control
		FROM
			Batches AS b
			RIGHT OUTER JOIN DTSEnvelopes
			RIGHT JOIN Partners AS FromPartner
			RIGHT JOIN Partners AS ToPartner
			RIGHT JOIN DTSDocuments AS d ON ToPartner.tpid = d.tpid_recipient
			ON FromPartner.tpid = d.tpid_sender
			ON DTSEnvelopes.id = d.envid
			ON b.BatchID = DTSEnvelopes.mid
		WHERE
			1 = 1
			--AND DataChannel = '1073742975'
			AND (DATEPART(yyyy, d.[timestamp]) = 2020)
			AND (DATEPART(mm, d.[timestamp]) = 3)
			AND (d.type = '850' OR d.type = '852')
			AND d.tpid_recipient = 840
		GROUP BY
                        b.BatchId,
                        b.UserCreated,
			b.DataChannel,
			b.ContentSize,
			b.Created,
			DTSEnvelopes.tpid_sender,
			DTSEnvelopes.tpid_recipient,
			DTSEnvelopes.id,
			FromPartner.name,
			ToPartner.name,
			d.type,
			DTSEnvelopes.control,
			b.BatchTitle
		ORDER BY
			b.Created DESC;
-- <HR><PRE><HR>## Query:<br>
		SELECT
ID                        b.BatchId,
FLAG                        b.UserCreated,
QTY			count(*) as Qty,
CHAN			b.DataChannel,
SIZE			b.ContentSize,
TITLE			b.BatchTitle,
DATE			b.Created,
SENDER			DTSEnvelopes.tpid_sender as tpid,
ENVELOPE			DTSEnvelopes.id as env_id,
TPNAME1			FromPartner.name as tpname,
RECV			DTSEnvelopes.tpid_recipient,
TPNAME2			ToPartner.name as recipient,
TYPE			d.type,
CONTROL			DTSEnvelopes.control
		FROM
			Batches AS b
			RIGHT OUTER JOIN DTSEnvelopes
			RIGHT JOIN Partners AS FromPartner
			RIGHT JOIN Partners AS ToPartner
			RIGHT JOIN DTSDocuments AS d ON ToPartner.tpid = d.tpid_recipient
			ON FromPartner.tpid = d.tpid_sender
			ON DTSEnvelopes.id = d.envid
			ON b.BatchID = DTSEnvelopes.mid
		WHERE
			1 = 1
			AND (DATEPART(yyyy, d.[timestamp]) = 2020)
			AND (DATEPART(mm, d.[timestamp]) = 3)
			AND (d.type = '850')
			AND d.tpid_recipient = 1105
		GROUP BY
                        b.BatchId,
                        b.UserCreated,
			b.DataChannel,
			b.ContentSize,
			b.Created,
			DTSEnvelopes.tpid_sender,
			DTSEnvelopes.tpid_recipient,
			DTSEnvelopes.id,
			FromPartner.name,
			ToPartner.name,
			d.type,
			DTSEnvelopes.control,
			b.BatchTitle
		ORDER BY
			b.Created DESC