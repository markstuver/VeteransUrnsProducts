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

include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";
include "checklogin.inc.php";
include "../admin/ashopconstants.inc.php";

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/af_orderhistory.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE sessionid='$affiliatesesid'";
$result = @mysqli_query($db, "$sql");

// Store affiliate information in variables...
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$affiliateid = @mysqli_result($result, 0, "affiliateid");

// Get statistics from database...
$selectorderids = "	<p><table class=\"ashopaffiliatehistorybox\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\">
	<tr class=\"ashopaffiliatehistoryrow\"><td align=\"left\"><span class=\"ashopaffiliatehistorytext1\">&nbsp;".REFERENCE."</span></td><td align=\"left\" width=\"150\"><span class=\"ashopaffiliatehistorytext1\">&nbsp;".DATETIME."</span></td><td align=\"center\" width=\"100\"><span class=\"ashopaffiliatehistorytext1\">".AMOUNT."</span></td></tr>";

$totalprovision = 0;
$totalourdebt = 0;
$sql="SELECT orders.date, orders.orderid, orders.invoiceid, orders.wholesale, orderaffiliate.* FROM orders, orderaffiliate WHERE orderaffiliate.affiliateid='$affiliateid' AND orderaffiliate.orderid=orders.orderid AND orders.paid != '0' AND orders.paid != '' AND orders.paid IS NOT NULL ORDER BY orderaffiliate.orderid DESC";
$result = @mysqli_query($db, "$sql");
$order = @mysqli_num_rows($result);
if (@mysqli_num_rows($result) != 0) {
  for ($i = 0; $i < @mysqli_num_rows($result);$i++) {
	  $orderdate = @mysqli_result($result, $i, "date");
	  $orderid = @mysqli_result($result, $i, "orderid");
	  $invoiceid = @mysqli_result($result, $i, "invoiceid");
	  if (empty($invoiceid)) $invoiceid = $orderid;
	  $wholesale = @mysqli_result($result, $i, "wholesale");
	  $paid = @mysqli_result($result, $i, "paid");
	  $paymethod = @mysqli_result($result, $i, "paymethod");
	  $provision = @mysqli_result($result, $i, "commission");
	  $secondtier = @mysqli_result($result, $i, "secondtier");
	  $tierlevel = $secondtier+1;
	  if (!$paid) $ourdebt += $provision;

	  $selectorderids .= "<tr><td align=\"left\"><span class=\"ashopaffiliatetext3\">";
	  if ($provision < 0) $selectorderids .= "Chargeback ";
	  $selectorderids .= "Order ID: $invoiceid";
	  if ($wholesale) $selectorderids .= " W";
	  if ($secondtier) $selectorderids .= ", ".TIER2." $tierlevel";
	  $selectorderids .= "</span></td><td><span class=\"ashopaffiliatetext3\">$orderdate</span></td><td align=\"right\"><span class=\"ashopaffiliatetext2\">";
	  if ($provision < 0) $selectorderids .= "<font color=\"red\">- ".$currencysymbols[$ashopcurrency]["pre"].number_format(-$provision,2,'.','')." ".$currencysymbols[$ashopcurrency]["post"];
	  else $selectorderids .= $currencysymbols[$ashopcurrency]["pre"].number_format($provision,2,'.','')." ".$currencysymbols[$ashopcurrency]["post"];
	  if ($provision < 0) $selectorderids .= "</font>";
	  $selectorderids .= "</span></td></tr>";

	  if ($paid && $provision > 0) {
		  $chargebackresult = @mysqli_query($db, "SELECT orderid FROM orderaffiliate WHERE orderid='$orderid' AND commission<0 AND paid>0 AND paid IS NOT NULL");
		  if (!@mysqli_num_rows($chargebackresult)) {
			  $selectorderids .= "<tr><td align=\"left\"><span class=\"ashopaffiliatetext3\">".PAIDBY." $paymethod</span></td><td><span class=\"ashopaffiliatetext3\">$paid</span></td><td align=\"right\"><span class=\"ashopaffiliatehistorytext2\">- ".$currencysymbols[$ashopcurrency]["pre"].number_format($provision,2,'.','')." ".$currencysymbols[$ashopcurrency]["post"]."</span></td></tr>";
		  }
	  }
  }
}
$selectorderids .= "<tr class=\"ashopaffiliatehistoryrow\"><td colspan=\"2\" align=\"right\"><span class=\"ashopaffiliatehistorytext1\">".TOTALUNPAID.":</span></td><td align=\"right\"><span class=\"ashopaffiliatehistorytext1\">".$currencysymbols[$ashopcurrency]["pre"].number_format($ourdebt,2,'.','')." ".$currencysymbols[$ashopcurrency]["post"]."</span></td></tr></table></p>";

// Get number of unread PMs...
$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND (hasbeenread='' OR hasbeenread='0' OR hasbeenread IS NULL)";
$unreadresult = @mysqli_query($db, "$sql");
$unreadcount = @mysqli_num_rows($unreadresult);

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/affiliate.html");

echo "<br><table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr><td align=\"left\"><span class=\"ashopaffiliateheader\">".WELCOME." $firstname $lastname! ".AFFILIATEID.": $affiliateid</span></td>$salesreplink</tr></table>
	<table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"affiliate.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".STATISTICS."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"affiliate.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".STATISTICS."\"></a></td>";
echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changeprofile.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".VIEWPROFILE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changepassword.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".CHANGEPASS."\"></a></td>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"parties.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".PARTIES."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"login.php?logout\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"login.php?logout\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
echo "</tr></table>
	<table align=\"center\" width=\"400\"><tr>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
echo "<td align=\"center\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".ORDERHISTORY."\" disabled></td>";
if ($activateleads) {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"leads.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LEADS."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"inbox.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
} else {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"inbox.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
}
echo "
	</tr></table>
	<br><span class=\"ashopaffiliateheader\">".COMMISSIONHISTORY."</span>$selectorderids";

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");

// Close database...
@mysqli_close($db);
?>