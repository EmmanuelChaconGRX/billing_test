<?php

if ($HTML_FORM_FUNCTIONS == "") {
    $HTML_FORM_FUNCTIONS = "LOADED";

#	die();
#	include "includes/vars.php";
#	Functions to generate the form fields.

    function hline($length, $thick, $bg) {
        return "<TR><TD COLSPAN=\"$length\" HEIGHT=\"$thick\" BGCOLOR=\"$bg\"></TD></TR>\n";
    }

    function qr($x) {
        $qr = array("<TABLE BORDER=\"0\" WIDTH=\"100%\" CELLPADDING=\"0\" CELLSPACING=\"0\">", "<TR><TD>", "</TD><TD>", "</TD></TR>", "</TABLE>");
        if ($x <= count($qr))
            return $qr[$x];
    }

    function form_select($name, $arr, $val, $xtra = "", $maxlength = -1) {
        global $READONLY, $f;

        if ($READONLY && $val == "")
            return;

        if (!isset($arr))
            return false;
        $out = "";
        foreach ($arr as $item) {
            if (strtolower($item[1]) == strtolower($val))
                $sel = "SELECTED";
            else
                $sel = "";
            if ($READONLY && $sel != "")
                return "$f[11]{$item[0]}$f[0]";
            if ($maxlength <> -1 && strlen($item[0]) > $maxlength)
                $item[0] = substr($item[0], 0, $maxlength) . "...";
            $out .= "<OPTION VALUE=\"{$item[1]}\" $sel>{$item[0]}</OPTION>";
        }

        if ($READONLY)
            return "&nbsp;";

        return onerrorbox($name)
                . "<SELECT NAME=\"$name\" $xtra STYLE=\"font-size:9\">$out</SELECT>"
                . onerrorboxclose($name);
    }

    function form_radio($name, $arr, $val, $xtra = "", $font = "") {
        global $f;
        if ($font == "")
            $font = $f[2];

        if (!isset($arr))
            return false;
        $out = "";
        foreach ($arr as $item) {
            if (strtolower($item[1]) == strtolower($val))
                $checked = "Checked";
            else
                $checked = "";
            $out .= "$font {$item[0]}$f[0]<INPUT TYPE=\"radio\" NAME=\"$name\" VALUE=\"{$item[1]}\" $xtra $checked>";
        }
        return onerrorbox($name) . $out . onerrorboxclose($name);
    }

    function form_checkbox($name, $value, $preval, $xtra = "") {
        if ($value == $preval)
            $checked = "CHECKED";
        else
            $checked = "";
        return "<INPUT TYPE=\"Checkbox\" NAME=\"$name\" VALUE=\"$value\" $checked $xtra STYLE=\"width:10px\">";
    }

    function form_input($name, $value, $xtra = "", $request = true) {
        global $READONLY;
        global $f;
        if ($value == "" && $request)
            $value = $_REQUEST[$name];
        $value = str_replace(",", "", $value);
        $errstyle = errorstyle($name);

        if ($READONLY)
            return "$f[11]$value&nbsp;$f[0]";
        else
            return "<INPUT TYPE=\"TEXT\" NAME=\"$name\" VALUE=\"$value\" $xtra STYLE=\"font-size=9\" $errstyle>";
    }

    function form_hidden($name, $value, $xtra = "", $request = true) {
        global $READONLY;
        global $f;
        if ($value == "" && $request)
            $value = $_REQUEST[$name];
        $value = str_replace(",", "", $value);

        return "<INPUT TYPE=\"HIDDEN\" NAME=\"$name\" VALUE=\"$value\" $xtra>";
    }

    function form_submit($name, $value, $xtra = "") {
        global $READONLY;
        global $f;
        $value = str_replace(",", "", $value);

        if ($READONLY)
            return "$f[11]$value&nbsp;$f[0]";
        else
            return "<INPUT TYPE=\"SUBMIT\" NAME=\"$name\" VALUE=\"$value\" $xtra STYLE=\"font-size=9\">";
    }

    function form_button($name, $value, $xtra = "") {
        global $READONLY;
        global $f;
        $value = str_replace(",", "", $value);

        if ($READONLY)
            return "$f[11]$value&nbsp;$f[0]";
        else
            return "<INPUT TYPE=\"BUTTON\" NAME=\"$name\" VALUE=\"$value\" $xtra STYLE=\"font-size=9\">";
    }

    function form_text($name, $value, $xtra = "") {
        global $READONLY;
        global $f;
        if ($value == "")
            $value = $_REQUEST[$name];
        $value = str_replace(",", "", $value);
        if ($READONLY)
            return "$f[11]$value&nbsp;$f[0]";
        else
            return onerrorbox($name)
                    . "<TEXTAREA NAME=\"$name\" $xtra STYLE=\"font-size=9;font-family:verdana\">$value</TEXTAREA>\n"
                    . onerrorboxclose($name);
    }

    function form_date($name, $value) {
        global $READONLY;
        global $datepicker;
        global $html_form_name;
        if ($value == "0000-00-00")
            $value = "";
        $out = form_input("$name", $value, "SIZE=\"10\"");
        if ($datepicker && !$READONLY)
            $out = "$out <A HREF=\"javascript:showDatePicker('$html_form_name','$name');\"><IMG SRC=\"./includes/datepicker/DatePicker.gif\" BORDER=\"0\"></A>";
        return $out;
    }

    function form_checkbox_grp($name, $arr, $val, $bool = false, $xtra = "", $join_str = "", $alt = 1) {
        global $f;
        if (!isset($arr))
            return false;

        if (is_array($val)) {
            foreach ($val as $v)
                $tval += $v;
            $val = $tval;
        }

        $out = "";
        foreach ($arr as $item) {
            $n++;
            $checked = "";
            if ($bool && (0 + $item[1]) & $val)
                $checked = "CHECKED";
            if (!$bool && $item[1] == $val)
                $checked = "CHECKED";
            $tmp .= "<TR><TD>$bool</TD><TD>{$item[0]}</TD><TD>[{$item[1]}</TD><TD>&</TD><TD>{$val}]</TD><TD>$checked [" . $rrr . "]</TD></TR>";
            if ($alt && !($n % $alt))
                $join = $join_str;
            else
                $join = "";
            $out .= "<INPUT TYPE=\"Checkbox\" NAME=\"$name\" VALUE=\"$item[1]\" $checked $xtra STYLE=\"width:10;height:10;valign:middle\">$f[2]$item[0]$f[0]$join ";
        }
        return $out;
    }

    function fill_arrays($key) {
        global $webtpid_fk, $tp_profile;

        if ($key == "plan_types")
            $sql = "SELECT description,PK_plan_type FROM codes_plan_types";
        if ($key == "patient_status")
            $sql = "SELECT description,code FROM codes_patient_status";
        if ($key == "rel_to_insured")
            $sql = "SELECT description,code FROM codes_relationship_to_insured";
        if ($key == "us_states")
            $sql = "SELECT code,code FROM codes_us_states";
        if ($key == "tp")
            $sql = "SELECT company_name,userid FROM webedi_tp_access INNER JOIN webedi_tp ON FK_tp_profile = userid WHERE FK_client_profile = $webtpid_fk";
        
        if ($key == "report_type")
            $sql = "SELECT description,code FROM codes_report_type ORDER BY pk_id";
        

#		All this queries should be put together in one query with
#		variable table_name: since most of the table names coincide with the array key.

        $result = mysql_query($sql) or die(mysql_error());

        if (mysql_num_rows($result) > 0)
            while ($rs = mysql_fetch_array($result))
                $array[] = $rs;
        else
            return false;

        return $array;
    }

    function sel_row($arr, $col, $val) {
        if (isset($arr))
            foreach ($arr as $row)
                if ($row[$col] == $val)
                    return $row;
        return false;
    }

    function sel_rows($arr, $col, $val) {
        if (isset($arr))
            foreach ($arr as $row)
                if ($row[$col] == $val)
                    $tmp[] = $row;
        return $tmp;
    }

    $qr = array("<TABLE BORDER=\"0\" WIDTH=\"\" CELLPADDING=\"1\" CELLSPACING=\"0\">", "<TR><TD NOWRAP>", "</TD><TD NOWRAP>", "</TD></TR>", "</TABLE>");

    # ARRAYS:
    if ($webtpid_fk != "")
        $form_arrays["tp"] = fill_arrays("tp");

    $form_arrays["plan_types"] = fill_arrays("plan_types");
    
    $form_arrays["us_states"] = fill_arrays("us_states");
    

#	$form_arrays["unit_of_measure"]			= array(array("Each","EA"),array("Pounds","LB"),array("Ounces","ON"),array("Inches","IN"),array("Feet","FT"),array("Container","CH"));

    $form_arrays["country"] = array(array("United States", "USA"), array("Canada", "CA"), array("Mexico", "Mexico"), array("Puerto Rico", "PR"));

    $form_arrays["sex"] = array(array("M", "m"), array("F", "f"));
    $form_arrays["yn"] = array(array("Yes", "y"), array("No", "n"));
    $form_arrays["ynu"] = array(array("Yes", "y"), array("No", "n"), array("NA", "u"));
#	$form_arrays["rel_to_insured"]			= array(array("Self",1),array("Spouse",2),array("Child",3),array("Other",4));
    $form_arrays["taxidtype"] = array(array("SSN", "ssn"), array("EIN", "ein"));
    $form_arrays["taxidqual"] = array(array("SSN", "34"), array("EIN", "EI"), array("Tax ID", "24"));
    $form_arrays["epsdt"] = array(array("N", "N"), array("U", "U"), array("W", "W"), array("Y", "Y"));
    $form_arrays["responsibility"] = array(array("Unknown", 0), array("Primary", 1), array("Secondary", 2), array("Tertiary", 3));
    $form_arrays["relationship_codes"] = array(array("One", 1), array("Two", 2), array("Three", 3), array("Four", 4));
    $form_arrays["release_of_information"] = array(array("Appropriate Release on File", "A"), array("Informed Consent to Release Medical Information", "I"), array("Limited or Restricted ability to release data", "M"), array("Not allowed to release data", "N"), array("On file at Payor", "O"), array("Provider has signed statement for release of Medical information", "Y"));
    $form_arrays["name_loop"] = array(array("IL", 1), array("PR", 2), array("QC", 3), array("DN", 4), array("QB", 5), array("DQ", 5));
    $form_arrays["amount_qualifier"] = array(array("Payor Amount Paid", "D"), array("Approved Amount", "AAE"), array("Allowed Actual", "B6"), array("Patient Responsibility Actual", "F2"), array("Coverage Amount", "AU"), array("Discount Amount", "D8"), array("Per Day Limit Amount", "DY"), array("Patient Amount Paid", "F8"), array("Tax", "T"), array("Total Claim Before Tax", "T2"));
    $form_arrays["cob_nm_types"] = array(array("Other Subscriber", "IL"), array("Other Payer", "PR"), array("Other Patient", "QC"), array("Other Attending Provider", "71"), array("Other Op Provider", "72"), array("Other Provider", "73"), array("Other Referring Provider", "DN"), array("Other Facility", "FA"));
    $form_arrays["marital_status"] = array(array("Single", 1), array("Married", 2), array("Other", 4));
    $form_arrays["employment_status"] = array(array("Employed", 8), array("Full-time Student", 16), array("Part-time Student", 32));
    $form_arrays["type_of_bill"] = array(array("Orig", 1), array("Corrected", 6), array("Replace", 7), array("Void", 8));
    $form_arrays["message_direction"] = array(array("All", 1), array("In", 2), array("Out", 4), array("Sent", 8));
    $form_arrays["hi_segment_types"] = array(
        array("Principal(BK)", "BK"),
        array("Admitting(BJ)", "BJ"),
        array("Diagnosis(BF)", "BF"),
#													array("ICD9","BR"),
        array("Procedure(BP)", "BP"),
        array("Other Procedure(BO)", "BO"),
        array("Other Procedure(BQ)", "BQ"),
        array("Occurrance(BH)", "BH"),
        array("Occurrance Span(BI)", "BI"),
        array("Value(BE)", "BE"),
        array("Condition(BG)", "BG"),
        array("Treatment(TC)", "TC"));

    $form_arrays["message_direction"] = array(array("All", 1), array("In", 2), array("Out", 4), array("Sent", 8));
    $form_arrays["detail_parts_qual"] = array(array("Buyer's Part Number", "bpn"));
    $form_arrays["sac_rate_type"] = array(array("Amount", "amount"), array("Rate", "rate"), array("Percent", "percent"));
    $form_arrays["doc_ref_type"] = array(array("Invoice", "invoice"), array("PO", "po"));
    $form_arrays["855_ack_type"] = array(array("Acknowledge - No Detail or Change", "AK"), array("Acknowledge with Detail and Change", "AC"), array("Acknowledge - With Exception Detail Only", "AE"), array("Reject with Detail", "RD"), array("Rejected - No Detail", "RJ"));
    
    $form_arrays["855_date_qual"] = array(array("Shipped", "011"), array("Estimated Delivery", "017"));
    $form_arrays["bid_type_response"] = array(
        array("Alternate Bid", "AB"),
        array("Best and Final", "BF"),
        array("Bid Without Exception", "BI"),
        array("Bid With Exception (Request for Information)", "BW"),
        array("Decline to Quote", "DQ"),
        array("One of Multiple", "OM"),
        array("Replacement", "RE"),
        array("Request for Time Extension", "RT"),
        array("Unable to Quote", "UQ"));

    $form_arrays["catalog_type"] = array(array("Each", "each"));
    $form_arrays["weight_uom"] = array(array("Ounces", "OZ"), array("Pounds", "PN"), array("Grams", "GR"), array("Milligrams", "ME"));
    $form_arrays["volume_uom"] = array(array("Barge", "B"), array("Cubic Centimiters", "C"), array("Cord", "D"), array("Cubic Feet", "E"), array("100 Board Feet", "F"), array("Gallons", "G"), array("Hundr.Mes.Tons", "H"), array("Load", "L"), array("Cubic Decimeters", "M"), array("Cubic Inches", "N"), array("Car", "R"), array("Measurement Ton", "S"), array("Container", "T"), array("Volumetric Unit", "U"), array("Liter", "V"), array("Cubic Meters", "X"));
    $form_arrays["dim_uom"] = array(array("Inches", "IN"), array("Feet", "FT"), array("Meters", "MR"), array("Centimeters", "CM"));
    $form_arrays["qty_uom"] = array(array("Each", "EA"), array("Container", "CT"), array("Carton", "CH"), array("Pallet", "PL"), array("Package", "PK"), array("Per Mile", "PM"));
    $form_arrays["shipment_mop"] = array(array("Collect", "CC"), array("Prepaid by seller", "PP"));

    $form_arrays["820_id_qual"] = array(array("Invoice", "IV"), array("Invoice", "IK"), array("Shippers ID No.", "SI"), array("Manifest Key Number", "MK"), array("Purchase Order", "PO"));

    $form_arrays["content_type"] = array(array("SKU/Sze", "SKU/Sze"), array("SKU/PPK", "SKU/PPK"), array("Style/Sze", "Style/Sze"), array("Ord/Grp", "Ord/Grp"));

    function form_update($name, $sqlcol, $type, &$sqlupdt, $n = -1, $fname, $req = 0) {
        global $form_errors;
        global $fields_in_error;

        if ($sqlcol == "")
            $sqlcol = $name;

        if ($n == -1)
            $tmp = $_REQUEST["txt_$name"];
        else
            $tmp = $_REQUEST["txt_$name"][$n];

        if ($tmp == "" && $req) {
            $form_errors[] = "$fname is a required field";
            $fields_in_error[] = "txt_$name";
        } elseif ($type == "name") {
            if ($tmp != "" && !preg_match("/^[a-zA-Z0-9()\- &#\.\\\'\:]*$/", $tmp)) {
                $form_errors[] = "$fname contains invalid characters";
                $fields_in_error[] = "txt_$name";
            }
        } elseif ($type == "amount") {
            if (!is_numeric($tmp)) {
                $form_errors[] = "$fname was in invalid format or had invalid characters. Please verify.";
                $fields_in_error[] = "txt_$name";
            }
        } elseif ($type == "company_name") {
            if ($tmp != "" && !preg_match("/^[0-9a-zA-Z()\- #&:\/\.\\\'\:]*$/", $tmp)) {
                $form_errors[] = "$fname contains invalid characters";
                $fields_in_error[] = "txt_$name";
            }
        } elseif ($type == "address") {
            if ($tmp != "" && !preg_match("/^[a-zA-Z&\- #\.\,0-9\-\/\\\:']*$/", $tmp)) {
                $form_errors[] = "$fname contains invalid characters";
                $fields_in_error[] = "txt_$name";
            }
        } elseif ($type == "zip") {
            if ($tmp != "" && !( preg_match("/^[0-9]{5}$/", $tmp) || preg_match("/^[0-9]{5}[- ]{0,1}[0-9]{4}$/", $tmp) || preg_match("/^[A-Z0-9]{3}[- ]{0,1}[A-Z0-9]{3}$/", $tmp))) {
                $form_errors[] = "$fname is invalid";
                $fields_in_error[] = "txt_$name";
            }
        } elseif ($type == "primary_id" && $tmp != "") {
            $tmp = preg_replace("/[^0-9]+/", "", $tmp);
            if ($tmp == "" || !preg_match("/^[0-9]{9,12}$/", $tmp)) {
                $form_errors[] = "$fname is invalid";
                $fields_in_error[] = "txt_$name";
            }
        } elseif ($tmp != "" && $type == "phone") {
            $tmp = preg_replace("/[^0-9]+/", "", $tmp);
            if (!preg_match("/^[0-9]{10}$/", $tmp)) {
                $form_errors[] = "$fname is invalid";
                $fields_in_error[] = "txt_$name";
            } else
                $tmp = substr($tmp, 0, 3) . "-" . substr($tmp, 3, 3) . "-" . substr($tmp, 6, 4);
        } elseif ($tmp != "" && $type == "phone2") {
            $tmp = preg_replace("/[^0-9]+/", "", $tmp);
            if (!preg_match("/^[0-9]{10}$/", $tmp)) {
                $form_errors[] = "$fname is invalid";
                $fields_in_error[] = "txt_$name";
            }
#			else
#				$tmp = substr($tmp,0,3)."-".substr($tmp,3,3)."-".substr($tmp,6,4);
        } elseif ($type == "date" && $tmp != "") {
            $sep = "\-\.\,\/ ";
            if (preg_match("/^[0-9]{4}[$sep][0-9]{2}[$sep][0-9]{2}$/", $tmp))
                list($y, $m, $d) = preg_split("/[$sep]/", $tmp);
            if (preg_match("/^[0-9]{2}[$sep][0-9]{2}[$sep][0-9]{4}$/", $tmp))
                list($m, $d, $y) = preg_split("/[$sep]/", $tmp);
            elseif (strtotime($tmp) != -1) {
                $tmp = date("Y-m-d", strtotime($tmp));
                list($y, $m, $d) = preg_split("/[$sep]/", $tmp);
            }
            if (!checkdate($m, $d, $y)) {
                $form_errors[] = "$fname is invalid";
                $fields_in_error[] = "txt_$name";
            } else
                $tmp = "$y-$m-$d";
        }
        $sqlupdt[] = "$sqlcol='$tmp'";
        return $tmp;
    }

    function print_form_type() {
        global $msg_type, $message_dir;
        if ($msg_type == "210")
            $name = "(210) Motor Carrier Freight Details and Invoice";
        if ($msg_type == "214")
            $name = "(214) Shipment Status";
        if ($msg_type == "276")
            $name = "HIPAA Claim Status Request";
        if ($msg_type == "277")
            $name = "HIPAA Claim Status Update";
        if ($msg_type == "277rq")
            $name = "HIPAA Request for Additional Information";
        if ($msg_type == "275")
            $name = "HIPAA Request for Additional Information";
        if ($msg_type == "270")
            $name = "HIPAA Eligibility Inquiry";
        if ($msg_type == "271")
            $name = "HIPAA Eligibility Response";
        if ($msg_type == "810")
            $name = "(810) Invoice";
        if ($msg_type == "820")
            $name = "(820) Remittance Advice";
        if ($msg_type == "824")
            $name = "(824) Application Advice";
        if ($msg_type == "830")
            $name = "(830) Schedule Release";
        if ($msg_type == "835")
            $name = "HIPAA Claim Payment Advice";
        if ($msg_type == "837P")
            $name = "HIPAA Professional Claim (HCFA1500)";
        if ($msg_type == "837I")
            $name = "HIPAA Institutional Claim (UB92)";
        if ($msg_type == "840")
            $name = "(840) Request for Quote";
        if ($msg_type == "843")
            $name = "(843) Quote Response";
        if ($msg_type == "846")
            $name = "(846) Inventory Inquiry/Advice";
        if ($msg_type == "850")
            $name = "(850) Purchase Order";
        if ($msg_type == "852")
            $name = "(852) Product Activity Data";
        if ($msg_type == "855")
            $name = "(855) Purchase Order Acknowledgement";
        if ($msg_type == "856")
            $name = "(856) Advanced Ship Notice";
        if ($msg_type == "860")
            $name = "(860) Purchase Order Adjustment";
        if ($msg_type == "862")
            $name = "(862) Shipping Schedule";
        if ($msg_type == "864")
            $name = "(864) Text Message";
        if ($msg_type == "865")
            $name = "(865) Purchase Order Adjustment Acknowledgement";
        if ($msg_type == "867")
            $name = "(867) Product Transfer and Resale Report";
        if ($msg_type == "sale")
            $name = "Sale Registration";

        if ($msg_type == "DELFOR")
            $name = "(DELFOR) Delivery Forecast Schedule";
        if ($msg_type == "DELJIT")
            $name = "(DELJIT) Delivery Just in Time Schedule";
        if ($msg_type == "ORDERS")
            $name = "(ORDERS) Purchase Order";
        if ($msg_type == "ORDRSP")
            $name = "(ORDRSP) Purchase Order Response";
        if ($msg_type == "ORDCHG")
            $name = "(ORDCHG) Purchase Order Change Request";
        if ($msg_type == "RECADV")
            $name = "(RECADV) Receiving Advice";
        if ($msg_type == "APERAK")
            $name = "(APERAK) Application Error and Acknowledgement";
        if ($msg_type == "GENRAL")
            $name = "(GENRAL) General Purpose";
        if ($msg_type == "CONTRL")
            $name = "(CONTRL) Syntax and Service Report";
        if ($msg_type == "DESADV")
            $name = "(DESADV) Despatch Advice";
        if ($msg_type == "INVOIC")
            $name = "(INVOIC) Invoice";

        if ($msg_type == "DESPADV")
            $name = "(DESPADV) Shipping Schedule";

        return $name;
    }

    function get_fk_data(&$name, &$val) {
        if ($val == "")
            return false;
        $rs = mysql_row("SELECT * FROM fk_translation_rules WHERE key_name = '$name'");
        if (isset($rs["query"])) {
            $query = str_replace("%VAL%", $val, $rs["query"]);
            $val = mysql_value($query);
            $name = substr($name, 3, strlen($name) - 3);
            return true;
        }
        return false;
    }

    function transform_field($id, &$val) { #get_fk_data(&$name,&$val)
        $val = str_replace(",", "", $val);
        if ($val != "") {
            if ($id == "xpatient_status") {
                $val = mysql_value_concat("SELECT description FROM codes_patient_status WHERE code & $val");
            }
        }
    }

    function tp_select($client_profile, $tp_profile) {
        global $form_arrays;
        $out = form_select("txt_trading_partner", $form_arrays["tp"], $tp_profile);
        return $out;
    }

    function array2tbl($str, $params = "", $td_params = "", $tr1_params = "") {
        $out .= "<TABLE $params>\n";
        $rows = explode("--", $str);
        foreach ($rows as $row) {
            $out .= "<TR $tr1_params>";
            $cols = explode("||", $row);
            foreach ($cols as $col) {
                $colp = "";
                if (preg_match("/({.+})/", $col, $x)) {
                    $col = preg_replace("/{.+}/", "", $col);
                    $colp = preg_replace("/[{}]/", "", $x[1]);
                }
                $out .= "<TD $td_params $colp>$col</TD>";
            }
            $out .= "</TR>\n";
            $tr1_params = "";
        }
        $out .= "</TABLE>\n";
        return $out;
    }

    function onerrorbox($name) {
        global $msg_type;
        if (!stristr("856,810", $msg_type))
            return "";
        global $fields_in_error;
        if (is_array($fields_in_error))
            if (in_array($name, $fields_in_error))
                return "<SPAN NAME=\"\" STYLE=\"border:solid;border-width:3;border-padding:100;border-color:red;padding:2;position:relative\">";
    }

    function onerrorboxclose($name) {
        global $msg_type;
        if (!stristr("856,810", $msg_type))
            return "";
        global $fields_in_error;
        if (is_array($fields_in_error))
            if (in_array($name, $fields_in_error))
                return "</SPAN>";
    }

    function dateyyyymmdd($date) {
        $tmp = $date;
        $sep = "\-\.\,\/ ";
        if (preg_match("/^[0-9]{4}[$sep][0-9]{2}[$sep][0-9]{2}$/", $tmp)) {
            list($y, $m, $d) = preg_split("/[$sep]/", $tmp);
#			print "11111111111111111111111<BR>";
        } elseif (preg_match("/^[0-9]{2}[$sep][0-9]{2}[$sep][0-9]{4}$/", $tmp)) {
            list($m, $d, $y) = preg_split("/[$sep]/", $tmp);
#			print "22222222222222222222222<BR>";
        } elseif (strtotime($tmp) != -1) {
#			print "33333333333333333333333<BR>";
            $tmp = date("Y-m-d", strtotime($tmp));
            list($y, $m, $d) = preg_split("/[$sep]/", $tmp);
        }
        if (checkdate($m, $d, $y)) {
            return "$y-$m-$d";
        }
        return false;
    }

    function set_flag_bit($msg, $bit) {
        
    }

    function get_param($param) {
        global $webtpid_fk;
        return mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$webtpid_fk' AND param='$param'");
    }

    function get_param_tp($param, $tpp) {
        return mysql_value("SELECT value FROM webedi_tp_params WHERE FK_client='$tpp' AND param='$param'");
    }

    function set_param($param, $value) {
        global $webtpid_fk;
        return mysql_value("INSERT INTO webedi_tp_params (FK_client,param,value) VALUES('$webtpid_fk','$param','$value')");
    }

    function set_param_tp($param, $value, $tpp) {
        $count = mysql_value("SELECT count(*) FROM webedi_tp_params WHERE FK_client='$tpp' AND param='$param'");
        if ($count > 0)
            mysql_query("UPDATE webedi_tp_params SET value='$value' WHERE FK_client='$tpp' AND param='$param'");
        else
            mysql_query("INSERT INTO webedi_tp_params (FK_client,param,value) VALUES('$tpp','$param','$value')");
    }

    function ucc_check_digit($number) {
        for ($a = 0; $a < strlen($number); $a++) {
            if ($even != "no")
                $even = "no";
            else
                $even = "yes";
            if ($even != "yes")
                $odd[] = $number[$a];
            else
                $evv[] = $number[$a];
        }
        $result = array_sum($odd) * 3 + array_sum($evv);
        $result = substr($result, strlen($result) - 1, 1);
        $result = 10 - $result;

        if ($result == 10)
            $result = 0;

        return $result;
    }

    function mysql_queryt($s) {
        print "<BR><B>Query:</B> $s<BR>";
        return true;
    }

    function getformhtml($msg_id, $msg_type) {
        $homeurl = $_SERVER["SERVER_NAME"];
        if ($_SERVER['SERVER_PORT'] == 443) {
            $ssl = true;
            $http = "https";
            $homeurl = "www.datatranswebedi.com";
        } else
            $http = "http";

        $row = mysql_row("SELECT * FROM webedi_messages WHERE PK_id=$msg_id");
        if ($row["msg_type"] == "837I" || $row["msg_type"] == "837P")
            $url = "$http://$homeurl/includes/pages/form_837.php";
        if ($row["msg_type"] == "276")
            $url = "$http://$homeurl/includes/pages/form_276_print.php";
        if ($row["msg_type"] == "214")
            $url = "$http://$homeurl/includes/pages/form_214.php";
        if ($row["msg_type"] == "810")
            $url = "$http://$homeurl/includes/pages/form_810.php";
        if ($row["msg_type"] == "820")
            $url = "$http://$homeurl/includes/pages/form_820.php";
        if ($row["msg_type"] == "824")
            $url = "$http://$homeurl/includes/pages/form_824.php";
        if ($row["msg_type"] == "830")
            $url = "$http://$homeurl/includes/pages/form_830.php";
        if ($row["msg_type"] == "835")
            $url = "$http://$homeurl/includes/pages/form_835.php";
        if ($row["msg_type"] == "840")
            $url = "$http://$homeurl/includes/pages/form_840.php";
        if ($row["msg_type"] == "843")
            $url = "$http://$homeurl/includes/pages/form_843.php";
        if ($row["msg_type"] == "846")
            $url = "$http://$homeurl/includes/pages/form_846.php";
        if ($row["msg_type"] == "850")
            $url = "$http://$homeurl/includes/pages/form_850.php";
        if ($row["msg_type"] == "852")
            $url = "$http://$homeurl/includes/pages/form_852.php";
        if ($row["msg_type"] == "855")
            $url = "$http://$homeurl/includes/pages/form_855.php";
        if ($row["msg_type"] == "856")
            $url = "$http://$homeurl/includes/pages/form_856.php";
        if ($row["msg_type"] == "860")
            $url = "$http://$homeurl/includes/pages/form_860.php";
        if ($row["msg_type"] == "865")
            $url = "$http://$homeurl/includes/pages/form_865.php";
        if ($row["msg_type"] == "867")
            $url = "$http://$homeurl/includes/pages/form_867.php";
        if ($row["msg_type"] == "875")
            $url = "$http://$homeurl/includes/pages/form_875.php";
        if ($row["msg_type"] == "880")
            $url = "$http://$homeurl/includes/pages/form_880.php";
        if ($row["msg_type"] == "DELFOR")
            $url = "$http://$homeurl/includes/pages/form_830.php";
        if ($row["msg_type"] == "DELJIT")
            $url = "$http://$homeurl/includes/pages/form_862.php";
        if ($row["msg_type"] == "ORDERS")
            $url = "$http://$homeurl/includes/pages/form_850.php";
        if ($row["msg_type"] == "ORDRSP")
            $url = "$http://$homeurl/includes/pages/form_855.php";
        if ($row["msg_type"] == "ORDCHG")
            $url = "$http://$homeurl/includes/pages/form_860.php";
        if ($row["msg_type"] == "RECADV")
            $url = "$http://$homeurl/includes/pages/form_861.php";
        if ($row["msg_type"] == "APERAK")
            $url = "$http://$homeurl/includes/pages/form_824.php";
        if ($row["msg_type"] == "GENRAL")
            $url = "$http://$homeurl/includes/pages/form_864.php";
        if ($row["msg_type"] == "CONTRL")
            $url = "$http://$homeurl/includes/pages/form_997.php";
        if ($row["msg_type"] == "DESADV")
            $url = "$http://$homeurl/includes/pages/form_856.php";
        if ($row["msg_type"] == "INVOIC")
            $url = "$http://$homeurl/includes/pages/form_810.php";
        if ($row["msg_type"] == "sale")
            $url = "$http://$homeurl/includes/pages/form_sale.php";

        $params[] = "msg_id=$msg_id";
        $params[] = "action=print_form";
        $params[] = "form={$row["msg_type"]}";
        $params[] = "webtpid={$row["FK_client_profile"]}";

        $params = implode("&", $params);
        $ch = curl_init("$url?$params");

#		die("$url?$params");

        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 900000);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $buf = curl_exec($ch);

        return "$buf";
    }

    function getcsv($msg_id, $msg_type) {
        $homeurl = $_SERVER["SERVER_NAME"];
        $row = mysql_row("SELECT * FROM webedi_messages WHERE PK_id=$msg_id");
        $url = "https://$homeurl/includes/pages/form_{$row["msg_type"]}.php";

        if ($url == "")
            return "\n\n";

        $params[] = "msg_id=$msg_id";
        $params[] = "action=csv";
        $params[] = "form={$row["msg_type"]}";
        $params[] = "webtpid={$row["FK_client_profile"]}";

        $params = implode("&", $params);
        $ch = curl_init("$url?$params");

#		print "$url?$params";

        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 900000);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $buf = curl_exec($ch);

        if (stristr($buf, "<SCRIPT"))
            $buf = "";
        if (stristr($buf, "<HTML>"))
            $buf = "";

        return "$buf";
    }

    function errorstyle($name) {
        global $fields_in_error;
        if (is_array($fields_in_error))
            if (in_array($name, $fields_in_error))
                return "STYLE=\"border:solid;border-width:2;border-color:red;background=#CCCCCC\"";
    }

    function mkdir_r($dirName) {
        foreach (explode('/', dirname($dirName)) as $dirPart)
            mkdir($newDir = "$newDir$dirPart/", 0777);

        chown($newDir, "webedi");
        chgrp($newDir, "webedi");
    }

    function webedi_csv_name($webtpid, $msg_type) {
        global $DEBUGGING;

        $diradd = "";

        if (strstr($_SERVER['SCRIPT_FILENAME'], "/webedi_dev/"))
            $diradd = "dev/";
        if (strstr($_SERVER['SCRIPT_FILENAME'], "/webedi_beta/"))
            $diradd = "beta/";

        $ftpdir = mysql_value("SELECT ftpdir FROM webedi_tp WHERE webtpid='$webtpid'");
        $filename .= "/home/webedi/$ftpdir/$diradd" . date("Ymd") . "." . date("His") . ".$msg_type";
        $filename = strtolower($filename);
        $filename = preg_replace("/[^a-z\/0-9\.]/", "", $filename);

        if (!is_dir($filename))
            mkdir_r($filename);

#		if($DEBUGGING)
#			die("$filename");

        return $filename;
    }

    function val_in_parenthesis($t) {
        $part = $t;
        $part = substr($part, strrpos($part, "(") + 1);
        $part = substr($part, 0, strpos($part, ")"));
        $part = trim($part);
        if ($part)
            return $part;
        else
            return $t;
    }

    function dateDiff($dt1, $dt2, $split = 'yw') {
        $date1 = (strtotime($dt1) != -1) ? strtotime($dt1) : $dt1;
        $date2 = (strtotime($dt2) != -1) ? strtotime($dt2) : $dt2;
        $dtDiff = $date1 - $date2;
        $totalDays = intval($dtDiff / (24 * 60 * 60));
        $totalSecs = $dtDiff - ($totalDays * 24 * 60 * 60);
        $dif['h'] = $h = intval($totalSecs / (60 * 60));
        $dif['m'] = $m = intval(($totalSecs - ($h * 60 * 60)) / 60);
        $dif['s'] = $totalSecs - ($h * 60 * 60) - ($m * 60);
        // set up array as necessary
        switch ($split) {
            case 'yw': # split years-weeks-days
                $dif['y'] = $y = intval($totalDays / 365);
                $dif['w'] = $w = intval(($totalDays - ($y * 365)) / 7);
                $dif['d'] = $totalDays - ($y * 365) - ($w * 7);
                break;
            case 'y': # split years-days
                $dif['y'] = $y = intval($totalDays / 365);
                $dif['d'] = $totalDays - ($y * 365);
                break;
            case 'w': # split weeks-days
                $dif['w'] = $w = intval($totalDays / 7);
                $dif['d'] = $totalDays - ($w * 7);
                break;
            case 'd': # don't split -- total days
                $dif['d'] = $totalDays;
                break;
            default:
                die("Error in dateDiff(). Unrecognized \$split parameter. Valid values are 'yw', 'y', 'w', 'd'. Default is 'yw'.");
        }
        return $dif;
    }

}
