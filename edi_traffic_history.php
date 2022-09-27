<?php
if ($_SESSION["auth"] != "OK") {
    header("location: .");
    die();
}

#Constants ...
$_datatrans_id = 489;

include "../includes/functions/db.php";
include "../includes/functions/html_form_functions.php";
include "../includes/vars.php";
include "common.php";
error_reporting(E_ERROR);

$year = $_REQUEST["year"];
$month = $_REQUEST["month"];
$salesperson = $_REQUEST["salesperson"];

if ($month == "") {
    $month = date("n");
}
if ($year == "") {
    $year = date("Y");
}

for ($y = 0; $y < 4; $y++) {
    $arr_years[] = array(date("Y") - $y, date("Y") - $y);
}

for ($m = 1; $m < 13; $m++) {
    $arr_months[] = array(date("F", strtotime("$m/01/2001")), date("n", strtotime("$m/01/2001")));
}

$form_year = form_select("year", $arr_years, $year);
$form_month = form_select("month", $arr_months, $month);
$form_submit = form_submit("btn_submit", "Update");

print "<font style='font-family:verdana;font-size:20;font-weight:bold'>VIEWING HISTORY</font><BR>";
if ($_SESSION["access_level"] >= 50) {
    print "<a href=index.php?year=$year&month=$month>[click here to go to billing main page]</a>";
}

#Apply sort order / apply changes if necessary
if (isset($_REQUEST["sort_order"])) {
    $sort_order = $_REQUEST["sort_order"];
    $_SESSION["sort_order"] = $sort_order;
}

$sort_order = $_SESSION["sort_order"];
if ($sort_order == "")
    $sort_order = "company_name:asc";

# Handle order  stuff::::
list($OBY, $ODIR) = split(":", $sort_order);

#SELECT w.PK_id, invoice_date,invoice_number,total_amount FROM webedi_messages w INNER JOIN form_810_header h ON h.PK_id = FK_form INNER JOIN form_810_summary s ON FK_header = FK_form WHERE FK_client_profile = $_datatrans_id;
#$partners = mysql_rows("SELECT * FROM webedi_tp WHERE entity_type IN ('CS','WC','IT','2B','SW') ORDER BY $OBY $ODIR");

$qyear = $year;
$qmonth = $month + 1;

if ($qmonth > 12) {
    $qyear++;
    $qmonth = 1;
}

if ($_SESSION["access_level"] <= 50) {
    #die("DDDD");
}

### PRELOAD SALES PEOPLE:
$sql = "SELECT * FROM webedi.billing_users";
$sale_rows = mysql_rows($sql);
$arr_salesperson[] = array("All Resellers", "");
foreach ($sale_rows as $row) {
    $resellers[$row["PK_id"]] = $row["username"];
    $arr_salesperson[] = array("{$row["firstname"]} {$row["lastname"]}", $row["PK_id"]);
}

$form_salesperson = form_select("salesperson", $arr_salesperson, $salesperson);
if (!empty($salesperson))
    $where_salesperson = " AND salesperson = $salesperson ";


### PRELOAD TRAFFIC:
$sql = "SELECT * FROM webedi_tp_monthly_traffic WHERE `month`=$month AND `year`=$year";
#print $sql;
$all_traffic = mysql_rows($sql);
foreach ($all_traffic as $t) {
    $tp_traffic[$t["FK_tp"]] = $t["traffic"];
}
#print_r($all_traffic);
#die();
unset($all_traffic);

### Default query for ALL customers (ADMIN?)
$sql = "SELECT i.*,salesperson FROM vw_webedi_messages_dts_invoices i INNER JOIN webedi30.Partners p ON p.PK_id = i.FK_tp_profile WHERE YEAR(invoice_date)=$qyear AND MONTH(invoice_date)=$qmonth $where_salesperson ORDER BY company_name";

### Specific query for low level users.
if ($_SESSION["access_level"] <= 50) {
    print "<PRE>";
    $sql = "SELECT i.*,p.salesperson FROM vw_webedi_messages_dts_invoices i INNER JOIN webedi30.Partners p ON p.PK_id = i.FK_tp_profile WHERE salesperson = {$_SESSION["user_id"]} AND YEAR(invoice_date)=$qyear AND MONTH(invoice_date)=$qmonth ORDER BY company_name";
}
//DS-4121
if ($qyear=='2021' && $qmonth == '11'){
    $sql = "SELECT i.FK_tp_profile,i.company_name,i.webtpid,i.userid,i.invoice_date,i.invoice_number,i.total_amount,p.salesperson
FROM vw_webedi_messages_dts_invoices i
INNER JOIN webedi30.Partners p ON p.PK_id = i.FK_tp_profile
WHERE YEAR(i.invoice_date)=$qyear AND MONTH(i.invoice_date)=$qmonth $where_salesperson
GROUP BY i.FK_tp_profile,i.company_name,i.webtpid,i.userid,i.invoice_date,i.invoice_number,i.total_amount,p.salesperson
ORDER BY company_name";
}

$partners = mysql_rows($sql);

print "<SCRIPT LANGUAGE=\"Javascript\" SRC=\"includes/jscripts/datepicker.js\"></SCRIPT>";
print "<FORM ID=\"form1\" NAME=\"form1\" METHOD=\"POST\" ONCLICK=\"\">";
print "<INPUT TYPE=\"HIDDEN\" ID=\"sort_order\" NAME=\"sort_order\" VALUE=\"$sort_order\">";
print "$form_year $form_month $form_salesperson $form_submit<BR><BR>";

