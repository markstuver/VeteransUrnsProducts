<?php
// AShop
// Copyright 2014 - AShop Software - http://www.ashopsoftware.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, see: http://www.gnu.org/licenses/.

include "config.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/affiliates.inc.php";

// Validate variables...
if (!is_numeric($resultpage)) unset($resultpage);
if (!is_numeric($commissiondisplayitems)) unset($commissiondisplayitems);
else {
	$c_commissiondisplayitems = $commissiondisplayitems;
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("c_commissiondisplayitems","$commissiondisplayitems");
}
if (!is_numeric($c_commissiondisplayitems)) unset($c_commissiondisplayitems);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

echo "$header
<script language=\"JavaScript\">
<!--
	function selectPayMethod(paymentform) {
		if (paymentform.paymethod.value=='0') return false;
		else {
			paymentform.action='payaffiliate.php?affiliateid=$affiliateid&paymethod='+paymentform.paymethod.value;
			paymentform.submit();
		}
	}
-->
</script>
";

// Get Traffic Exchange payment options if needed...
if ($ashopcurrency == "tec") {
	$result = @mysqli_query($db, "SELECT * FROM payoptions ORDER BY name");
	$paymentoptions = "<select onChange=\"selectPayMethod(paymentform)\" name=\"paymethod\"><option value=\"0\"";
	if (empty($paymethod)) $paymentoptions .= " selected";
	$paymentoptions .= ">".CHOOSE."</option>";
	while ($row = @mysqli_fetch_array($result)) {
		$paymentoptions .= "<option value=\"{$row["payoptionid"]}\""; if ($paymethod==$row["payoptionid"]) $paymentoptions .= " selected"; $paymentoptions .= ">{$row["name"]}</option>";
	}
	$paymentoptions .= "</select>";
}

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE affiliateid='$affiliateid'";
$result = @mysqli_query($db, "$sql");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$email = @mysqli_result($result, 0, "email");

echo  "<div class=\"heading\">".PAYMENTTO." $firstname $lastname, ".AFFILIATEID." $affiliateid <a href=\"editaffiliate.php?affiliateid=$affiliateid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFORAFFILIATE." $affiliateid\" title=\"".PROFILEFORAFFILIATE." $affiliateid\" border=\"0\"></a>
<a href=\"affiliatedetail.php?affiliateid=$affiliateid\"><img src=\"images/icon_history.gif\" alt=\"".STATISTICSFORAFFILIATE." $affiliateid\" title=\"".STATISTICSFORAFFILIATE." $affiliateid\" border=\"0\"></a>&nbsp;<a href=\"editaffiliate.php?affiliateid=$affiliateid&remove=True&fromstats=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEAFFILIATE." $affiliateid ".FROMTHEDATABASE."\" title=\"".DELETEAFFILIATE." $affiliateid ".FROMTHEDATABASE."\" border=\"0\"></a></div><center>";

if ($ashopcurrency == "tec") $selectorderids = "<table width=\"700\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#D0D0D0\">
	<tr bgcolor=\"#808080\"><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".ORDERID."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".THEWORDDATE."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".TRAFFICEXCHANGE."</b></font></td><td align=\"center\" width=\"50\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".AMOUNT."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".PAY."</b></font></td></tr>";

else $selectorderids = "<table width=\"600\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#D0D0D0\">
	<tr bgcolor=\"#808080\"><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".ORDERID."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".THEWORDDATE."</b></font></td><td align=\"center\" width=\"50\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".AMOUNT."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".PAY."</b></font></td></tr>";


$totalprovision = 0;
if ($ashopcurrency == "tec") {
	if (!empty($paymethod) && is_numeric($paymethod)) $result = @mysqli_query($db, "SELECT orderaffiliate.*, orders.payoptionid, orders.invoiceid, orders.wholesale, orders.date FROM orderaffiliate, orders WHERE orderaffiliate.affiliateid='$affiliateid' AND orderaffiliate.orderid=orders.orderid AND (orderaffiliate.paid=0 OR orderaffiliate.paid IS NULL) AND orders.payoptionid='$paymethod' ORDER BY orders.date DESC");
	else $result = @mysqli_query($db, "SELECT orders.date, orders.invoiceid, orders.wholesale, orders.payoptionid, orderaffiliate.* FROM orders, orderaffiliate WHERE orderaffiliate.affiliateid='$affiliateid' AND orderaffiliate.orderid=orders.orderid AND (orderaffiliate.paid=0 OR orderaffiliate.paid IS NULL) ORDER BY orders.date DESC");
} else $result = @mysqli_query($db, "SELECT orders.date, orders.invoiceid, orders.wholesale, orderaffiliate.* FROM orders, orderaffiliate WHERE orderaffiliate.affiliateid='$affiliateid' AND orderaffiliate.orderid=orders.orderid AND (orderaffiliate.paid=0 OR orderaffiliate.paid IS NULL) ORDER BY orders.date DESC");

