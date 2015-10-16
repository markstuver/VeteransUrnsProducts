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
include "language/$adminlang/editproduct.inc.php";
// Get context help for this page...
$contexthelppage = "editqtypricing";
include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Set default customer level...
if (empty($customerlevel) || !is_numeric($customerlevel)) $customerlevel = 0;

// Get product name and default price...
$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
$productname = @mysqli_result($result,0,"name");
$productprice = @mysqli_result($result,0,"price");
$productpricetext = @mysqli_result($result,0,"pricetext");
$quantitypricetype = @mysqli_result($result,0,"qtytype");
$qtycategory = @mysqli_result($result,0,"qtycategory");

// Check if there are any existing levels...
$result = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$productid'");
$levelsexist = @mysqli_num_rows($result);

// Deactivate qty pricing...
if ($deactivate) {
	@mysqli_query($db, "DELETE FROM qtypricelevels WHERE productid='$productid'");
	@mysqli_query($db, "UPDATE product SET pricetext='', qtytype='' WHERE productid='$productid'");
	$productpricetext = "";
	$quantitypricetype = "";
}

// Update pricing description and type...
if ($updatepricetext || $nqtytype || $npricetype) {
	if ($nqtytype == "3") {
		$qtycategory = $cat;
		$nqtytype = "2";
	} else $qtycategory = "";
	if ($npricetype == "1") {
		if ($nqtytype == "1") $nqtypricetype = "1";
		else $nqtypricetype = "2";
	} else {
		if ($nqtytype == "1") $nqtypricetype = "3";
		else $nqtypricetype = "4";
	}
	@mysqli_query($db, "UPDATE product SET pricetext='$nproductpricetext', qtytype='$nqtypricetype', qtycategory='$qtycategory' WHERE productid='$productid'");
	$productpricetext = $nproductpricetext;
	$quantitypricetype = $nqtypricetype;
}

// Check if any other product uses global qty-pricing calculation...
if ($quantitypricetype == "2" || $quantitypricetype == "4") {
	$result = @mysqli_query($db, "SELECT productid FROM product WHERE (qtytype='2' OR qtytype='4') AND productid!='$productid'");
	while($row = @mysqli_fetch_array($result)) $otherproducts[]=$row["productid"];
}

// Update selected qty-pricing level...
$duplicatelevel = "";
if ($updatepricelevel && !$delete) {
	if ($updatepricelevel == "new") {
		// Check if a qty pricing type has been selected...
		if (empty($quantitypricetype)) @mysqli_query($db, "UPDATE product SET qtytype='1' WHERE productid='$productid'");
		// Check if same or conflicting level exists...
		$result = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE levelquantity='$nlevelquantity' AND productid='$productid' AND customerlevel='$customerlevel'");
		if (@mysqli_num_rows($result)) $duplicatelevel = "true";
		$sql="INSERT INTO qtypricelevels (levelprice, levelquantity, productid, customerlevel) VALUES ('$nlevelprice', '$nlevelquantity', '$productid', '$customerlevel')";
		// Make sure zero level exists...
		if ($nlevelquantity != 0) {
			$result = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE levelquantity='0' AND productid='$productid' AND customerlevel='$customerlevel'");
			if (!@mysqli_num_rows($result)) @mysqli_query($db, "INSERT INTO qtypricelevels (levelprice, levelquantity, productid, customerlevel) VALUES ('$productprice', '0', '$productid', '$customerlevel')");
			$zerolevelset = 1;
		}
	} else {
		$sql="UPDATE qtypricelevels SET levelprice='$nlevelprice', levelquantity='$nlevelquantity' WHERE levelid='$updatepricelevel'";
		// Make sure zero level exists...
		if ($nlevelquantity != 0) {
			$result = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE levelquantity='0' AND productid='$productid'");
			if (!@mysqli_num_rows($result)) @mysqli_query($db, "INSERT INTO qtypricelevels (levelprice, levelquantity, productid) VALUES ('$productprice', '0', '$productid')");
		}
	}
	if (!$duplicatelevel) $result = @mysqli_query($db, "$sql");
} else if ($updatepricelevel && $delete) {
	$sql="DELETE FROM qtypricelevels WHERE levelid=$updatepricelevel";
	$result = @mysqli_query($db, "$sql");
	if (($quantitypricetype == "2" || $quantitypricetype == "4") && is_array($otherproducts)) foreach ($otherproducts as $productnumber=>$otherproductid) {
		@mysqli_query($db, "DELETE FROM qtypricelevels WHERE productid='$otherproductid' AND customerlevel='$customerlevel'");
		$result = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$productid' AND customerlevel='$customerlevel'");
		while($row = @mysqli_fetch_array($result)) @mysqli_query($db, "INSERT INTO qtypricelevels (levelprice, levelquantity, productid, customerlevel) VALUES ('{$row["levelprice"]}', '{$row["levelquantity"]}', '$otherproductid', '$customerlevel')");
	}
}

