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

include "checklicense.inc.php";
include "ashopconstants.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/index.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check if there is an important announcement to read...
include "checkannouncement.inc.php";
if (!empty($announcement) && is_numeric($announcement) && $readannouncement != $announcement) {
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$announcement' WHERE prefname='readannouncement'");
	header("Location: resources.php");
	exit;
}

if ($userid == "1" && $action == "resetcounter" && $yes) {
	$visitcounterinstalldate = date("d.m.Y", time()+$timezoneoffset);
	@mysqli_query($db, "UPDATE visitcounter SET total='0', today='0', installdate='$visitcounterinstalldate' WHERE extrafield='ashopadmin'");
	@mysqli_query($db, "DELETE FROM visitcounter_today");
	@mysqli_query($db, "DELETE FROM visitcounter_online");
	header("Location: index.php");
	exit;
}

// Get AWeber lists...
if (!empty($aweberauthcode)) {
	require_once('../includes/aweber/aweber_api.php');
	$auth = explode("|",$aweberauthcode);
	list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = $auth;
	$aweber = new AWeberAPI($consumerKey, $consumerSecret);
	$aweber->adapter->debug = false;
	$account = $aweber->getAccount($accessKey, $accessSecret);
	@mysqli_query($db, "DELETE FROM autoresponders");
	foreach($account->lists as $list) {
		$responderid = $list->id;
		$respondername = $list->name;
		if (!empty($responderid) && !empty($respondername)) @mysqli_query($db, "INSERT INTO autoresponders (responderid, name) VALUES ('$responderid','$respondername')");
	}
}
 
echo $header;
if ($error == 1) echo "<p><font face=\"Arial, Helvetica, sans-serif\" color=\"#900000\"><b>Error! The user name or password for the database is incorrect!<br>Check your config.inc.php!</b></font></p>";
elseif ($error == 2) echo "<p><font face=\"Arial, Helvetica, sans-serif\" color=\"#900000\"><b>Error! The database name is incorrect!<br>Check your config.inc.php!</b></font></p>";
if ($activated) echo "<p><font face=\"Arial, Helvetica, sans-serif\" color=\"#009000\"><b>".ORDERACTIVATIONCOMPLETED."</b></font></p>";
if ($licwarningmessage) echo $licwarningmessage;
echo  "<div class=\"heading\">$ashopname</div><table cellpadding=\"0\" align=\"center\"><tr><td>";

