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
// Get context help for this page...
		$contexthelppage = "editdiscount";
		include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get the discount ID or add email address...
if (!$discountid) {
	$result = @mysqli_query($db, "SELECT discountid FROM discount WHERE productid='$productid'");
	$discountid = @mysqli_result($result,0,"discountid");
} else if ($email && $action == "remove") @mysqli_query($db, "DELETE FROM onetimediscounts WHERE email='$email' AND discountid='$discountid'");
else if ($email) {
	$result = @mysqli_query($db, "SELECT * FROM onetimediscounts WHERE email='$email' AND discountid='$discountid'");
	if (!@mysqli_num_rows($result)) @mysqli_query($db, "INSERT INTO onetimediscounts (discountid, email) VALUES ('$discountid', '$email')");
} else if (is_uploaded_file($emaillist)) {
	unset($emaillistarray);
	move_uploaded_file($emaillist, "$ashopspath/products/emaillist$productid");
	$fp = fopen ("$ashopspath/products/emaillist$productid","r");
	while (!feof ($fp)) $emaillistcontents .= fgets($fp, 4096);
	fclose($fp);
	unlink ("$ashopspath/products/emaillist$productid");
	$emaillistcontents = str_replace("\r", "\n", $emaillistcontents);
	$emaillistcontents = str_replace("\n\n", "\n", $emaillistcontents);
	$emails = explode("\n", $emaillistcontents);
	if ($emails) foreach($emails as $emailnumber=>$email) {
		if ($email) {
			$result = @mysqli_query($db, "SELECT * FROM onetimediscounts WHERE email='$email' AND discountid='$discountid'");
			if (!@mysqli_num_rows($result)) @mysqli_query($db, "INSERT INTO onetimediscounts (discountid, email) VALUES ('$discountid', '$email')");
		}
	}
}

echo "$header
        <table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td  class=\"heading1\">Edit Catalogue</td></tr></table>
        <div class=\"heading\">Manage Discount Codes <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table cellpadding=\"10\" align=\"center\" width=\"500\"><td align=\"center\" class=\"heading\"><tr><td><form action=\"editonetimediscounts.php\" method=\"post\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		      <tr><td colspan=\"3\" class=\"formtitle\">Add an email address for this one time discount code:</td></tr>
		      <tr><td align=\"right\"class=\"formlabel\">Email:</td><td><input type=\"text\" name=\"email\" size=\"40\"><input type=\"hidden\" name=\"discountid\" value=\"$discountid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></td><td><input type=\"submit\" value=\"Add\"></td></tr></tr></table></form><form action=\"editonetimediscounts.php\" method=\"post\" enctype=\"multipart/form-data\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		      <tr><td colspan=\"3\" class=\"formtitle\">Import email list:</td></tr>
		      <tr><td>&nbsp;</td><td><input type=\"file\" name=\"emaillist\" size=\"40\"><input type=\"hidden\" name=\"discountid\" value=\"$discountid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></td><td><input type=\"submit\" value=\"Upload\"></td></tr></tr></table></form></td></tr></table>";

// Get current list of email addresses for this discount from database...
$result = @mysqli_query($db, "SELECT * FROM onetimediscounts WHERE discountid='$discountid'");
if (@mysqli_num_rows($result)) {
	echo "<p align=\"center\" class=\"formtitle\">Existing email addresses:</p><table width=\"50%\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" bgcolor=\"#D0D0D0\"><tr class=\"reporthead\"><td>Used</td><td>Email</td><td width=\"20\">&nbsp;</td></tr>";
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$email = @mysqli_result($result, $i, "email");
		$used = @mysqli_result($result, $i, "used");
		if ($used == "1") $used = "Yes";
		else $used = "No";
		echo "<tr class=\"reportline\"><td width=\"50\">$used</td><td>$email</td><td><a href=\"editonetimediscounts.php?discountid=$discountid&cat=$cat&resultpage=$resultpage&email=$email&action=remove\"><img src=\"images/icon_trash.gif\" border=\"0\"></a></tr>";
	}
	echo "</table>";
}

echo "</center>$footer";
?>