$inv_order = array("asc" => "desc", "desc" => "asc");
$order_imgs = array("asc" => "/images/arrow_ASC.gif", "desc" => "/images/arrow_DESC.gif");

#Create default order by links
$company_order = "<A HREF=\"\" ONCLICK=\"return change_order('company_name:{$ODIR}');\">";
$webtpid_order = "<A HREF=\"\" ONCLICK=\"return change_order('webtpid:{$ODIR}');\">";

#inverts the order for the current selected order:
if ($OBY == "webtpid") {
    $webtpid_order_img = "<IMG SRC='{$order_imgs[$ODIR]}' BORDER='0'>";
    $webtpid_order = "<A HREF=\"\" ONCLICK=\"change_order('webtpid:{$inv_order[$ODIR]}');\">";
}
if ($OBY == "company_name") {
    $company_order_img = "<IMG SRC='{$order_imgs[$ODIR]}' BORDER='0'>";
    $company_order = "<A HREF=\"\" ONCLICK=\"change_order('company_name:{$inv_order[$ODIR]}');\">";
}

print "<TABLE BORDER=1 WIDTH=500 CELLSPACING=0 CELLPADDING=3 BORDERCOLOR=\"Black\">";
print "<TR STYLE='font-family:verdana;font-size:12;font-weight:bold'>";
print "<TD NOWRAP VALIGN=\"TOP\"><TABLE CELLPADDING=0 CELLSPACING=0 STYLE='font-family:verdana;font-size:12;font-weight:bold'><TR><TD>{$webtpid_order}WebTPId</A>&nbsp</TD><TD>$webtpid_order_img</TD></TR></TABLE></TD>";
print "<TD NOWRAP VALIGN=\"TOP\"><TABLE CELLPADDING=0 CELLSPACING=0 STYLE='font-family:verdana;font-size:12;font-weight:bold'><TR><TD>{$company_order}Company Name</A>&nbsp</TD><TD>$company_order_img</TD></TR></TABLE></TD>";
print "<TD NOWRAP VALIGN=\"TOP\">Message Count</TD>";
print "<TD NOWRAP VALIGN=\"TOP\">Invoice Date</TD>";
print "<TD NOWRAP VALIGN=\"TOP\">Invoice Amount</TD>";
print "<TD NOWRAP VALIGN=\"TOP\">Sales Person</TD>";
print "</TR>";

foreach ($partners as $partner) {
    unset($data);

    $userid = $partner["userid"];

    $total_amount = number_format($partner["total_amount"], 2);
    $grand_total_amount += $partner["total_amount"];
    $grand_total_qty += $tp_traffic[$partner["FK_tp_profile"]];

    $l_partner = "<A HREF='edi_traffic_view_tp_1.php?form_month=$month&form_year=$year&form_userid=$userid' TARGET='_new'>";
    $l_invoice = "<A HREF='https://www.datatranswebedi.com/billing2/view_invoice.php?i={$partner["PK_id"]}' TARGET='_new'>";

    print "<TR STYLE='font-family:verdana;font-size:12;font-weight:none'>";
    print "<TD NOWRAP>{$partner["webtpid"]} - ({$partner["FK_tp_profile"]})&nbsp;</TD>";
    print "<TD NOWRAP>$l_partner{$partner["company_name"]}</a>&nbsp;</TD>";
    #print "<TD NOWRAP ALIGN=\"CENTER\">$l_invoice{$partner["invoice_number"]}</a>&nbsp;</TD>";
    print "<TD NOWRAP ALIGN=\"RIGHT\">{$tp_traffic[$partner["FK_tp_profile"]]}</a>&nbsp;</TD>";
    print "<TD NOWRAP ALIGN=\"RIGHT\">{$partner["invoice_date"]}&nbsp;</TD>";
    print "<TD NOWRAP ALIGN=\"RIGHT\">{$total_amount}&nbsp;</TD>";
    print "<TD NOWRAP ALIGN=\"LEFT\">{$resellers[$partner["salesperson"]]}&nbsp;</TD>";
    print "</TR>";
}

$grand_total_total = number_format($grand_total_amount, 2);
$grand_total_amount = number_format($grand_total_amount, 2);

if ($_SESSION["access_level"] >= 0) {
    print "<TR STYLE='font-family:verdana;font-size:10;font-weight:none'>";
    print "<TD COLSPAN='2'>TOTALS</TD>";
    print "<TD NOWRAP ALIGN=\"RIGHT\">{$grand_total_qty}&nbsp;</TD>";
    print "<TD NOWRAP ALIGN=\"RIGHT\">&nbsp;</TD>";
    print "<TD NOWRAP ALIGN=\"RIGHT\">{$grand_total_amount}&nbsp;</TD>";
    print "<TD NOWRAP ALIGN=\"RIGHT\">&nbsp;</TD>";
    print "</TR>";
    print "</TABLE>";
}
?>


<SCRIPT LANGUAGE="Javascript">
    function change_order(what)
    {
        var obj = document.getElementById("sort_order");
        if (obj)
        {
            obj.value = what;
        }
        var form = document.getElementById("form1");
        if (form)
        {
            form1.submit();
        }
        return false;
    }
</SCRIPT>
