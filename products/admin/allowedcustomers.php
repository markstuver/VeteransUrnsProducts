<?php
// AShop
// Copyright 2013 - AShop Software - http://www.ashopsoftware.com
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

error_reporting(E_ALL ^ E_NOTICE);

include "config.inc.php";
include "checklogin.inc.php";

if ($userid != "1") {
	header("Location: salesreport.php");
	exit;
}

include "template.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";

// Get context help for this page...
$contexthelppage = "whitelist";
include "help.inc.php";

// Open database...
$db = @mysql_connect("$databaseserver", "$databaseuser", "$databasepasswd");
@mysql_select_db("$databasename",$db);

// Handle new and updated black list items...
if ($submitbutton && !empty($whitelistitem)) {
	if (!$whitelistitemid) {
		$sql="INSERT INTO customerwhitelist (whitelistitem) VALUES ('$whitelistitem')";
	} else {
		$sql = "UPDATE customerwhitelist SET whitelistitem='$whitelistitem' WHERE whitelistitemid='$whitelistitemid'";
	}
	$result = @mysql_query($sql,$db);
}

// Delete black list item...
if ($deletebutton) {
	$sql = "DELETE FROM customerwhitelist WHERE whitelistitemid=$whitelistitemid";
	$result = @mysql_query($sql,$db);
}

echo "$header
<div class=\"heading\">White Listed Customers <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><center>
<p><form action=\"bannedcustomers.php\" method=\"post\" name=\"addform\">
      <table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\" bgcolor=\"#E5E5E5\">
	  <tr class=\"formtitle\"><td valign=\"top\" colspan=\"3\">White list a customer:</td></tr>
<tr class=\"formlabel\"><td width=\"300\" valign=\"top\" align=\"right\">IP number:</td><td valign=\"top\"><input type=\"text\" name=\"whitelistitem\" size=\"40\" maxlength=\"100\"></td><td width=\"250\" align=\"left\"><input type=\"submit\" value=\"".ADD."\" name=\"submitbutton\"></td></tr></table></form></p>
";

// Get whitelist items from database...
$sql="SELECT * FROM customerwhitelist ORDER BY whitelistitemid ASC";
$result = @mysql_query("$sql",$db);
if (@mysql_num_rows($result)) {
	echo "<p class=\"formtitle\" align=\"center\">Currently White Listed:</p>";
	for ($i = 0; $i < @mysql_num_rows($result); $i++) {
		$thiswhitelistitem = @mysql_result($result, $i, "whitelistitem");
		$thiswhitelistitemid = @mysql_result($result, $i, "whitelistitemid");
		echo "<p><form action=\"allowedcustomers.php\" method=\"post\"><input type=\"hidden\" name=\"whitelistitemid\" value=\"$thiswhitelistitemid\">
		<table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\" bgcolor=\"#D0D0D0\">
		<tr class=\"formlabel\"><td valign=\"top\"><input type=\"text\" name=\"whitelistitem\" value=\"$thiswhitelistitem\" size=\"40\" maxlength=\"100\"></td><td width=\"70\" align=\"center\"><input type=\"submit\" value=\"Update\" name=\"submitbutton\"></td><td width=\"70\" class=\"formlabel\" align=\"center\"><input type=\"submit\" value=\"Delete\" name=\"deletebutton\">
		</td></tr></table></form></p>";
	}
}

echo "</center>$footer";
?>