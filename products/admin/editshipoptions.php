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
$contexthelppage = "editshipoptions";
include "help.inc.php";

echo "$header
<div class=\"heading\">".CUSTOMSHIPPINGOPTIONS." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table align=\"center\" cellpadding=\"10\"><tr><td>

	<form action=\"editshipoptions.php\" method=\"post\" name=\"shipoptionform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#D0D0D0\">
		<tr><td colspan=\"2\"  class=\"formtitle\">".ADDNEWSHIPPINGOPTION."</td></tr><tr><td align=\"right\" class=\"formlabel\">".DESCRIPTION.": </td><td class=\"formlabel\"><input type=\"text\" name=\"ndescr\" size=\"30\"></td></tr><tr><td align=\"right\" class=\"formlabel\">".FEE.":</td><td class=\"formlabel\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"nfee\" size=\"15\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr><tr><td align=\"right\" class=\"formlabel\">".SHIPPED.":</td><td class=\"formlabel\"><select name=\"nshipped\"><option value=\"international\">".INTERNATIONALLY."<option value=\"local\">".LOCALLY."<option value=\"both\">".BOTH."</select></td></tr><tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"ndisableshipping\"> ".DISABLEOTHERSHIPPING."</td></tr><tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"updateshipoption\" value=\"new\"><input type=\"submit\" name=\"add\" value=\"".ADD."\"></td></tr></table></form><br>";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Update selected shipping option...
if ($updateshipoption && !$delete && !$moveup) {
	if ($ndisableshipping == "on") $ndisableshipping = 1;
	if (!$nfee) $nfee = 0.00;
	if ($updateshipoption == "new") $sql="INSERT INTO shipoptions (description, fee, shipped, disableshipping) VALUES ('$ndescr', '$nfee', '$nshipped', '$ndisableshipping')";
	else $sql="UPDATE shipoptions SET description='$ndescr', fee='$nfee', shipped='$nshipped', disableshipping='$ndisableshipping' WHERE shipoptionid='$updateshipoption'";
	$result = @mysqli_query($db, "$sql");
} else if ($updateshipoption && $delete && !$moveup) {
	$sql="DELETE FROM shipoptions WHERE shipoptionid=$updateshipoption";
	$result = @mysqli_query($db, "$sql");
} else if ($updateshipoption && $moveup) {
	$result=@mysqli_query($db, "SELECT * FROM shipoptions WHERE shipoptionid='$updateshipoption'");
	$thisrow=@mysqli_fetch_array($result);
	$result=@mysqli_query($db, "SELECT MIN(shipoptionid) AS previousid FROM shipoptions WHERE shipoptionid>'$updateshipoption'");
	$row=@mysqli_fetch_array($result);
	$result=@mysqli_query($db, "SELECT * FROM shipoptions WHERE shipoptionid='".$row["previousid"]."'");
	$prevrow=@mysqli_fetch_array($result);
	$result=@mysqli_query($db, "UPDATE shipoptions SET description='".$prevrow["description"]."', fee='".$prevrow["fee"]."', shipped='".$prevrow["shipped"]."', disableshipping='".$prevrow["disableshipping"]."' WHERE shipoptionid='".$thisrow["shipoptionid"]."'");
	$result=@mysqli_query($db, "UPDATE shipoptions SET description='".$thisrow["description"]."', fee='".$thisrow["fee"]."', shipped='".$thisrow["shipped"]."', disableshipping='".$thisrow["disableshipping"]."' WHERE shipoptionid='".$prevrow["shipoptionid"]."'");
}


// Display current payment options...
$sql="SELECT * FROM shipoptions ORDER BY shipoptionid DESC";
$result = @mysqli_query($db, "$sql");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$shipoptionid = @mysqli_result($result, $i, "shipoptionid");
	$shipoptiondescr = @mysqli_result($result, $i, "description");
	$shipoptionfee = @mysqli_result($result, $i, "fee");
	$shipoptionshipped = @mysqli_result($result, $i, "shipped");
	$shipoptiondisableshipping = @mysqli_result($result, $i, "disableshipping");

	echo "<form action=\"editshipoptions.php\" method=\"post\" name=\"shipoptionform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#D0D0D0\">
		<tr><td align=\"right\" class=\"formlabel\">".DESCRIPTION.":</td><td><input type=\"text\" name=\"ndescr\" size=\"35\" value=\"$shipoptiondescr\"></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".FEE.":</td><td class=\"formlabel\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"nfee\" size=\"15\" value=\"$shipoptionfee\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr><tr><td align=\"right\" class=\"formlabel\">".SHIPPED.":</td><td class=\"formlabel\"><select name=\"nshipped\"><option value=\"international\""; if($shipoptionshipped == "international") echo " selected"; echo ">".INTERNATIONALLY."<option value=\"local\""; if($shipoptionshipped == "local") echo " selected"; echo ">".LOCALLY."<option value=\"both\""; if($shipoptionshipped == "both") echo " selected"; echo ">".BOTH."</select></td></tr><tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"ndisableshipping\""; if ($shipoptiondisableshipping == 1) echo " checked"; echo "> ".DISABLEOTHERSHIPPING."</td></tr><tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"updateshipoption\" value=\"$shipoptionid\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\">";
	if($i) echo " <input type=\"submit\" name=\"moveup\" value=\"".MOVEUP."\">";
	echo "</td></tr></table></form><br>";
}

// Close database...
@mysqli_close($db);

echo "</table>$footer";
?>