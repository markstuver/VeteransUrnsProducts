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
include "language/$adminlang/customers.inc.php";
include "ashopconstants.inc.php";

// Validate variables...
if (empty($bidderid) || !is_numeric($bidderid)) {
	header("Location: salesadmin.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check if the current user should have access to this profile...
if ($userid != "1") {
	header("Location: index.php");
	exit;
}

if ($remove && $bidderid) {
	if ($yes) {
		@mysqli_query($db, "DELETE FROM pricebidder WHERE bidderid='$bidderid'");
		header("Location: bidderadmin.php");
    }
	elseif ($no) header("Location: bidderadmin.php");
	else {
		$sql="SELECT screenname FROM pricebidder WHERE bidderid='$bidderid'";
		$result = @mysqli_query($db, $sql);
		$screenname = @mysqli_result($result,0,"screenname");
		if (!$screenname) $screenname = "Unknown";
		echo "$header
<div class=\"heading\">".REMOVEBIDDER."</div><center>
        <p>".AREYOUSUREBIDDER." $bidderid, $screenname?</font></p>
		<form action=\"editbidder.php\" method=\"post\">
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"button\" name=\"no\" value=\"".NO."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"bidderid\" value=\"$bidderid\">
		<input type=\"hidden\" name=\"remove\" value=\"True\"></form>
		</center>
        $footer";
		exit;
	}
} 

// Store updated data...
if ($update) {
	$sql="UPDATE pricebidder SET bidcode='$bidcode', screenname='$screenname', numberofbids='$bids' WHERE bidderid='$bidderid'";
	$result = @mysqli_query($db, "$sql");

	header("Location: bidderadmin.php");
	exit;
}

// Get customer information from database...
$sql="SELECT * FROM pricebidder WHERE bidderid='$bidderid'";
$result = @mysqli_query($db, "$sql");
$bidcode = @mysqli_result($result, 0, "bidcode");
$screenname = @mysqli_result($result, 0, "screenname");
$bids = @mysqli_result($result, 0, "numberofbids");

// Close database...
@mysqli_close($db);


// Show customer page in browser...
	if (strpos($header, "title") != 0) {
	    $newheader = substr($header,1,strpos($header, "title")+5);
	    $newheader .= CUSTOMERDATAFOR.": $firstname $lastname - ".substr($header,strpos($header, "title")+6,strlen($header));
    } else {
		$newheader = substr($header,1,strpos($header, "TITLE")+5);
		$newheader .= CUSTOMERDATAFOR.": $firstname $lastname - ".substr($header,strpos($header, "TITLE")+6,strlen($header));
	}

echo "$newheader
<div class=\"heading\">".EDITBIDDER." $bidderid <a href=\"editbidder.php?bidderid=$bidderid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEBIDDER." $bidderid ".FROMDB."\" title=\"".DELETEBIDDER." $bidderid ".FROMDB."\" border=\"0\"></a>";
echo "</div><center>";
echo "
	<form action=\"editbidder.php\" method=\"post\"><input type=\"hidden\" name=\"bidderid\" value=\"$bidderid\">
    <table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".SCREENNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"screenname\" value=\"$screenname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".BIDCODE.":</font></td>
    <td align=\"left\"><input type=text name=\"bidcode\" value=\"$bidcode\" size=40></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".BIDS.":</font></td>
    <td align=\"left\"><input type=text name=\"bids\" value=\"$bids\" size=4></td></tr>
	<tr><td></td><td align=\"right\"><input type=\"submit\" value=\"".UPDATE."\" name=\"update\"></td></tr></table></form>
</table>
</font></center>$footer";
?>