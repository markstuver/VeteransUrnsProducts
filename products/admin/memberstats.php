<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "checklicense.inc.php";
include "ashopconstants.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/members.inc.php";
// Get context help for this page...
$contexthelppage = "memberstats";
include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

echo "$header
";

echo  "<div class=\"heading\">".STATISTICSANDPAYMENT." <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><center>
<form action=\"memberstats.php?resultpage=$resultpage&admindisplayitems=$admindisplayitems\" method=\"post\" name=\"memberfilterform\" style=\"margin-bottom: 0px;\"><span class=\"text\">".FILTERBYNAME.": <input type=\"text\" name=\"namefilter\" value=\"$namefilter\" size=\"10\"> <input type=\"submit\" value=\"".FILTER."\"></span></form><br>
      <form action=\"memberstats.php\" method=\"post\"><table width=\"80%\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" bgcolor=\"#D0D0D0\">
      <tr class=\"reporthead\"><td align=\"left\">".IDSHOP."</td><td align=\"center\" width=\"80\">".ORDERS."</td><td align=\"center\" width=\"80\">".EARNED."</td><td align=\"center\" width=\"80\">".UNPAID."</td><td align=\"center\" width=\"80\">".ACTION."</td></tr>";

// Reset statistics...
$totalclicks = 0;
$totalorders = 0;
$totalprovision = 0;
$totalourdebt = 0;

// Get member information from database...
$sql="SELECT * FROM user WHERE shopname IS NOT NULL AND username != 'ashopadmin' AND email IS NOT NULL";
if ($namefilter) $sql .= " AND shopname  LIKE '%$namefilter%'";
$sql .=" ORDER BY userid";
$result = @mysqli_query($db, "$sql");
$numberofrows = intval(@mysqli_num_rows($result));
if (!$admindisplayitems) {
	if ($c_admindisplayitems) $admindisplayitems = $c_admindisplayitems;
	else $admindisplayitems = 10;
}
$numberofpages = ceil($numberofrows/$admindisplayitems);
if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $admindisplayitems;
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
$stoprow = $startrow + $admindisplayitems;
@mysqli_data_seek($result, $startrow);
$thisrow = $startrow;
while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	$thisrow++;
	$commission = 0;
	$provision = 0;
	$ourdebt = 0;
	$totalcommission = 0;
    $shopname = $row["shopname"];
	$commissionlevel = $row["commissionlevel"];
	if (!$commissionlevel) $commissionlevel = $memberpercent;
	$email = $row["email"];
    $memberid = $row["userid"];
	$ordersresult = @mysqli_query($db, "SELECT * FROM memberorders WHERE userid='$memberid' AND date!='' AND paid!=''");
	$orders = @mysqli_num_rows($ordersresult);
	$totalorders += $orders;
	if (@mysqli_num_rows($ordersresult) != 0) {
		for ($j = 0; $j < @mysqli_num_rows($ordersresult);$j++) {
			$price = @mysqli_result($ordersresult, $j, "price");
			$paidtoshop = @mysqli_result($ordersresult, $j, "paidtoshop");
			//$affiliatecommission = @mysqli_result($ordersresult, $j, "affiliatecommission");
			$baseprice = $price; // - $affiliatecommission;
			$commission = $baseprice * ($commissionlevel/100);
			$commission += $shipping + $tax + $gst + $pst;
			$provision += $commission;
			if (!$paidtoshop) $ourdebt += $commission;
		}
	}
	echo "<tr class=\"reportline\"><td align=\"left\">$memberid, <a href=\"editmember.php?memberid=$memberid&fromstats=True\">$shopname</a></td><td align=\"center\">$orders</td><td align=\"right\">".$currencysymbols[$ashopcurrency]["pre"].number_format($provision,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td align=\"right\">";
	if ($ourdebt) echo $currencysymbols[$ashopcurrency]["pre"].number_format($ourdebt,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"];
	echo "</td><td align=\"center\">";
	if ($ourdebt) echo "<a href=\"paymember.php?memberid=$memberid\"><img src=\"images/icon_pay.gif\" alt=\"".PAYMEMBER." $memberid.\" title=\"".PAYMEMBER." $memberid.\" border=\"0\"></a>&nbsp;";
	else echo "<img src=\"images/spacer.gif\" width=\"15\" border=\"0\"></a>&nbsp;";
	echo "<a href=\"editmember.php?memberid=$memberid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $memberid\" title=\"".PROFILEFOR." $memberid\" border=\"0\"></a>&nbsp;<a href=\"salesreport.php?memberid=$memberid&generate=true&reporttype=paid\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $memberid\" title=\"".SALESHISTORYFOR." $memberid\" border=\"0\"></a>&nbsp;<a href=\"editmember.php?memberid=$memberid&remove=True&fromstats=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEMEMBER." $memberid ".FROMDB."\" title=\"".DELETEMEMBER." $memberid ".FROMDB."\" border=\"0\"></a></td></tr>";
	$totalprovision += $provision;
	$totalourdebt += $ourdebt;
}

echo "<tr><td class=\"reporttotal\" align=\"right\">".TOTALS." </td><td class=\"reporttotal\" align=\"center\">$totalorders</td><td class=\"reporttotal\" align=\"right\"> ".$currencysymbols[$ashopcurrency]["pre"].number_format($totalprovision,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td class=\"reporttotal\" align=\"right\"> ".$currencysymbols[$ashopcurrency]["pre"].number_format($totalourdebt,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td class=\"reporttotal\">&nbsp;</td></tr></table>";
if ($numberofrows > 5) {
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
	if ($numberofpages > 1) {
		echo "<b>".PAGE.": </b>";
		if ($resultpage > 1) {
			$previouspage = $resultpage-1;
			echo "<<<a href=\"memberstats.php?resultpage=$previouspage&admindisplayitems=$admindisplayitems&namefilter=$namefilter\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"memberstats.php?resultpage=$i&admindisplayitems=$admindisplayitems&namefilter=$namefilter\">";
			echo "$i";
			if ($i != $resultpage) echo "</a>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"memberstats.php?resultpage=$nextpage&admindisplayitems=$admindisplayitems&namefilter=$namefilter\"><b>".NEXTPAGE."</b></a>>>";
		}
	}
	echo " ".DISPLAY.": <select name=\"admindisplayitems\" onChange=\"document.location.href='memberstats.php?resultpage=$resultpage&namefilter=$namefilter&admindisplayitems='+admindisplayitems.value;\"><option value=\"$numberofrows\">".SELECT."</option><option value=\"5\">5</option><option value=\"10\">10</option><option value=\"20\">20</option><option value=\"40\">40</option><option value=\"$numberofrows\">".ALL."</option></select> ".MEMBERS2."</td></tr></table></form>
	";
}
echo "</center>$footer";
?>