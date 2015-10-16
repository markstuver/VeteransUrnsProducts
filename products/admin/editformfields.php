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
include "ashopconstants.inc.php";
// Get context help for this page...
$contexthelppage = "editformfields";
include "help.inc.php"; 

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$result = @mysqli_query($db, "SELECT name FROM payoptions WHERE payoptionid='$payoption'");
$payoptionname = @mysqli_result($result, 0, "name");

echo "$header
<table align=\"center\" cellpadding=\"10\"><tr><td><center><div class=\"heading\">".PAYMENTOPTIONS."</div><p>".EXTRAFORMFIELDSFOR.": <b>$payoptionname</b></p>
        </center>

	<form action=\"editformfields.php\" method=\"post\" name=\"formfieldform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		<tr><td align=\"right\" class=\"formlabel\">".FIELDLABEL.":</td><td><input type=\"text\" name=\"nlabel\" size=\"35\" value=\"$label\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME.":</td><td><input type=\"text\" name=\"nname\" size=\"35\" value=\"$name\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".SIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nsize\" size=\"10\" value=\"35\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".ROWS.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nrows\" size=\"10\" value=\"1\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".REQUIRED.":</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"nrequired\" value=\"1\"></td></tr>
		<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"payoption\" value=\"$payoption\"><input type=\"hidden\" name=\"updateformfield\" value=\"new\"><input type=\"submit\" name=\"add\" value=\"".ADD."\"></td></tr></table></form>";

// Update selected form field...
if ($updateformfield && !$delete) {
	if (empty($nrequired)) $nrequired = 0;
	if ($updateformfield == "new") $sql="INSERT INTO formfields (payoptionid, label, name, size, rows, required) VALUES ('$payoption', '$nlabel', '$nname', '$nsize', '$nrows', '$nrequired')";
	else $sql="UPDATE formfields SET payoptionid='$payoption', label='$nlabel', name='$nname', size='$nsize', rows='$nrows', required='$nrequired' WHERE formfieldid='$updateformfield'";
	$result = @mysqli_query($db, "$sql");
} else if ($updateformfield && $delete) {
	$sql="DELETE FROM formfields WHERE formfieldid='$updateformfield'";
	$result = @mysqli_query($db, "$sql");
}

// Display current payment options...
$sql="SELECT * FROM formfields WHERE payoptionid='$payoption' ORDER BY formfieldid DESC";
$result = @mysqli_query($db, "$sql");
$gw = "";
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$formfieldid = @mysqli_result($result, $i, "formfieldid");
	$label = @mysqli_result($result, $i, "label");
	$name = @mysqli_result($result, $i, "name");
	$size = @mysqli_result($result, $i, "size");
	$rows = @mysqli_result($result, $i, "rows");
	$required = @mysqli_result($result, $i, "required");

	echo "<form action=\"editformfields.php\" method=\"post\" name=\"formfieldform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		<tr><td align=\"right\" class=\"formlabel\">".FIELDLABEL.":</td><td><input type=\"text\" name=\"nlabel\" size=\"35\" value=\"$label\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME.":</td><td><input type=\"text\" name=\"nname\" size=\"35\" value=\"$name\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".SIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nsize\" size=\"10\" value=\"$size\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".ROWS.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nrows\" size=\"10\" value=\"$rows\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".REQUIRED.":</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"nrequired\" value=\"1\""; if ($required) echo " checked"; echo "></td></tr>
		<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"payoption\" value=\"$payoption\"><input type=\"hidden\" name=\"updateformfield\" value=\"$formfieldid\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\"></td></tr></table></form>";
}

// Close database...
@mysqli_close($db);

echo "</table>$footer";
?>