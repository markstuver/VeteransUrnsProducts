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

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/af_affiliate.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Store hideprice...
if (!empty($_POST["update"]) && $_POST["update"] == "Update"){
	if (!empty($_POST["nhideprice"]) && $_POST["nhideprice"] == "1") @mysqli_query($db,"UPDATE affiliate SET hideprice='' WHERE sessionid='$affiliatesesid'");
	else @mysqli_query($db,"UPDATE affiliate SET hideprice='1' WHERE sessionid='$affiliatesesid'");
}

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE sessionid='$affiliatesesid'";
$result = @mysqli_query($db, "$sql");

// Reset statistics...
$click = 0;
$provision = 0;
$ourdebt = 0;

// Store affiliate information in variables...
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$affiliateid = @mysqli_result($result, 0, "affiliateid");
$correctpasswd = @mysqli_result($result, 0, "password");
$referralcode = @mysqli_result($result, 0, "referralcode");
$click = @mysqli_result($result, 0, "clicks");
$commissionlevel = @mysqli_result($result, 0, "commissionlevel");
$excludecategorieslist = @mysqli_result($result, 0, "excludecategories");
$excludeproductslist = @mysqli_result($result, 0, "excludeproducts");
$excludecategories = explode("|",$excludecategorieslist);
$excludeproducts = explode("|",$excludeproductslist);
$hideprice = @mysqli_result($result, 0, "hideprice");

// Hide a category...
if (!empty($category) && is_numeric($category)) {
	if (!in_array($category,$excludecategories)) $excludecategorieslist .= "$category|";
	else {
		$excludecategorieslist = "";
		foreach ($excludecategories as $thiscategory) if ($thiscategory != $category && !empty($thiscategory)) $excludecategorieslist .= "$thiscategory|";
	}
	@mysqli_query($db, "UPDATE affiliate SET excludecategories='$excludecategorieslist' WHERE sessionid='$affiliatesesid'");
	if (empty($r) || $r != "1") {
		header("Location: ../index.php?cat=$category");
		exit;
	} else {
		header("Location: affiliate.php");
		exit;
	}
}

// Hide a product...
if (!empty($product) && is_numeric($product)) {
	if (!in_array($product,$excludeproducts)) $excludeproductslist .= "$product|";
	else {
		$excludeproductslist = "";
		foreach ($excludeproducts as $thisproduct) if (!empty($thisproduct) && $thisproduct != $product) $excludeproductslist .= "$thisproduct|";
	}
	@mysqli_query($db, "UPDATE affiliate SET excludeproducts='$excludeproductslist' WHERE sessionid='$affiliatesesid'");
	if (empty($r) || $r != "1") {
		header("Location: ../index.php?product=$product");
		exit;
	} else {
		header("Location: affiliate.php");
		exit;
	}
}

// Get statistics from database...
$sql="SELECT * FROM affiliate WHERE referedby='$affiliateid'";
$result = @mysqli_query($db, "$sql");
$referrals = @mysqli_num_rows($result);
$sql="SELECT orderaffiliate.orderid FROM orders, orderaffiliate WHERE orderaffiliate.affiliateid='$affiliateid' AND orderaffiliate.orderid=orders.orderid AND (orderaffiliate.secondtier IS NULL OR orderaffiliate.secondtier='0')";
$result = @mysqli_query($db, "$sql");
$order = @mysqli_num_rows($result);
if (!$order) $order = "0";
$sql="SELECT orderaffiliate.paid, orderaffiliate.commission FROM orders, orderaffiliate WHERE orderaffiliate.affiliateid='$affiliateid' AND orderaffiliate.orderid=orders.orderid";
$result = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($result)) {
  while ($row = @mysqli_fetch_array($result)) {
	  $paid = $row["paid"];
	  $commission = $row["commission"];
	  $provision += $commission;
	  if (!$paid) $ourdebt += $commission;
  }
}
$sql="SELECT orderaffiliate.orderid FROM orders, orderaffiliate WHERE orderaffiliate.affiliateid='$affiliateid' AND orderaffiliate.orderid=orders.orderid AND (orderaffiliate.secondtier>0 AND orderaffiliate.secondtier IS NOT NULL)";
$result = @mysqli_query($db, "$sql");
$secondtier = @mysqli_num_rows($result);
if (!$secondtier) $secondtier = "0";

// Get number of unread PMs...
$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND (hasbeenread='' OR hasbeenread='0' OR hasbeenread IS NULL)";
$unreadresult = @mysqli_query($db, "$sql");
$unreadcount = @mysqli_num_rows($unreadresult);

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/affiliate.html");

echo "<br><table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr><td align=\"left\"><span class=\"ashopaffiliateheader\">".WELCOME." $firstname $lastname! ".AFFILIATEID.": $affiliateid</span></td>$salesreplink</tr></table>
	<table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"Catalog\" disabled></td>";
