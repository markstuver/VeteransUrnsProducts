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
// --------------------------------------------------------------------

// Include configuration file and functions...
if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists(ashop_mailsafe)) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Check if this is a new visitor...
$ip = $_SERVER["REMOTE_ADDR"];
$shownresult = @mysqli_query($db, "SELECT * FROM maintenanceviews WHERE ipnumber='$ip'");
if (@mysqli_num_rows($shownresult)) $showmessage = FALSE;
else $showmessage = TRUE;

// Check if maintenance message is enabled...
if ($showmessage && !empty($maintenancemessage)) {
	@mysqli_query($db, "INSERT INTO maintenanceviews (ipnumber) VALUES ('$ip')");

	// Show maintenance message...
	echo "
		<style type=\"text/css\">
		<!--
		.darkenBackground {
			background-color: rgb(0, 0, 0);
			opacity: 0.7; /* Safari, Opera */
			-moz-opacity:0.70; /* FireFox */
			filter: alpha(opacity=70); /* IE */
			z-index: 20;
			height: 100%;
			width: 100%;
			background-repeat:repeat;
			position:fixed;
			top: 0px;
			left: 0px;
		}
		-->
		</style>
		<div id=\"darkBackgroundLayer\" class=\"darkenBackground\"></div>
		<script language=\"javascript\" type=\"text/javascript\">
		document.getElementById(\"darkBackgroundLayer\").style.display = \"none\";
		</script>
		<script type=\"text/javascript\">var timer; var h = -400; var w = 400; var t = 100;</script><script type=\"text/javascript\" src=\"includes/dhtmlpopup.js\"></script>
		<div id=\"pa\" style=\"background:$bgcolor;text-align:center;padding:0px;width:400px;border:2px solid #666;position:absolute;top:-400px;z-index:10000\">
		<div style=\"float:right;position:absolute;top:3px;right:3px\"><a href=\"javascript:void(0)\" onclick=\"hideAp(); document.getElementById('darkBackgroundLayer').style.display = 'none';\" style=\"border:none\"><img src=\"images/close.gif\" border=\"0\" alt=\"Close\" /></a></div>
		<table class=\"ashopboxtable\" cellspacing=\"0\"><tr>
		<tr><td class=\"ashopboxcontent\"><br>$maintenancemessage
		</td></tr></table>
		</div><script type=\"text/javascript\">startAp(); document.getElementById(\"darkBackgroundLayer\").style.display = \"\";</script>";
}
?>