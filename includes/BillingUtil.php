<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BillingUtil
 *
 * @author javier
 */
class BillingUtil {

    private static $allBillingSchemas = array();
    public static $partnerBilledAmounts = array();

    public static function getConnection() {
        $mysql = new PDO("mysql:host=" . _MYSQL_HOST . ";dbname=" . _MYSQL_DB2 . "", _MYSQL_USER, _MYSQL_PASS,
                array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ));
        return $mysql;
    }

    public static function getCustomerList(array $filtered = array()) {
        $mysql = self::getConnection();
        $join = '';
        $where = '';
        $order = '';
        if (isset($filtered['month']) && isset($filtered['year'])) {
            $filtered['month'] -= 1;
            if ($filtered['month'] < 1) {
                $filtered['month'] = 12;
                $filtered['year'] -= 1;
            }
            $join = "LEFT JOIN
    webedi.webedi_tp_monthly_traffic t ON t.FK_tp = p.PK_id AND t.year=:month AND t.month=:year";
            $order = "ORDER BY t.traffic";
        }
        if (isset($filtered['tpid'])) {
            $where = " AND p.PK_id in (:tpid) ";
        }
        $query = "SELECT 
    p.*, pa.value AS `billing_schema`, t.traffic
FROM
    webedi30.Partners p
        LEFT JOIN
    webedi.webedi_tp_params pa ON p.PK_id = pa.FK_client
        AND pa.param = 'billing_schema'
        AND pa.value NOT IN (17) 
    $join
WHERE
    ((p.type = 'TP' AND pa.value IS NOT NULL)
        OR p.type IN ('WebEDI' , 'Hosted', 'Software', 'Van'))
        AND p.status IN ('Active' , 'InActive')
        $where
        $order;";
        $stmt = $mysql->prepare($query);
        $res = $stmt->execute($filtered);
        if ($res) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return array();
    }

    public static function updateCustomerTraffic($tpid, $year, $month, $traffic, $schema, $force=false) {
        self::$partnerBilledAmounts[$tpid] = $traffic;
        $mysql = self::getConnection();
        $schema = ($schema) ? $schema : 17;
        $stmtFind = $mysql->prepare("SELECT * FROM  webedi.webedi_tp_monthly_traffic WHERE month=:month AND year=:year AND FK_tp=:tp");
        $stmtFind->execute(array('month' => $month, 'year' => $year, 'tp' => $tpid));
        $traffic = ($traffic==null)?0:$traffic;
        $rows = $stmtFind->fetchAll();
        if ($rows !== false && count($rows) === 0) {
            $msg = "INSERTING Traffic Summary TP:$tpid - $traffic\n";
            $sql = "INSERT INTO webedi.webedi_tp_monthly_traffic (FK_tp,month,year,traffic,billing_schema,date) VALUES(:tp,:month,:year,:traffic,:schema,now())";
            $stmt = $mysql->prepare($sql);
        } else if ($rows !== false && $rows[0]['status'] === 'pending' && ($force || $traffic != $rows[0]['traffic']) ) {
            $msg = "UPDATING Traffic Summary TP:$tpid - $traffic\n";
            $sql = "UPDATE webedi.webedi_tp_monthly_traffic SET traffic=:traffic, billing_schema=:schema, date=now() WHERE FK_tp=:tp AND year=:year AND month=:month";
            $stmt = $mysql->prepare($sql);
        } else {
            // leave untouched
            echo "Traffic Summary Invoiced. Not modifying TP:$tpid - $traffic\n";
            $stmt = null;
        }
        if ($stmt) {
            echo $msg;
            $res = $stmt->execute(array(
                'tp' => $tpid,
                'month' => $month,
                'year' => $year,
                'traffic' => $traffic,
                'schema' => $schema
            ));
            if ($res === false) {
                throw new ErrorException('Error inserting/updating Traffic' . print_r($stmt->errorInfo(), true));
            }
        }
    }

    public static function isCancelled($partner, $year, $month) {
        if ($partner["status"] == 'Cancelled') {
            // If no date has been entered then we ignore this record altogether
            if ($partner["cancel_date"] == "0000-00-00") {
                echo "Cancelled account -- Ignoring record\n";
                return true;
            }

            // Otherwise let's see if one last round of billing is needed:
            $cmonth = date("m", strtotime($partner["cancel_date"]));
            $cyear = date("Y", strtotime($partner["cancel_date"]));
            // $cday = date("d", strtotime($partner["cancel_date"]));
            // Will compare the first of the month of the billing month, and the first of the month of the cancellation month to determine if we are to bill one last time
            // If the dates are the same then we bill, otherwise we don't ;-)
            $cFirstOfTheMonth = strtotime("$cyear-$cmonth-01");
            $bFirstOfTheMonth = strtotime("$year-$month-01");

            if ($cFirstOfTheMonth != $bFirstOfTheMonth) {
                echo "Cancelled account -- Ignoring record\n";
                return true;
            }
        }
        return false;
    }

    public static function getBillingSchema($schemaid) {
        if (empty(self::$allBillingSchemas)) {
            $mysql = self::getConnection();
            $stmt = $mysql->prepare('SELECT * FROM webedi.webedi_tp_billing_schemas');
            $stmt->execute();
            while ($schema = $stmt->fetch(PDO::FETCH_ASSOC)) {
                self::$allBillingSchemas[$schema['PK_id']] = $schema;
            }
        }
        return self::$allBillingSchemas[$schemaid];
    }

}
