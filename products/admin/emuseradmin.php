<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

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
$contexthelppage = "emuseradmin";
include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

echo "$header
<div class=\"heading\">".SALESOFFICEUSERS."</div><center><p><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> <a href=\"editemuser.php?new=true\">".ADDNEWUSER."</a> - <a href=\"editemuser.php?new=true&salesrep=true\">".ADDNEWSALESREP."</a></p>
      <table width=\"50%\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" bgcolor=\"#D0D0D0\">
      <tr class=\"reporthead\"><td align=\"left\">".USERNAME."</td><td width=\"50\" align=\"center\">".ACTION."</td></tr>";

// Get user information from database...
$result = @mysqli_query($db, "SELECT * FROM emerchant_user");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$emusername = @mysqli_result($result, $i, "username");
	echo "<tr class=\"reportline\"><td align=\"left\">$emusername</td><td align=\"center\"><a href=\"editemuser.php?emusername=$emusername\"><img src=\"images/icon_profile.gif\" alt=\"".EDITPASSWORD."\" title=\"".EDITPASSWORD."\" border=\"0\"></a>&nbsp;";
	if ($emusername != "admin") echo "<a href=\"editemuser.php?emusername=$emusername&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".REMOVEUSER."\" title=\"".REMOVEUSER."\" border=\"0\"></a>";
	else echo "<img src=\"images/spacer.gif\" width=\"15\" height=\"15\">";
	echo "</td></tr>";
}

echo "</table></center>$footer";
?>