if ($userid == "1") {
	if ($action == "resetcounter" && !$yes) {
		echo "<center><p>".AREYOUSURERESETCOUNTER."</font></p>
			<form action=\"index.php\" method=\"post\">
				<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
				<tr>
				<td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
				<input type=\"button\" name=\"no\" value=\"".NO."\" onClick=\"javascript:history.back()\"></td>
				</tr></table><input type=\"hidden\" name=\"action\" value=\"resetcounter\"></form>
				</center>
				</td></tr></table>$footer";
		exit;
	}

// Begin Stats...
echo "<table width=\"700\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr valign=\"top\"><td width=\"50%\">";

// Report visitors....
$time = time()+$timezoneoffset;
$date = date("d.m.Y", $time);
$result = @mysqli_query($db, "SELECT * FROM visitcounter WHERE id = '$userid'");
$row = @mysqli_fetch_array($result);
$currenttoday = $row["currenttoday"];
$keepcurrent = $row["keepcurrent"];
$total = $row["total"];
$today = $row["today"];
$installdate = explode(".",$row["installdate"]);
$midnight = strtotime("$date 00:00:00");
$yesterday = $midnight-1;
$yesterdayresult = @mysqli_query($db, "SELECT * FROM visitcounter_today WHERE time < '$yesterday'");
if (@mysqli_num_rows($yesterdayresult)) {
	$today = $today - @mysqli_num_rows($yesterdayresult);
	@mysqli_query($db, "UPDATE visitcounter SET today='$today' WHERE id = '$userid'");
	@mysqli_query($db, "DELETE FROM visitcounter_today WHERE time < '$yesterday'");
}
$lastactivetime = $time-$keepcurrent;
@mysqli_query($db, "DELETE FROM visitcounter_online WHERE time < '$lastactivetime'");
$result = @mysqli_query($db, "SELECT COUNT(*) FROM visitcounter_online");
$row = @mysqli_fetch_array($result);
$online = $row[0];
echo "<table width=\"280\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\" border=\"0\" style=\"border: 1px solid #D0D0D0;\"><tr valign=\"top\"><td width=\"100%\"><img src=\"images/icon_visitors.gif\" border=\"0\" alt=\"".VISITORS."\" title=\"".VISITORS."\"> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".VISITORS."</b><br>".CURRENTLYONLINE.": $online<br>".TOTALTODAY.": $today<br>".SINCE." {$installdate[2]}-{$installdate[1]}-{$installdate[0]}: $total<br><a href=\"index.php?action=resetcounter\" class=\"sm\">".RESETCOUNTER."</a></font></td></tr></table><br>";

// Sales reports...
echo "<table width=\"280\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\" border=\"0\" style=\"border: 1px solid #D0D0D0;\"><tr valign=\"top\"><td width=\"100%\"><a href=\"salesreport.php\"><img src=\"images/icon_reports.gif\" border=\"0\" alt=\"".GENERATESALESREPORTS."\" title=\"".GENERATESALESREPORTS."\"></a> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b><a href=\"salesreport.php\">".SALESREPORTS."</a></b><br>";

// Total Sales today...
$today = date("Y-m-d", time()+$timezoneoffset);
$today .= " 00:00:00";
if ($userid > 1) $sql="SELECT SUM(price) AS subtotal FROM orders WHERE userid LIKE '%|$userid|%' AND date > '$today' AND paid != ''";
else $sql="SELECT SUM(price) AS subtotal FROM orders WHERE date > '$today' AND paid != ''";
$result = @mysqli_query($db, $sql);
$subtotal = @mysqli_result($result,0,"subtotal");
echo TOTALTODAY.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($subtotal,2,'.','').$currencysymbols[$ashopcurrency]["post"]."<br>";

// Total Sales MTD...
$firstdayofmonth = date("Y-m", time()+$timezoneoffset);
$firstdayofmonth .= "-01 00:00:00";
if ($userid > 1) $sql="SELECT SUM(price) AS subtotal FROM orders WHERE userid LIKE '%|$userid|%' AND date >= '$firstdayofmonth' AND paid != ''";
else $sql="SELECT SUM(price) AS subtotal FROM orders WHERE date >= '$firstdayofmonth' AND paid != ''";
$result = @mysqli_query($db, $sql);
$subtotal = @mysqli_result($result,0,"subtotal");
echo TOTALMTD.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($subtotal,2,'.','').$currencysymbols[$ashopcurrency]["post"]."<br>";

// Total Orders today...
$today = date("Y-m-d", time()+$timezoneoffset);
$today .= " 00:00:00";
if ($userid > 1) $sql="SELECT * FROM orders WHERE userid LIKE '%|$userid|%' AND date > '$today' AND paid != ''";
else $sql="SELECT * FROM orders WHERE date > '$today' AND paid != ''";
$result = @mysqli_query($db, $sql);
$numberorders = @mysqli_num_rows($result);
echo ORDERSTODAY.": $numberorders<br>";

// Total Sales MTD...
$firstdayofmonth = date("Y-m", time()+$timezoneoffset);
$firstdayofmonth .= "-01 00:00:00";
if ($userid > 1) $sql="SELECT * FROM orders WHERE userid LIKE '%|$userid|%' AND date >= '$firstdayofmonth' AND paid != ''";
else $sql="SELECT * FROM orders WHERE date >= '$firstdayofmonth' AND paid != ''";
$result = @mysqli_query($db, $sql);
$numberorders = @mysqli_num_rows($result);
echo ORDERSMTD.": $numberorders<br>";

// Unpaid orders...
$sql = "SELECT date FROM orders WHERE date != '' ORDER BY date LIMIT 1";
$result = @mysqli_query($db, "$sql");
$mindate = explode("-",@mysqli_result($result, 0, "date"));
$startyear = $mindate[0];
$startmonth = $mindate[1];
$startday = explode(" ",$mindate[2]);
$startday = $startday[0];
if ($userid > 1) $sql="SELECT * FROM orders WHERE userid LIKE '%$userid|%' AND date IS NOT NULL AND date != '' AND paid = ''";
else $sql="SELECT * FROM orders WHERE date IS NOT NULL AND date != '' AND paid = ''";
$result = @mysqli_query($db, $sql);
$numberunpaid = @mysqli_num_rows($result);
// Get the current month and day...
$currentmonth = date("m", time()+$timezoneoffset);
$currentday = date("d", time()+$timezoneoffset);
$currentyear = date("Y", time()+$timezoneoffset);
echo "<a href=\"salesreport.php?reporttype=unpaid&generate=Edit&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$currentyear&tomonth=$currentmonth&today=$currentday&orderby=date&ascdesc=asc\">".UNPAIDORDERS."</a>: $numberunpaid<br>";

// Customer count...
if ($userid > 1) $result = @mysqli_query($db, "SELECT DISTINCT orders.customerid FROM orders, customer WHERE userid LIKE '%|$userid|%' AND customer.firstname != '' AND customer.email != '' AND orders.customerid=customer.customerid");
else $result = @mysqli_query($db, "SELECT * FROM customer WHERE customer.firstname != '' AND customer.email != '' AND customer.password != '' AND customer.password IS NOT NULL");
$numbercustomers = @mysqli_num_rows($result);
echo "<a href=\"salesadmin.php\">".CUSTOMERS."</a>: $numbercustomers";


echo "</font></td></tr></table><br>";

// Reset affiliate statistics...
$totalclicks = 0;
$numberorders = 0;
$totalcommission = 0;
$totalunpaidcommission = 0;

// Check if there are any affiliates...
$result = @mysqli_query($db, "SELECT * FROM affiliate");
$numberaffiliates = @mysqli_num_rows($result);
if ($numberaffiliates) {

	// Get affiliate commission from database...
	if ($userid > 1) $sql="SELECT SUM(orderaffiliate.commission) AS totalcommission FROM orders, orderaffiliate WHERE orders.userid LIKE '%|$userid|%' AND orders.orderid=orderaffiliate.orderid";
	else $sql="SELECT SUM(orderaffiliate.commission) AS totalcommission FROM orderaffiliate";
	$result = @mysqli_query($db, "$sql");
	$totalcommission = @mysqli_result($result, 0, "totalcommission");
	echo "<table width=\"280\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\" border=\"0\" style=\"border: 1px solid #D0D0D0;\"><tr valign=\"top\"><td width=\"100%\"><a href=\"affiliatestats.php\"><img src=\"images/icon_affiliatereports.gif\" border=\"0\" alt=\"".VIEWAFFILIATES."\" title=\"".VIEWAFFILIATES."\"></a> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b><a href=\"affiliatestats.php\">".AFFILIATESUC."</a></b><br>".COMMISSIONSEARNED.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($totalcommission,2,'.','');
	if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];

	// Get unpaid affiliate commission from database...
	if ($userid > 1) $sql="SELECT SUM(orderaffiliate.commission) AS totalcommission FROM orderaffiliate, orders WHERE (orderaffiliate.paid=0 OR orderaffiliate.paid IS NULL) AND orders.orderid=orderaffiliate.orderid AND orders.userid LIKE '%|$userid|%'";
	else $sql="SELECT SUM(orderaffiliate.commission) AS totalcommission FROM orderaffiliate WHERE orderaffiliate.paid=0 OR orderaffiliate.paid IS NULL";
	$result = @mysqli_query($db, "$sql");
	$totalunpaidcommission = @mysqli_result($result, 0, "totalcommission");
	echo "<br>".UNPAIDCOMMISSIONS.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($totalunpaidcommission,2,'.','');
	if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];

	// Get total number of affiliate orders...
	if ($userid > 1) $sql="SELECT * FROM orders, orderaffiliate WHERE orders.userid LIKE '%|$userid|%' AND orders.orderid=orderaffiliate.orderid";
	else $sql="SELECT * FROM orderaffiliate";
	$result = @mysqli_query($db, $sql);
	$numberorders = @mysqli_num_rows($result);
	echo "<br>".AFFILIATEORDERS.": $numberorders";

	// Total number of clicks...
	if ($userid == "1") {
		$sql="SELECT SUM(clicks) AS totalclicks FROM affiliate";
		$result2 = @mysqli_query($db, $sql);
		$totalclicks = @mysqli_result($result2, 0, "totalclicks");
		echo "<br>".CLICKSTODATE.": $totalclicks";
	}

	// Total number of affiliates...
	echo "<br>".AFFILIATECOUNT.": $numberaffiliates</font></td></tr></table>";
}
$ashopversion = substr($ashopversion,0,5);
echo "</td><td width=\"50%\"><table width=\"280\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\" border=\"0\" style=\"border: 1px solid #D0D0D0;\"><tr valign=\"top\"><td width=\"100%\"><a href=\"resources.php\"><img src=\"images/icon_resources.gif\" border=\"0\" alt=\"".CHECKFORUPDATESRESOURCES."\" title=\"".CHECKFORUPDATESRESOURCES."\"></a> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b><a href=\"resources.php\">".CARTRESOURCES."</a></b><br>".VERSION.": AShop ";
if (file_exists("$ashoppath/members/index.php")) echo "V ";
else echo "GPL ";
echo "$ashopversion<br><a href=\"resources.php\">".CHECKFORUPDATES."</a></font></td></tr></table><br><table width=\"280\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\" border=\"0\" style=\"border: 1px solid #D0D0D0;\"><tr valign=\"top\"><td width=\"100%\"><a href=\"backupdb.php\"><img src=\"images/icon_backup.gif\" border=\"0\"></a> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><font color=\"#000000\"><b><a href=\"backupdb.php\">".BACKUPDATABASE."</a></b></font><br><a href=\"importdb.php\">".RESTOREDATABASE."</a> (".CAUTION.")</font></td></tr></table><br>";

