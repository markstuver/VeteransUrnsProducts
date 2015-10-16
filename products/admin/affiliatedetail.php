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
include "ashopconstants.inc.php";
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
$db = mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Delete paid commission...
if ($delete && $affiliateid) {
	if ($payment == "true") $sql="UPDATE orderaffiliate SET paid='', paymethod='' WHERE orderid='$delete'"; 
	else if ($chargeback == "true") $sql="DELETE FROM orderaffiliate WHERE orderid='$delete' AND commission<0";
	else $sql="DELETE FROM orderaffiliate WHERE orderid='$delete'";
	$result = @mysqli_query($db, "$sql");
}

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE affiliateid='$affiliateid'";
$result = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($result) == 0) {
    echo "<html><head><title>".ERRORNOSUCHAFFILIATE."</title></head>
         <body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\" vlink=\"#000000\" alink=\"#000000\"><table width=\"75%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
	     <tr bordercolor=\"#000000\" align=\"center\"><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
 		 <tr align=\"center\"><td> <img src=\"../images/logo.gif\"><br><hr size=\"0\" noshade>
		 </td></tr></table><p><font face=\"Arial, Helvetica, sans-serif\" size=\"5\">".ERRORNOSUCHAFFILIATE."</p>
		 <p><a href=\"javascript:history.back()\">".TRYAGAIN."</a></p></font></td></tr></table></body></html>";
    exit;
}

// Store affiliate information in variables...
$firstname = mysqli_result($result, 0, "firstname");
$lastname = mysqli_result($result, 0, "lastname");
$click = mysqli_result($result, 0, "clicks");
$lastdate = @mysqli_result ($result, 0, "lastdate");

// Get statistics from database...
$selectorderids = "	<p><table width=\"60%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#D0D0D0\">
	<tr class=\"reporthead\"><td align=\"center\">".DATETIME."</td><td align=\"center\">".AMOUNT."</td><td align=\"center\">".REFERENCE."</td><td width=\"15\"></td></tr>";


