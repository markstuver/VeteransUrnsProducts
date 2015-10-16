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
// Get context help for this page...
		$contexthelppage = "editflags";
		include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Print product flags form...
if (!$update && !$delete && !$add) {
  echo "$header
        <table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td class=\"heading1\">Configuration</td></tr></table>
        <table cellpadding=\"10\" align=\"center\"><tr><td><p align=\"center\"><span class=\"heading\">Product Flags</span> <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\" align=\"middle\"></a></p>
        <form action=\"editflags.php\" method=\"post\" name=\"newflagform\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"productid\" value=\"$productid\">
        <table width=\"480\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\" align=\"center\">
        <tr><td colspan=\"2\" class=\"formtitle\">Create New Flag</td></tr><tr><td class=\"formlabel\">Caption:</td><td class=\"formlabel\"><input type=\"text\" name=\"caption\" size=\"40\"></td>
		<td class=\"formlabel\"><input type=\"submit\" name=\"add\" value=\"Add flag\"></td></tr></table></form>";

    $sql = "SELECT * FROM productflags ORDER BY flagid";
	$result = @mysqli_query($db, $sql);
	if (@mysqli_num_rows($result)) echo "<table width=\"480\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\"><tr><td class=\"formtitle\">Existing Flags:</td></tr></table>";
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$caption = @mysqli_result($result, $i, "name");
		$flagid = @mysqli_result($result, $i, "flagid");
		echo "<table width=\"480\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\" align=\"center\">";
		if ($updated == $flagid) echo "<tr><td class=\"confirm\">Successfully updated!</td></tr>";
		echo "<tr><td width=\"75%\"><form action=\"editflags.php\" method=\"post\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><input type=\"hidden\" name=\"flagid\" value=\"$flagid\"><tr><td width=\"70%\" class=\formlabel\"><input type=\"text\" name=\"caption\" size=\"40\" value=\"$caption\"></td>
		<td class=\"formtitle\"><input type=\"submit\" name=\"update\" value=\"Update\"></td><td class=\"formtitle\"><input type=\"submit\" name=\"delete\" value=\"Remove\"></td></tr></table></form></td></tr></table><br>";
	}

	echo "</td></tr></table>$footer";

// Store data in database...
} else if ($add) {
	$sql = "INSERT INTO productflags (name) VALUES ('$caption')";
	$result = @mysqli_query($db, $sql);
	header ("Location: editflags.php");
} else if ($update) {
	$sql = "UPDATE productflags SET name='$caption' WHERE flagid='$flagid'";
	$result = @mysqli_query($db, $sql);
	header ("Location: editflags.php?updated=$flagid");
} else if ($delete) {
	$sql = "DELETE FROM productflags WHERE flagid='$flagid'";
	$result = @mysqli_query($db, $sql);
	$sql = "DELETE FROM flagvalues WHERE flagid='$flagid'";
	$result = @mysqli_query($db, $sql);
	header ("Location: editflags.php");
}
?>