$result = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND ((prodtype != 'content' AND prodtype != 'mallfee') OR prodtype IS NULL) AND name != 'AShopFirstPage' AND name != 'AShopAboutPage' AND name != 'AShopTermsPage' AND (copyof IS NULL OR copyof = '' OR copyof = '0')");
$active = @mysqli_num_rows($result);
$result = @mysqli_query($db, "SELECT * FROM product WHERE active!='1' AND ((prodtype != 'content' AND prodtype != 'mallfee') OR prodtype IS NULL) AND name != 'AShopFirstPage' AND name != 'AShopAboutPage' AND name != 'AShopTermsPage' AND (copyof IS NULL OR copyof = '' OR copyof = '0')");
$inactive = @mysqli_num_rows($result);
$total = $active+$inactive;
echo "<table width=\"280\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\" border=\"0\" style=\"border: 1px solid #D0D0D0;\"><tr valign=\"top\"><td width=\"100%\"><img src=\"images/icon_product.gif\" border=\"0\" alt=\"".PRODUCTS."\" title=\"".PRODUCTS."\"> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b><a href=\"editcatalogue.php\">".PRODUCTS."</a></b><br>".ACTIVE.": $active<br>".INACTIVE.": $inactive<br>".TOTAL.": $total";
if ($saasuwsaccesskey && $saasufileid) echo "<br><a href=\"saasuexport.php\">".EXPORTTOSAASU."</a>";
echo "</font></td></tr></table><br>";

