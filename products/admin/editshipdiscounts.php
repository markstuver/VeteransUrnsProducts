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
include "ashopconstants.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/configure.inc.php";
// Get context help for this page...
$contexthelppage = "editshipdiscounts";
include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Update selected payment option...
$duplicatediscount = "";
if ($updateshipdiscount && !$delete) {
	if (!$nfee) $nfee = 0.00;
	if ($updateshipdiscount == "new") {
		// Check if same or conflicting discount exists...
		$result = @mysqli_query($db, "SELECT * FROM shipdiscounts WHERE quantity='$nquantity' AND local='$nlocal' AND (shipoptionid='$nshipoption' OR '$nshipoption'='0')");
		if (@mysqli_num_rows($result)) $duplicatediscount = "true";
		$sql="INSERT INTO shipdiscounts (value, quantity, local, shipoptionid) VALUES ('$nvalue', '$nquantity', '$nlocal', '$nshipoption')";
	} else $sql="UPDATE shipdiscounts SET value='$nvalue', quantity='$nquantity', local='$nlocal', shipoptionid='$nshipoption' WHERE shipdiscountid='$updateshipdiscount'";
	if (!$duplicatediscount) $result = @mysqli_query($db, "$sql");
} else if ($updateshipdiscount && $delete) {
	$sql="DELETE FROM shipdiscounts WHERE shipdiscountid=$updateshipdiscount";
	$result = @mysqli_query($db, "$sql");
}

// Get shipping options...
$result = @mysqli_query($db, "SELECT * FROM shipoptions");
$shipoptionselectstring = "<select name=\"nshipoption\"><option value=\"0\">".ANY;
if (@mysqli_num_rows($result)) for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$shipoptionselectstring .= "<option value=\"".@mysqli_result($result, $i, "shipoptionid")."\">".@mysqli_result($result, $i, "description");
}

echo "$header
<div class=\"heading\">".SHIPPINGDISCOUNTS." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table align=\"center\" cellpadding=\"10\"><tr><td><center>";
if ($duplicatediscount) echo "<p><font face=\"Arial, Helvetica, sans-serif\" color=\"#900000\"><b>".ERROR."</b><br>".EQUALDISCOUNTEXISTS."</font></p>";
echo "</center>

	<form action=\"editshipdiscounts.php\" method=\"post\" name=\"shipdiscountform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#D0D0D0\">
		<tr><td width=\"30%\" class=\"formtitle\">".ADDNEWSHIPPINGDISCOUNT."</td></tr><tr><td class=\"formlabel\" align=\"left\">".DISCOUNT.": ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"nvalue\" size=\"5\">".$currencysymbols[$ashopcurrency]["post"]." ".ONQUANTITIESOVER." <input type=\"text\" name=\"nquantity\" size=\"5\"></td></tr><tr><td class=\"formlabel\" align=\"left\">".SHIPPED." <select name=\"nlocal\"><option value=\"0\">".INTERNATIONALLY."<option value=\"1\">".LOCALLY."</select> ".WITHOPTION.": $shipoptionselectstring</td></tr><tr><td align=\"right\"><input type=\"hidden\" name=\"updateshipdiscount\" value=\"new\"><input type=\"submit\" name=\"add\" value=\"".ADD."\"></td></tr></table></form><br>";

// Display current shipping discounts...
$sql="SELECT * FROM shipdiscounts ORDER BY shipdiscountid DESC";
$result = @mysqli_query($db, "$sql");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$shipdiscountid = @mysqli_result($result, $i, "shipdiscountid");
	$shipdiscountvalue = @mysqli_result($result, $i, "value");
	$shipdiscountquantity = @mysqli_result($result, $i, "quantity");
	$shipdiscountlocal = @mysqli_result($result, $i, "local");
	$shipdiscountoption = @mysqli_result($result, $i, "shipoptionid");

	// Get shipping options...
	$sql = "SELECT * FROM shipoptions";
	if($shipdiscountlocal) $sql .= " WHERE shipped='local'";
	else $sql .= " WHERE shipped='international'";
	$sql .= " OR shipped='both'";
	$result2 = @mysqli_query($db, $sql);
	$shipoptionselectstring = "<select name=\"nshipoption\"><option value=\"0\">".ANY;
	if (@mysqli_num_rows($result2)) for ($j = 0; $j < @mysqli_num_rows($result2); $j++) {
		$thisshipoptionid = @mysqli_result($result2, $j, "shipoptionid");
		$shipoptionselectstring .= "<option value=\"$thisshipoptionid\"";
		if ($shipdiscountoption == $thisshipoptionid) $shipoptionselectstring .= " selected";
		$shipoptionselectstring .= ">".@mysqli_result($result2, $j, "description");
	}

	echo "<form action=\"editshipdiscounts.php\" method=\"post\" name=\"shipdiscountform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#D0D0D0\">
		<tr><td class=\"formlabel\">".DISCOUNT.": ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"nvalue\" size=\"5\" value=\"$shipdiscountvalue\">".$currencysymbols[$ashopcurrency]["post"]." ".ONQUANTITIESOVER." <input type=\"text\" name=\"nquantity\" size=\"5\" value=\"$shipdiscountquantity\"></td></tr><tr><td class=\"formlabel\">".SHIPPED." <select name=\"nlocal\"><option value=\"0\">".INTERNATIONALLY."<option value=\"1\" "; if ($shipdiscountlocal) echo "selected"; echo ">".LOCALLY."</select> ".WITHOPTION.": $shipoptionselectstring</td></tr><tr><td align=\"right\"><input type=\"hidden\" name=\"updateshipdiscount\" value=\"$shipdiscountid\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\"></td></tr></table></form><br>";
}

// Close database...
@mysqli_close($db);

echo "</table>$footer";
?>