$numberofrows = intval(@mysqli_num_rows($result));
if (empty($commissiondisplayitems)) {
	if ($c_commissiondisplayitems) $commissiondisplayitems = $c_commissiondisplayitems;
	else $commissiondisplayitems = 20;
}
$numberofpages = ceil($numberofrows/$commissiondisplayitems);
if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $commissiondisplayitems;
else {
	$resultpage = 1;
	$startrow = 0;
}
$startpage = $resultpage - 9;
if ($numberofpages - $resultpage < 10) {
	$pagesleft = $numberofpages - $resultpage;
	$startpage = $startpage - (10 - $pagesleft);
}
if ($startpage < 1) $startpage = 1;
$stoprow = $startrow + $commissiondisplayitems;
@mysqli_data_seek($result, $startrow);
$thisrow = $startrow;
while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	$thisrow++;
	$provision = $row["commission"];
	$provision = number_format($provision,$showdecimals,$decimalchar,$thousandchar);
	$orderdate = $row["date"];
	$orderid = $row["orderid"];
	$invoiceid = $row["invoiceid"];
	$wholesale = $row["wholesale"];
	if ($ashopcurrency == "tec") $payoptionid = $row["payoptionid"];
	if (!empty($payoptionid) && is_numeric($payoptionid)) {
		$payoptionresult = @mysqli_query($db, "SELECT name FROM payoptions WHERE payoptionid='$payoptionid'");
		$payoptionname = @mysqli_result($payoptionresult,0,"name");
	}
	$totalprovision += $provision;
	$selectorderids .= "<tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"salesreport.php?generate=true&orderid=$orderid\">$invoiceid";
	if ($wholesale) $selectorderids .= " ".WHOLESALESIGN;
	$selectorderids .= "</a></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$orderdate</font></td>";
	if ($ashopcurrency == "tec") $selectorderids .= "<td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$payoptionname</font></td>";
	$selectorderids .= "<td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".$currencysymbols[$ashopcurrency]["pre"].number_format($provision,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</font></td><td align=\"center\"><input type=\"checkbox\" name=\"paid$orderid\" checked></td></tr>";
}
$selectorderids .= "</table>";

echo "<form method=\"post\" action=\"affiliatepay.php\" name=\"paymentform\">
<p><font face=\"Arial, Helvetica, sans-serif\" size=\"3\">".TOTALUNPAIDCOMMISSION.": <b>".$currencysymbols[$ashopcurrency]["pre"].number_format($totalprovision,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</b></font></p>
<p>$selectorderids</p>";
if ($numberofrows > 5) {
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
	if ($numberofpages > 1) {
		echo "<b>".PAGE.": </b>";
		if ($resultpage > 1) {
			$previouspage = $resultpage-1;
			echo "<<<a href=\"payaffiliate.php?resultpage=$previouspage&commissiondisplayitems=$commissiondisplayitems&affiliateid=$affiliateid\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"payaffiliate.php?resultpage=$i&commissiondisplayitems=$commissiondisplayitems&affiliateid=$affiliateid\">";
			echo "$i";
			if ($i != $resultpage) echo "</a>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"payaffiliate.php?resultpage=$nextpage&commissiondisplayitems=$commissiondisplayitems&affiliateid=$affiliateid\"><b>".NEXTPAGE."</b></a>>>";
		}
	}
	echo " ".DISPLAY.": <select name=\"commissiondisplayitems\" onChange=\"document.location.href='payaffiliate.php?resultpage=$resultpage&affiliateid=$affiliateid&commissiondisplayitems='+paymentform.commissiondisplayitems.value;\"><option value=\"$numberofrows\">".SELECT."</option><option value=\"20\">20</option><option value=\"50\">50</option><option value=\"100\">100</option><option value=\"200\">200</option><option value=\"$numberofrows\">".ALL."</option></select></td></tr></table>
	";
}
echo "<table width=\"400\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
<tr><td width=\"120\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
if ($ashopcurrency == "tec") {
	echo PAYCREDITS.":</font></td><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$paymentoptions</font></td></tr>";
	if (!empty($paymethod) && is_numeric($paymethod)) echo "
	<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".RECIPIENTEMAIL.":</font></td><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><input type=\"text\" name=\"recipientemail\" size=\"43\" value=\"$email\"></font></td></tr><tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"affiliateid\" value=\"$affiliateid\"><input type=\"hidden\" name=\"paymethod\" value=\"$paymethod\"><input type=\"submit\" name=\"check\" value=\"".PAYNOW."\"></td></tr>";
} else {
	echo PAYSELECTEDBY.":</font></td><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><input type=\"radio\" name=\"paymethod\" value=\"PayPal\"> PayPal <input type=\"radio\" name=\"paymethod\" value=\"Check\" checked> ".CHECK."</font></td></tr>";
}
echo "</table>
<br><input type=\"hidden\" name=\"affiliateid\" value=\"$affiliateid\">";
if ($ashopcurrency != "tec") echo "<input type=\"submit\" name=\"check\" value=\"".MARKASPAID."\">";
echo "</form>
</center>$footer";
?>