$result = @mysqli_query($db, "SELECT * FROM searchstatistics");
$numberofsearches = @mysqli_num_rows($result);
$today = date("Y-m-d", time()+$timezoneoffset);
$result = @mysqli_query($db, "SELECT * FROM searchstatistics WHERE date LIKE '$today%'");
$searchestoday = @mysqli_num_rows($result);
echo "<table width=\"280\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\" border=\"0\" style=\"border: 1px solid #D0D0D0;\"><tr valign=\"top\"><td width=\"100%\"><img src=\"images/icon_search.gif\" border=\"0\" alt=\"".SEARCHES."\" title=\"".SEARCHES."\"> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".SEARCHES."</b><br>".SEARCHESTODAY.": $searchestoday<br>".TOTALSEARCHES.": $numberofsearches<br><a href=\"searchstats.php\">".SEARCHSTATS."</a></font></td></tr></table>
</td></tr>";
} else if ($memberprodmanage && file_exists("$ashoppath/admin/welcome.txt")) {
	echo "<table width=\"450\" align=\"center\" cellpadding=\"10\" cellspacing=\"10\" border=\"0\"><tr valign=\"top\"><td width=\"50%\">";
	include "welcome.txt";
	echo "</td></tr></table>";
}
echo "</td></tr></table></center></td></tr></table>$footer";
?>