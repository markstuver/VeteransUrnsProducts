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
include "language/$adminlang/affiliates.inc.php";

if (empty($affiliateid) || !is_numeric($affiliateid)) {
	header ("Location: affiliateadmin.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
$result = @mysqli_query($db, "SELECT * FROM affiliate WHERE affiliateid='$affiliateid'");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$referralcode = @mysqli_result($result, 0, "referralcode");

// Update selected payment option...
if ($add) @mysqli_query($db, "INSERT INTO storediscounts (code, value, type, affiliate) VALUES ('$referralcode', '$value', '$type', '$affiliateid')");
else if ($update && $discountid) @mysqli_query($db, "UPDATE storediscounts SET value='$value', type='$type' WHERE discountid='$discountid'");
else if ($delete && $discountid) @mysqli_query($db, "DELETE FROM storediscounts WHERE discountid='$discountid'");

// Check for existing referral discount...
$result = @mysqli_query($db, "SELECT discountid, value, type FROM storediscounts WHERE code='$referralcode' AND affiliate='$affiliateid'");
$discountid = @mysqli_result($result, 0, "discountid");
$oldvalue = @mysqli_result($result, 0, "value");
$oldtype = @mysqli_result($result, 0, "type");

echo "$header
<div class=\"heading\">".REFERRALDISCOUNTSFOR." $firstname $lastname, ".AFFILIATEID." $affiliateid <a href=\"editaffiliate.php?affiliateid=$affiliateid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFORAFFILIATE." $affiliateid\" title=\"".PROFILEFORAFFILIATE." $affiliateid\" border=\"0\"></a>&nbsp;<a href=\"affiliatedetail.php?affiliateid=$affiliateid\"><img src=\"images/icon_history.gif\" alt=\"".STATISTICSFORAFFILIATE." $affiliateid\" title=\"".STATISTICSFORAFFILIATE." $affiliateid\" border=\"0\"></a>&nbsp;<a href=\"editaffiliate.php?affiliateid=$affiliateid&remove=True&fromstats=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEAFFILIATE." $affiliateid ".FROMTHEDATABASE."\" title=\"".DELETEAFFILIATE." $affiliateid ".FROMTHEDATABASE."\" border=\"0\"></a></div><table align=\"center\" cellpadding=\"10\"><tr><td>
<form action=\"referraldiscounts.php\" method=\"post\" name=\"refdiscountform$i\">
<table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\" align=\"center\">
<tr><td colspan=\"2\" class=\"formtitle\">".ADDNEWREFERRALDISCOUNT."</td></tr>
<tr><td width=\"150\" align=\"right\" class=\"formlabel\">".DISCOUNTCODE.":</td><td>$referralcode</td></tr>
<tr><td align=\"right\" class=\"formlabel\">".DISCOUNT.":</td><td class=\"formlabel\"><input type=\"text\" name=\"value\" size=\"7\" value=\"$oldvalue\"><input type=\"radio\" name=\"type\" value=\"%\""; if ($oldtype == "%") echo " checked"; echo ">% <input type=\"radio\" name=\"type\" value=\"$\""; if ($oldtype == "$") echo " checked"; echo ">";
if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
echo "</td></tr>
<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"affiliateid\" value=\"$affiliateid\"><input type=\"hidden\" name=\"discountid\" value=\"$discountid\">";
if (empty($discountid)) echo "<input type=\"submit\" name=\"add\" value=\"".ADD."\">";
else echo "<input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"delete\" value=\"".REMOVEBUTTON."\">";
echo "</td></tr></table></form><br>";

// Close database...
@mysqli_close($db);

echo "</td></tr></table>$footer";
?>