$totalprovision = 0;
$totalourdebt = 0;
$sql="SELECT orders.date, orders.orderid, orders.invoiceid, orders.wholesale, orderaffiliate.* FROM orders, orderaffiliate WHERE orderaffiliate.affiliateid='$affiliateid' AND orderaffiliate.orderid=orders.orderid AND orders.paid != '0' AND orders.paid != '' AND orders.paid IS NOT NULL ORDER BY orderaffiliate.orderid DESC";
$result = @mysqli_query($db, "$sql");
$order = intval(@mysqli_num_rows($result));
if (!$commissiondisplayitems) {
	if ($c_commissiondisplayitems) $commissiondisplayitems = $c_commissiondisplayitems;
	else $commissiondisplayitems = 10;
}
$numberofpages = ceil($order/$commissiondisplayitems);
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
	$orderdate = $row["date"];
	$orderid = $row["orderid"];
	$invoiceid = $row["invoiceid"];
	$wholesale = $row["wholesale"];
	$paid = $row["paid"];
	$paymethod = $row["paymethod"];
	$provision = $row["commission"];
	$provision = number_format($provision,$showdecimals,$decimalchar,$thousandchar);
	$secondtier = $row["secondtier"];
	if ($secondtier) $secondtier++;
	$totalprovision += $provision;
	if ($provision < 0) {
		$chargebackresult = @mysqli_query($db, "SELECT orderid FROM orders WHERE reference='$orderid' LIMIT 1");
		$linkorderid = @mysqli_result($chargebackresult,0,"orderid");
	} else $linkorderid = $orderid;

	$selectorderids .= "<tr class=\"reportline\"><td align=\"center\">$orderdate</td><td align=\"right\"><font ";
	if ($provision < 0) $selectorderids .= " color=\"#FF0000\">- ".$currencysymbols[$ashopcurrency]["pre"].number_format(-$provision,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"];
	else $selectorderids .= ">".$currencysymbols[$ashopcurrency]["pre"].number_format($provision,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"];
	$selectorderids .= "</font></td><td align=\"left\">&nbsp;&nbsp;<a href=\"salesreport.php?generate=true&orderid=$linkorderid\">";
	if ($provision < 0) $selectorderids .= CHARGEBACK." ";
	$selectorderids .= ORDERID.": $invoiceid";
	if ($wholesale) $selectorderids .= " ".WHOLESALESIGN;
	$selectorderids .= "</a>";
	if ($secondtier) $selectorderids .= ", ".TIER." $secondtier";
	$selectorderids .= "</td><td align=\"center\" width=\"15\">";
	if ($provision > 0) $selectorderids .= "<a href=\"affiliatedetail.php?affiliateid=$affiliateid&delete=$orderid\"><img src=\"images/icon_trash.gif\" border=\"0\" alt=\"".DELETETRANSACTION."\" title=\"".DELETETRANSACTION."\"></a>";
	else $selectorderids .= "<a href=\"affiliatedetail.php?affiliateid=$affiliateid&delete=$orderid&chargeback=true\"><img src=\"images/icon_trash.gif\" border=\"0\" alt=\"".DELETETRANSACTION."\" title=\"".DELETETRANSACTION."\"></a>";
	$selectorderids .= "</td></tr>";

	if ($paid && $provision > 0) {
		$selectorderids .= "<tr class=\"reportline\"><td align=\"center\">$paid</td><td align=\"right\"><font color=\"#FF0000\">- ".$currencysymbols[$ashopcurrency]["pre"].number_format($provision,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</font></td><td>&nbsp;&nbsp;".PAIDBY." $paymethod</td><td><a href=\"affiliatedetail.php?affiliateid=$affiliateid&delete=$orderid&payment=true\"><img src=\"images/icon_trash.gif\" border=\"0\" alt=\"".DELETETRANSACTION."\" title=\"".DELETETRANSACTION."\"></a></td></tr>";
	}
}
$selectorderids .= "</table></p>";


// Show affiliate stats in browser...
	if (strpos($header, "title") != 0) {
		$newheader = substr($header,1,strpos($header, "title")+5);
		$newheader .= AFFILIATEDATAFOR.": $firstname $lastname - ".substr($header,strpos($header, "title")+6,strlen($header));
    } else {
		$newheader = substr($header,1,strpos($header, "TITLE")+5);
		$newheader .= AFFILIATEDATAFOR.": $firstname $lastname - ".substr($header,strpos($header, "TITLE")+6,strlen($header));
	}

echo "$header
<div class=\"heading\">".STATISTICSFOR." $firstname $lastname, ".AFFILIATEID." $affiliateid\n <a href=\"editaffiliate.php?affiliateid=$affiliateid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFORAFFILIATE." $affiliateid\" title=\"".PROFILEFORAFFILIATE." $affiliateid\" border=\"0\"></a>&nbsp;<a href=\"referraldiscounts.php?affiliateid=$affiliateid\"><img src=\"images/icon_discount.gif\" alt=\"".REFERRALDISCOUNTSFORAFFILIATE." $affiliateid\" title=\"".REFERRALDISCOUNTSFORAFFILIATE." $affiliateid\" border=\"0\"></a>&nbsp;<a href=\"editaffiliate.php?affiliateid=$affiliateid&remove=True&fromstats=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEAFFILIATE." $affiliateid ".FROMTHEDATABASE."\" title=\"".DELETEAFFILIATE." $affiliateid ".FROMTHEDATABASE."\" border=\"0\"></a></div><center>
	<p><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".TOTALNUMBEROFCLICKS.": $click<br>
	".TOTALNUMBEROFORDERS.": $order<br>
	".TOTALEARNINGS.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($totalprovision,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."<br>
	".DATEOFLASTACTIVITY.": $lastdate</font></p>
	<p><b>".COMMISSIONHISTORY."</b></p>$selectorderids";
if ($order > 5) {
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
	if ($numberofpages > 1) {
		echo "<b>".PAGE.": </b>";
		if ($resultpage > 1) {
			$previouspage = $resultpage-1;
			echo "<<<a href=\"affiliatedetail.php?resultpage=$previouspage&commissiondisplayitems=$commissiondisplayitems&affiliateid=$affiliateid\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"affiliatedetail.php?resultpage=$i&commissiondisplayitems=$commissiondisplayitems&affiliateid=$affiliateid\">";
			echo "$i";
			if ($i != $resultpage) echo "</a>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"affiliatedetail.php?resultpage=$nextpage&commissiondisplayitems=$commissiondisplayitems&affiliateid=$affiliateid\"><b>".NEXTPAGE."</b></a>>>";
		}
	}
	echo " ".DISPLAY.": <select name=\"commissiondisplayitems\" id=\"commissiondisplayitems\" onChange=\"document.location.href='affiliatedetail.php?resultpage=$resultpage&affiliateid=$affiliateid&commissiondisplayitems='+document.getElementById('commissiondisplayitems').value;\"><option value=\"$order\">".SELECT."</option><option value=\"5\">5</option><option value=\"10\">10</option><option value=\"20\">20</option><option value=\"40\">40</option><option value=\"$order\">".ALL."</option></select> ".AFFILIATES2."</form></td></tr></table>
	";
}
echo "</center>$footer";

// Close database...
@mysqli_close($db);
?>