<?php



class EcsQueries {

    protected function getFTP($userid, $tpid, $DATE_W) {
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
            //$edi_dir = WebEDIQueries::getFtpUser($userid);
        }
        if ($tpid == 121) {
            $others_ = "OR BatchTitle = 'FiveRivers POP3 EDI Input Channel'";
            $rec_121_ = "SELECT * FROM Batches WHERE BatchTitle LIKE 'Packing List From MID-SHIP - Five Rivers Distribution, Van Buren, AR%' AND $DATE_W";
            #$sent_121_ = "SELECT * FROM Deliveries WHERE OutputQueue = '1073743018' AND $DATE_W";
            $sent_121_ = "SELECT created,recipients as BatchTitle, size as ContentSize, 'Truck File' as type, 'MIDSHIP' as tpname FROM Deliveries WHERE OutputQueue='1073743018' AND $DATE_W";
            $edi_dir = "dts1312";
            $webedi_ftp = "fiverivers";
        }
        return array($edi_dir, $webedi_ftp, $others_, $sent_121_, $rec_121_);
    }

    protected function getDocFilter($filterDocsInclusive, $dropship) {
        $_docFilter = '';
        if ($filterDocsInclusive) {
            $_docFilter = "AND (DTSDocuments.type in($filterDocsInclusive)) ";
        }
        if ($dropship) {
        //    $_docFilter .= " AND (Batches.UserCreated IS NULL OR Batches.UserCreated NOT LIKE '%DROPSHIP%') ";
        }
        return $_docFilter;
    }

    protected $tpid;
    protected $start_date;
    protected $end_date;
    protected $filterDocsInclusive;
    protected $partnerFilter;
    protected $dropship;
    protected $userid;
    protected $dateSql;
    protected $EDI_BASE_FROM;
    protected $EDI_BASE_WHERE;
    protected $EDI_FIELDS;
    protected $FLAT_FIELDS;
    protected $QUERY_DATA;
    public function __construct($tpid, $start_date, $end_date) {
        $this->tpid = $tpid;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->filterDocsInclusive = false;
        $this->dropship = false;
        $this->userid = $tpid;
        $this->partnerFilter = false;
        $this->dateSql = '';
        
        
        $this->EDI_BASE_FROM = "Batches WITH (NOLOCK)
                            RIGHT OUTER JOIN DTSEnvelopes WITH (NOLOCK)
                            RIGHT JOIN Partners AS FromPartner WITH (NOLOCK)
                            RIGHT JOIN Partners AS ToPartner WITH (NOLOCK)
                            RIGHT JOIN DTSDocuments WITH (NOLOCK) ON ToPartner.tpid = DTSDocuments.tpid_recipient
                            ON FromPartner.tpid = DTSDocuments.tpid_sender
                            ON DTSEnvelopes.id = DTSDocuments.envid 
                            ON Batches.BatchID = DTSEnvelopes.mid";
        $this->EDI_BASE_WHERE = array(
            'AND' => array(
                "(DTSDocuments.[timestamp] BETWEEN ':start_date' AND ':end_date')",
                "(DTSDocuments.type <> '997')"
            )
        );

### QUERY BY Channel ONLY (for non EDI data)
        $this->EDI_FIELDS = array(
            "BatchId" => "Batches.BatchId",
            "Qty" => "COUNT(*)",
            "ContentSize" => "Batches.ContentSize",
            "BatchTitle" => "Batches.BatchTitle",
            "Created" => "Batches.Created",
            "UserCreated" => "Batches.UserCreated",
            "tpid" => "FromPartner.tpid",
            "RECEIVER_ID" => "ToPartner.tpid",
            "tpname" => "FromPartner.name",
            "RECEIVER_NAME" => "ToPartner.name",
            "type" => "DTSDocuments.type",
            "control" => "DTSEnvelopes.control",
            "env_id" => "DTSEnvelopes.id",
            "CHANNEL" => "Batches.DataChannel",
            "MIMETYPE" => "Batches.ContentType",
            "STATUS" => "Batches.StatusText"
        );
        $this->FLAT_FIELDS = array(
            "BatchId" => "Batches.BatchId",
            "Qty" => "COUNT(*)",
            "ContentSize" => "Batches.ContentSize",
            "BatchTitle" => "Batches.BatchTitle",
            "Created" => "Batches.Created",
            "UserCreated" => "Batches.UserCreated",
            "tpid" => "FromPartner.tpid",
            "RECEIVER_ID" => "ToPartner.tpid",
            "tpname" => "FromPartner.name",
            "RECEIVER_NAME" => "ToPartner.name",
            "type" => "'FLATFILE'",
            "control" => "'N/A'",
            "env_id" => "'N/A'",
            "CHANNEL" => "Batches.DataChannel",
            "MIMETYPE" => "Batches.ContentType",
            "STATUS" => "Batches.StatusText"
        );
        $this->QUERY_DATA = array();
        $this->QUERY_DATA['QUERY_BY_CHANNEL'] = array(
            'CONDITION' => true,
            'EDI' => false,
            "FROM" => "Batches WITH (NOLOCK) INNER JOIN DTSBilling.dbo.DTS_ECS_Channels c ON DataChannel=c.item_id"
            . " LEFT JOIN Partners AS FromPartner WITH (NOLOCK) ON FromPartner.tpid = Batches.tpid_sender
		    LEFT JOIN Partners AS ToPartner WITH (NOLOCK) ON ToPartner.tpid = Batches.tpid_receiver ",
            "WHERE" => array(
                'AND' => array(
                    'c.tpid = :tpid',
                    'c.EDI = 0',
                    "(Batches.[Created] BETWEEN ':start_date' AND ':end_date')",
                    'Batches.ContentSize >0'
                )
            )
        );

### QUERY BY Channel PLUS EDI recipient
        $this->QUERY_DATA['QUERY_BY_CHANNEL_EDI'] = array(
            'CONDITION' => true,
            'EDI' => true,
            'FROM' => $this->EDI_BASE_FROM . " INNER JOIN DTSBilling.dbo.DTS_ECS_Channels c ON DataChannel=c.item_id ",
            'WHERE' => array(
                'AND' => array(
                    "c.tpid = :tpid",
                    "c.EDI = 1",
                    $this->EDI_BASE_WHERE,
                    "DTSDocuments.tpid_recipient = c.tpid"
                )
            )
        );
        $this->QUERY_DATA['QUERY_BY_OUTPUTQUEUE'] = array(
            'CONDITION' => true,
            'EDI' => false,
            'FROM' => "Deliveries WITH (NOLOCK)
			INNER JOIN DTSBilling.dbo.DTS_ECS_Channels c ON OutputQueue=c.item_id
			INNER JOIN Batches WITH (NOLOCK) ON Batches.BatchID = Deliveries.BatchID 
                        LEFT JOIN Partners AS FromPartner WITH (NOLOCK) ON FromPartner.tpid = Batches.tpid_sender
                        LEFT JOIN Partners AS ToPartner WITH (NOLOCK) ON ToPartner.tpid = Batches.tpid_receiver ",
            'WHERE' => array(
                'AND' => array(
                    'c.EDI = 0',
                    'c.tpid = :tpid',
                    "(Batches.[Created] BETWEEN ':start_date' AND ':end_date')",
                    'Deliveries.Transmitted IS NOT NULL')
            )
        );


        $this->QUERY_DATA['QUERY_SENT_FTP'] = array(
            'CONDITION' => array('tp' => 388),
            'EDI' => false,
            'FROM' => "Batches WITH (NOLOCK) LEFT JOIN Partners AS FromPartner WITH (NOLOCK) ON FromPartner.tpid = Batches.tpid_sender
		    LEFT JOIN Partners AS ToPartner WITH (NOLOCK) ON ToPartner.tpid = Batches.tpid_receiver",
            'WHERE' => array(
                'AND' => array(
                    "(Batches.[Created] BETWEEN ':start_date' AND ':end_date')",
                    'OR' => array(
                        "Batches.BatchTitle LIKE '%/home/webedi/\$webedi_ftp/%'",
                        "Batches.BatchTitle LIKE '%/home/\$edi_dir/%'",
                        "Batches.BatchTitle LIKE '%./todts/\$edi_dir.%'",
                        "\$others_"
                    )
                )
            )
        );

        $this->QUERY_DATA['QUERY_SENT_EDI'] = array(
            'CONDITION' => true,
            'EDI' => true,
            'FROM' => $this->EDI_BASE_FROM,
            'WHERE' => array(
                'AND' => array(
                    $this->EDI_BASE_WHERE,
                    "DTSDocuments.tpid_sender = :tpid",
                    "Batches.DataChannel NOT IN (1073742068)"
                )
            )
        );
        $this->QUERY_DATA['QUERY_REC_EDI'] = array(
            'CONDITION' => true,
            'EDI' => true,
            'FROM' => $this->EDI_BASE_FROM,
            'WHERE' => array(
                'AND' => array(
                    $this->EDI_BASE_WHERE,
                    "DTSDocuments.tpid_recipient = :tpid"
                )
            )
        );

        // Same as before but, Filter only 850s, Received by TP
        $this->QUERY_DATA['QUERY_REC_EDI_THILL_850s_CC'] = array(
            'CONDITION' => array('tp' => array(552, 806)),
            'EDI' => true,
            'FROM' => $this->EDI_BASE_FROM,
            'WHERE' => array(
                'AND' => array(
                    $this->EDI_BASE_WHERE,
                    "(DTSDocuments.type = '850')",
                    'DTSDocuments.tpid_recipient = :tp'
                )
            )
        );
        // Same as before but, Filter only 850s 852s, Received by TP
        $this->QUERY_DATA['QUERY_REC_EDI_NOVA_CC'] = array(
            'CONDITION' => array('tp' => 840),
            'EDI' => true,
            'FROM' => $this->EDI_BASE_FROM,
            'WHERE' => array(
                'AND' => array(
                    $this->EDI_BASE_WHERE,
                    'OR' => array(
                        "DTSDocuments.type = '850'",
                        "DTSDocuments.type = '852'"
                    ),
                    'DTSDocuments.tpid_recipient = :tp'
                )
            )
        );

        // From specific partners only
        $this->QUERY_DATA['QUERY_REC_EDI_FANMATS_HD'] = array(
            'CONDITION' => array('tp' => 178),
            'EDI' => true,
            'FROM' => $this->EDI_BASE_FROM,
            'WHERE' => array(
                'AND' => array(
                    $this->EDI_BASE_WHERE,
                    'OR' => array(
                        "FromPartner.name LIKE '%Home Depot%'",
                        "FromPartner.name LIKE '%Academy%'"
                    ),
                    "DTSDocuments.tpid_recipient = :tpid"
                )
            )
        );

        $this->QUERY_DATA['QUERY_REC_EDI_MMFASTENERS_CC'] = array(
            'CONDITION' => array('tp' => 770),
            "FROM" => $this->EDI_BASE_FROM,
            'EDI' => true,
            "WHERE" => array(
                'AND' => array(
                    $this->EDI_BASE_WHERE,
                    "DTSDocuments.tpid_sender = 627", // PACCAR Syncreon
                    "Batches.UserCreated IS NULL",
                    "DTSDocuments.tpid_recipient = :tpid"
                )
            )
        );

        $this->QUERY_DATA['QUERY_REC_EDI_INNOVATIVESD'] = array(
            'CONDITION' => array('tp' => 455),
            "FROM" => $EDI_BASE_FROM,
            'EDI' => true,
            "WHERE" => array(
                'AND' => array(
                    $EDI_BASE_WHERE,
                    'OR' => array(
                        "FromPartner.name LIKE '%Office Depot%'",
                        "FromPartner.name LIKE '%Target%'",
                        "FromPartner.name LIKE '%Office Max%'",
                        "FromPartner.name LIKE '%Staples%'",
                        "FromPartner.name LIKE '%X X X X X X X%'",
                        "DTSDocuments.tpid_recipient = :tpid"
                    )
                )
            )
        );

// DELETED FROM ECS
//    const QUERY_SENT_EDI_AKRON = "
//		SELECT 
//			DISTINCT B.BatchId, B.StatusFlags,B.UserCreated,
//			ConfigItems.Item_Type , 
//			B.Created, 
//			ConfigItems.Item_Name, 
//			B.BatchTitle, 
//			B.ContentType, 
//			B.ContentSize, 
//			B.StatusText, 
//			P1.name, 
//			P2.name, 
//			B.BatchID
//		FROM 
//			Batches AS B JOIN ConfigItems ON B.DataChannel = ConfigItems.Item_ID left 
//			JOIN Partners AS P1 ON B.tpid_sender = P1.tpid left 
//			JOIN Partners AS P2 ON B.tpid_receiver = P2.tpid  
//			JOIN Deliveries AS D WITH ( INDEX(Deliveries_BatchID) ) ON B.BatchID = D.BatchID
//		WHERE
//			D.OutputQueue = 1073742718
//			AND BatchTitle NOT LIKE '%997%'
//			AND (DATEPART(yyyy, B.created) = :year) 
//			AND (DATEPART(mm, B.created) = :month) 
//		ORDER BY 
//			B.Created DESC";
    }

    public function buildJointWhere($whereCond, $joint = 'AND') {
        $sqlWhere = '';
        if (is_array($whereCond)) {
            $sqlWherePart = array();
            foreach ($whereCond as $key => $value) {
                if (in_array($key, array('AND', 'OR')) || is_array($value)) {
                    $value = $this->buildJointWhere($value, $key);
                }
                if (!empty($value)) {
                    $sqlWherePart[] = $value;
                }
            }
            $sqlWhere = ' ( ' . implode(" $joint ", $sqlWherePart) . ' ) ';
        } else if (is_string($whereCond)) {
            $sqlWhere = $whereCond;
        } else {
            throw new RuntimeException('Invalid Where Condition: ' . print_r($whereCond, true));
        }
        return $sqlWhere;
    }

    public function isValid($tpid, $condition) {
        if (is_bool($condition)) {
            return $condition;
        } else {
            if (is_array($condition['tp'])) {
                return in_array($tpid, $condition['tp']);
            } else {
                return $tpid === $condition['tp'];
            }
        }
        return false;
    }

    protected function filterPartners($partnerFilter) {
        $filterSent = '';
        $filterRec = '';
        if ($partnerFilter && is_array($partnerFilter) && !empty($partnerFilter)) {
            $convertedIds = array();
            foreach ($partnerFilter as $_id) {
                $_id = intval($_id);
                if ($_id > 0) {
                    $convertedIds[] = $_id;
                }
            }
            if (!empty($convertedIds)) {
                $_ids = implode(",", $convertedIds);
                $filterSent = "DTSDocuments.tpid_recipient in ($_ids)";
                $filterRec = "DTSDocuments.tpid_sender in ($_ids)";
            }
        }
        return array($filterSent, $filterRec);
    }

    public function setFilterDocsInclusive($value) {
        $this->filterDocsInclusive = $value;
        return $this;
    }

    public function setPartnerFilter($value) {
        $this->partnerFilter = $value;
        return $this;
    }

    public function setDropship($value) {
        $this->dropship = $value;
        return $this;
    }

    public function setUserid($value) {
        $this->userid = $value;
        return $this;
    }

    public function setDateSql($value) {
        $this->dateSql = $value;
        return $this;
    }

    public function buildAllQueries() {
        $out = array();
        foreach ($this->QUERY_DATA as $key => $data) {
            if (!$this->isValid($this->tpid, $data['CONDITION'])) {
                continue;
            }
            $out[$key] = $this->buildQuery($key,$data);
        }
        return $out;
    }

    public function buildQuery($key,$data) {
        $query = '';
        error_log($key);
        $query .= "/** $key **/ \n";
        $fields = ($data['EDI']) ? $this->EDI_FIELDS : $this->FLAT_FIELDS;

        $SELECT_FIELDS = implode(',', array_map(function($a, $b) {
                    return "$b as [$a]";
                }, array_keys($fields), array_values($fields)));
        $GROUP_FIELDS = implode(',', array_filter($fields, function($e) {
                    return !in_array($e, array("COUNT(*)", "'FLATFILE'", "'N/A'"));
                }));

        $query .= "SELECT $SELECT_FIELDS\n FROM " . $data['FROM'];
        if ($data['EDI'] && $this->partnerFilter) {
            list($filterSent, $filterRec) = $this->filterPartners($this->partnerFilter);
            if (strpos($key, 'SENT') !== false) {

                $data['WHERE']['AND'][] = $filterSent;
            } else {
                $data['WHERE']['AND'][] = $filterRec;
            }
        }
        if ($this->filterDocsInclusive) {
            $data['WHERE']['AND'][] = "(DTSDocuments.type in({$this->filterDocsInclusive}))";
        }
        list($edi_dir, $webedi_ftp, $others_) = $this->getFTP($this->userid, $this->tpid, $this->dateSql);

        $query .= "\n WHERE " . $this->buildJointWhere($data['WHERE']);
        $query .= "\n GROUP BY $GROUP_FIELDS";
        $query .= "\n ORDER BY " . $fields['BatchId'] . ";\n\n";
        
        
        $query = str_replace(':tpid', $this->tpid, $query);
        $query = str_replace(':start_date', $this->start_date, $query);
        $query = str_replace(':end_date', $this->end_date, $query);
        $query = str_replace("\$edi_dir", ($edi_dir) ? $edi_dir : '1=1', $query);
        $query = str_replace("\$webedi_ftp", ($webedi_ftp) ? $webedi_ftp : '1=1', $query);
        $query = str_replace("\$others_", ($others_) ? $others_ : '1=1', $query);

        return $query;
    }

    function queryDB(\PDO $mssql, $sql, $tpid, \DateTime $startDate, \DateTime $endDate) {
        static $stmt = null;
        $fromDate = $startDate->format("m/d/Y");
        $toDate = $endDate->format("m/d/Y");

        if ($stmt === null) {
            $stmt = $mssql->prepare($sql);
        }
        if (false === $stmt) {
            throw new RuntimeException('Unable to prepare ' . $sql);
        }
        $res = $stmt->execute(array('tpid' => $tpid, 'fromDate' => $fromDate, 'toDate' => $toDate));
        if ($res) {
            $recordset = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        $stmt->closeCursor();
        return $recordset;
    }

}
