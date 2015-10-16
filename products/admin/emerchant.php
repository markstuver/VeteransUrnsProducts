<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

error_reporting(E_ALL ^ E_NOTICE);

include "checklicense.inc.php";
if ($noinactivitycheck == "false") {
	if ($msg) $noinactivitycheck = "true";
	else $noinactivitycheck = "false";
}
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/salesoffice.inc.php";
// Get context help for this page...
$contexthelppage = "affiliateadmin";
include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$result = @mysqli_query($db, "SELECT password FROM emerchant_user WHERE username='admin'");
$adminpassword = @mysqli_result($result, 0, "password");

echo "$header
<div class=\"heading\">".SALESOFFICEANNOUNCEMENT."</div><center>";
if ($message) {
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$message' WHERE confname='announcement'");
	echo "<p align=\"center\" class=\"confirm\"><b>".ANNOUNCEMENTPUBLISHED."</p>";
}
echo "<form action=\"emerchant.php\" method=\"post\"><table align=\"center\" cellpadding=\"10\"><tr><td><tr><td class=\"formtitle\">".PUBLISHANNOUNCEMENT.":<br><textarea name=\"message\" cols=\"40\" rows=\"5\"></textarea></p><p align=\"right\"><input type=\"submit\" name=\"announce\" value=\"".PUBLISH."\"></p></td></tr></table></form></center>$footer";
?>