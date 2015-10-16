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
include "language/$adminlang/configure.inc.php";
// Get context help for this page...
$contexthelppage = "shipping";
include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Print local tax rates form...
if (!$update && !$delete && !$add) {
  echo "$header
        <div class=\"heading\">".LOCALTAXRATES."</span> <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <form action=\"editlocaltax.php\" method=\"post\" name=\"newtaxrateform\">
        <table width=\"480\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\" align=\"center\">
        <tr><td colspan=\"2\" class=\"formtitle\">".ADDNEWLOCALRATE."</td></tr><tr><td class=\"formlabel\">".CITY2.": <input type=\"text\" name=\"city\" size=\"25\">&nbsp;&nbsp;&nbsp;".ADDITIONALRATE.": <input type=\"text\" name=\"rate\" size=\"3\">%</td>
		<td class=\"formlabel\"><input type=\"submit\" name=\"add\" value=\"".ADD."\"></td></tr></table></form>";

    $sql = "SELECT * FROM localtax ORDER BY city";
	$result = @mysqli_query($db, $sql);
	if (@mysqli_num_rows($result)) echo "<table width=\"480\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\"><tr><td class=\"formtitle\">".EXISTINGLOCALRATES.":</td></tr></table>";
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$city = @mysqli_result($result, $i, "city");
		$rate = @mysqli_result($result, $i, "rate");
		echo "<table width=\"480\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\" align=\"center\">";
		if ($updated == $city) echo "<tr><td class=\"confirm\">".SUCCESSFULLYUPDATED."</td></tr>";
		echo "<tr><td width=\"75%\"><form action=\"editlocaltax.php\" method=\"post\"><input type=\"hidden\" name=\"city\" value=\"$city\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><tr><td class=\"formlabel\">".CITY2.": <b>$city</b>, ".ADDITIONALRATE.": <input type=\"text\" name=\"rate\" size=\"3\" value=\"$rate\">%</td>
		<td class=\"formtitle\" width=\"60\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"></td><td class=\"formtitle\" width=\"60\"><input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\"></td></tr></table></form></td></tr></table><br>";
	}

	echo "</td></tr></table>$footer";

// Store data in database...
} else if ($add) {
	$sql = "INSERT INTO localtax (city,rate) VALUES ('$city','$rate')";
	$result = @mysqli_query($db, $sql);
	header ("Location: editlocaltax.php");
} else if ($update) {
	$sql = "UPDATE localtax SET rate='$rate' WHERE city='$city'";
	$result = @mysqli_query($db, $sql);
	header ("Location: editlocaltax.php?updated=$city");
} else if ($delete) {
	$sql = "DELETE FROM localtax WHERE city='$city'";
	$result = @mysqli_query($db, $sql);
	header ("Location: editlocaltax.php");
}
?>