else echo "<td align=\"center\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"Catalog\" disabled></td>";
echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changeprofile.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".VIEWPROFILE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changepassword.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".CHANGEPASS."\"></a></td>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"parties.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".PARTIES."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"login.php?logout\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"login.php?logout\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
echo "</tr></table>";
/*
echo "
	<table align=\"center\" width=\"400\"><tr>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"orderhistory.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".ORDERHISTORY."\"></a></td>";
if ($activateleads) {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"leads.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LEADS."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"inbox.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
} else {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"inbox.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></form></td>";
}
echo "</tr></table>";
*/
$affiliatelink = "$ashopurl/affiliate.php?id=$affiliateid";
$affiliatelinklength = strlen($affiliatelink);
echo "
	<p><span class=\"ashopaffiliatetext1\">Your Referral Link:</span> <input id=\"affiliatelink\" type=\"text\" size=\"$affiliatelinklength\" value=\"$ashopurl/affiliate.php?id=$affiliateid\" onclick=\"document.getElementById('affiliatelink').select();\"></p>";
if ($commissionlevel == "2") echo "<p><span class=\"ashopaffiliatetext1\">".ACCOUNTLEVEL.":</span><span class=\"ashopaffiliatetext2\"> ".UPGRADED."</span></p>";
/*
echo "
	<p><span class=\"ashopaffiliatetext1\">".STATISTICS.":</span></p><span class=\"ashopaffiliatetext2\">
	<p>".CLICKS.": $click<br>".ORDERS.": $order";

if ($upgradeaffiliate && $commissionlevel != "2") {
	$ordersleft = $upgradeaffiliate - $order;
	echo "<br>".ORDERSLEFT.": $ordersleft";
}

if ($secondtieractivated) echo "<br>".RECRUITED.": $referrals<br>".TWOTIERORDERS.": $secondtier";

echo "<br>".TOTALEARNINGS.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($provision,2,'.','')." ".$currencysymbols[$ashopcurrency]["post"]."<br>".OWEYOU.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($ourdebt,2,'.','')." ".$currencysymbols[$ashopcurrency]["post"]."</p></span></font></font>";

// Get top 5 referers...
$result = @mysqli_query($db, "SELECT * FROM affiliatereferer WHERE affiliateid='$affiliateid' ORDER BY clicks DESC LIMIT 5");
if (@mysqli_num_rows($result)) {
	echo "
	<p><span class=\"ashopaffiliatetext1\">".TOPREFERERS.":</span><span class=\"ashopaffiliatetext2\"><br>";
	while ($row = @mysqli_fetch_array($result)) {
		$referer = $row["referer"];
		$clicks = $row["clicks"];
		echo "<br>$referer - $clicks ".REFERERCLICKS;
	}
	echo "</p>";
}
*/

echo "<p><span class=\"ashopaffiliatetext1\">View catalog <a href=\"../index.php\" target=\"_blank\">here</a> to hide or unhide categories and/or products from your pages.</span></p>
<p><form action=\"affiliate.php\" id=\"priceform\" method=\"post\"><span class=\"ashopaffiliatetext1\"><input type=\"checkbox\" name=\"nhideprice\""; if (!$hideprice) echo " checked"; echo " value=\"1\"> Show price tags.</span> <input type=\"submit\" name=\"update\" value=\"Update\"></form></p>
<p><span class=\"ashopaffiliatetext1\"><b>Hidden categories:</b></span></p>
<ul>";
foreach ($excludecategories as $thisexcludedcategory) {
	$excludedcategoryresult = @mysqli_query($db, "SELECT name FROM category WHERE categoryid='$thisexcludedcategory'");
	$excludedcategoryrow = @mysqli_fetch_array($excludedcategoryresult);
	$excludedcategoryname = $excludedcategoryrow["name"];
	if (!empty($thisexcludedcategory)) echo "<li><span class=\"ashopaffiliatetext1\"><a href=\"../index.php?cat=$thisexcludedcategory\" target=\"_blank\">$excludedcategoryname</a> [<a href=\"affiliate.php?category=$thisexcludedcategory&r=1\">Unhide</a>]</span></li>\n";
}
echo "</ul>
<p><span class=\"ashopaffiliatetext1\"><b>Hidden products:</b></span></p>
<ul>";
foreach ($excludeproducts as $thisexcludedproduct) {
	$excludedproductresult = @mysqli_query($db, "SELECT name FROM product WHERE productid='$thisexcludedproduct'");
	$excludedproductrow = @mysqli_fetch_array($excludedproductresult);
	$excludedproductname = $excludedproductrow["name"];
	if (!empty($thisexcludedproduct)) echo "<li><span class=\"ashopaffiliatetext1\"><a href=\"../index.php?product=$thisexcludedproduct\" target=\"_blank\">$excludedproductname</a> [<a href=\"affiliate.php?product=$thisexcludedproduct&r=1\">Unhide</a>]</span></li>\n";
}
echo "</ul>";

// Close database...

@mysqli_close($db);

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
?>