echo "$header
<div class=\"heading\">".QTYPRICINGLEVELS."</div>
<table align=\"center\" cellpadding=\"10\" width=\"100%\"><tr><td><center><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>";
if ($duplicatelevel) echo "<p><font face=\"Arial, Helvetica, sans-serif\" color=\"#900000\"><b>".ERROR."</b><br>".EQUALLEVELEXISTS."</font></p>";
echo "
	<form action=\"editqtypricing.php\" method=\"post\" name=\"pricelevelsettingsform\">
		<table width=\"50%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr><td class=\"formtitle\" align=\"left\"><a href= \"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip0');\" onmouseout=\"return nd();\"></a> ".DESCRIPTION.":</td></tr><tr><td align=\"left\"><input type=\"text\" name=\"nproductpricetext\" value=\"$productpricetext\" size=\"51\"><input type=\"hidden\" name=\"updatepricetext\" value=\"true\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"></td></tr>
		<tr><td class=\"formtitle\" align=\"left\"><a href= \"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> ".TYPE.": <select name=\"npricetype\"><option value=\"1\""; if ($quantitypricetype=="1" || $quantitypricetype=="2") echo " selected"; echo ">".QUANTITYDISCOUNT."<option value=\"2\""; if ($quantitypricetype=="3" || $quantitypricetype=="4") echo " selected"; echo ">".SEPARATELEVELS."</select></td></tr>
		<tr><td class=\"formtitle\" align=\"left\"><a href= \"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".CALCULATIONBASEDONQTYOF.": <select name=\"nqtytype\"><option value=\"1\""; if ($quantitypricetype=="1" || $quantitypricetype=="3") echo " selected"; echo ">".THISPRODUCT."</option><option value=\"3\""; if (($quantitypricetype=="2" || $quantitypricetype=="4") && !empty($qtycategory)) echo " selected"; echo ">".THISCATEGORY."</option><option value=\"2\""; if (($quantitypricetype=="2" || $quantitypricetype=="4") && empty($qtycategory)) echo " selected"; echo ">".ALLPRODUCTS."</option></select> <input type=\"hidden\" name=\"customerlevel\" value=\"$customerlevel\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"></td></tr></table></form>
		<form action=\"editqtypricing.php\" method=\"post\" name=\"customerlevelselectionform\">
		<table width=\"50%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr><td class=\"formtitle\" align=\"left\">".PRICELEVELSFOR." <select name=\"customerlevel\" onchange=\"document.customerlevelselectionform.submit()\"><option value=\"0\""; if ($customerlevel=="0") echo " selected"; echo ">".RETAILLVL."<option value=\"1\""; if ($customerlevel=="1") echo " selected"; echo ">".WHOLESALELVL;
		for ($lvl = 2; $lvl <= $pricelevels; $lvl++) {
			echo "<option value=\"$lvl\""; if ($customerlevel==$lvl) echo " selected"; echo ">".WHOLESALE." ".LVL." $lvl";
		}
		echo "</select> ".LVLCUSTOMERS."</td></tr></table><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form>
	<form action=\"editqtypricing.php\" method=\"post\" name=\"pricelevelform$i\">
		<table width=\"50%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#D0D0D0\">
		<tr><td width=\"30%\" class=\"formtitle\" align=\"left\">".ADDNEWPRICELEVEL."</td></tr><tr><td class=\"formlabel\" align=\"left\">".PRICE.": ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"nlevelprice\" "; if(!$levelsexist) echo "value=\"$productprice\" "; echo "size=\"5\">".$currencysymbols[$ashopcurrency]["post"]." ".PERITEMONQTYABOVE." <input type=\"text\" name=\"nlevelquantity\" "; if(!$levelsexist) echo "value=\"0\" "; echo "size=\"5\"><input type=\"hidden\" name=\"updatepricelevel\" value=\"new\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"customerlevel\" value=\"$customerlevel\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"> <input type=\"submit\" name=\"add\" value=\"".ADD."\"></td></tr></table></form>";

// Display current pricing levels...
$sql="SELECT * FROM qtypricelevels WHERE productid='$productid' AND customerlevel='$customerlevel' ORDER BY levelquantity DESC";
$result = @mysqli_query($db, "$sql");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$levelid = @mysqli_result($result, $i, "levelid");
	$levelprice = @mysqli_result($result, $i, "levelprice");
	$levelquantity = @mysqli_result($result, $i, "levelquantity");

	echo "<form action=\"editqtypricing.php\" method=\"post\" name=\"qtypriceform$i\">
		<table width=\"50%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#D0D0D0\">
		<tr><td class=\"formlabel\">".PRICE.": ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"nlevelprice\" size=\"5\" value=\"$levelprice\">".$currencysymbols[$ashopcurrency]["post"]." ".PERITEMONQTYABOVE." <input type=\"text\" name=\"nlevelquantity\" size=\"5\" value=\"$levelquantity\"></td></tr><tr><td align=\"right\"><input type=\"hidden\" name=\"updatepricelevel\" value=\"$levelid\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"customerlevel\" value=\"$customerlevel\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\">";
	if ($levelquantity > 0) echo " <input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\">";
	echo "</td></tr></table></form>";
}

// Close database...
@mysqli_close($db);

echo "<form action=\"editqtypricing.php\" method=\"post\">
		<table width=\"50%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr><td class=\"formlabel\" align=\"right\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"customerlevel\" value=\"$customerlevel\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"submit\" name=\"deactivate\" class=\"widebutton\" value=\"".DEACTIVATEQTYPRICING."\"></form><br><br><input type=\"button\" value=\"".FINISH."\" onClick=\"document.location.href='editcatalogue.php?cat=$cat&pid=$pid&resultpage=$resultpage&search=$search'\"></td></tr></table></center></td></tr></table>$footer";
?>