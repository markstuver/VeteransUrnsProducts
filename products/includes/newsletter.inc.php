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
// Module: topform.inc.php
// Description: generates a form with search box, subtotal box and buttons to view cart or check out
// Input variables:
// layout = 1 : show just the form
// layout = 2 : show the form as a dhtml popup
// captcha = off : disable captcha image

// Validate $layout...
unset($_GET["layout"]);
unset($_POST["layout"]);
if (isset($layout) && !is_numeric($layout)) unset($layout);
if (isset($layout) && ($layout > 2 || $layout < 1)) unset($layout);
if (!isset($layout)) $layout = 2;

// Include configuration file and functions...
if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists(ashop_mailsafe)) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
if (file_exists("language/$lang/newsletter.inc.php")) include "language/$lang/newsletter.inc.php";

// Check if this is a new visitor...
$ip = $_SERVER["REMOTE_ADDR"];
$visitonlineresult = @mysqli_query($db, "SELECT * FROM visitcounter_online WHERE ip='$ip'");
$visittodayresult = @mysqli_query($db, "SELECT * FROM visitcounter_today WHERE ip='$ip'");
if ((@mysqli_num_rows($visitonlineresult) || @mysqli_num_rows($visittodayresult)) && empty($nl_fullname) && empty($nl_email)) $shownewsletter = FALSE;
else $shownewsletter = TRUE;
if ($layout != "2") $shownewsletter = TRUE;

// Check if newsletter is enabled...
if ($shownewsletter == TRUE && !empty($autoresponderid) && is_numeric($autoresponderid) && $activateautoresponder == "1" && !empty($newsresponderid) && is_numeric($newsresponderid)) {

	if (empty($captcha) || $captcha != "off") {

		// Check for GD...
		$checkgd = TRUE;
		include "includes/captcha.inc.php";
		
		// If GD is available generate random code for security check...
		if ($gdversion == 2) {
			$activatesecuritycheck = TRUE;
			// Generate new random code...
			mt_srand ((double)microtime()*1000000);
			$maxrandom = 1000000;
			$random = mt_rand(0, $maxrandom);
		} else $activatesecuritycheck = FALSE;
	} else $activatesecuritycheck = FALSE;
	
	// Initialize variables...
	if (!isset($nl_fullname)) $nl_fullname = "";
	if (!isset($nl_email)) $nl_email = "";
	if (!isset($nl_securitycheck)) $nl_securitycheck = "";
	if (!isset($nl_random)) $nl_random = "";

	// Show newsletter subscription form...
	if (empty($nl_fullname) || empty($nl_email) || empty($nl_securitycheck) || empty($nl_random) || ($activatesecuritycheck && ($nl_securitycheck != generatecode($nl_random)))) {
		if ($layout == "2") echo "
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
		<div style=\"float:right;position:absolute;top:3px;right:3px\"><a href=\"javascript:void(0)\" onclick=\"hideAp(); document.getElementById('darkBackgroundLayer').style.display = 'none';\" style=\"border:none\"><img src=\"{$buttonpath}images/close.gif\" border=\"0\" alt=\"".NLCLOSE."\" /></a></div>
		<table class=\"ashopboxtable\" cellspacing=\"0\"><tr><td class=\"ashopboxheader\">&nbsp;&nbsp;&nbsp;".NEWSLETTERSIGNUP."</td></tr>
		<tr><td class=\"ashopboxcontent\"><br>";
		if ($activatesecuritycheck && !empty($nl_securitycheck) && ($nl_securitycheck != generatecode($nl_random))) echo "<span class=\"ashopalert\">".NLCODEDIDNOTMATCH."</span><br><br>";
		echo "<span class=\"ashopboxcontent\">".NLSIGNUP."</span><br><br>
		<form action=\"$subscribe\" method=\"post\" style=\"margin-bottom: 0px;\"><table cellpadding=\"0\" cellspacing=\"2\" border=\"0\" align=\"center\"><tr><td align=\"left\" valign=\"middle\" class=\"ashopboxcontent\">".NLNAME.":</td><td align=\"left\" valign=\"bottom\" class=\"ashopboxcontent\"><input class=\"ashopnewsletterfield\" type=\"text\" size=\"20\" name=\"nl_fullname\" value=\"$nl_fullname\"></td></tr>
		<tr><td align=\"left\" valign=\"middle\" class=\"ashopboxcontent\">".NLEMAIL.":</td><td align=\"left\" valign=\"bottom\" class=\"ashopboxcontent\"><input class=\"ashopnewsletterfield\" type=\"text\" size=\"20\" name=\"nl_email\" value=\"$nl_email\"></td></tr>";
		if ($activatesecuritycheck) {
			echo "<tr><td align=\"left\" valign=\"middle\" class=\"ashopboxcontent\">".NLCODE.":</td><td align=\"left\" valign=\"bottom\" class=\"ashopboxcontent\"><input class=\"ashopcodefield\" type=\"text\" size=\"10\" name=\"nl_securitycheck\"><img src='includes/captcha.inc.php?action=generatecode&random=$random' border='1' alt='".NLSECURITYCODE."' title='".NLSECURITYCODE."' style=\"vertical-align: bottom;";
			// Check if IE is used...
			$ieisused = 1;
			if (!(stristr($HTTP_USER_AGENT, "MSIE"))) $ieisused = 0;
			if ($ieisused) echo " margin-bottom:1px;";
			echo "\"></td></tr>
			";
		}
		echo "<tr><td>&nbsp;</td><td align=\"right\" class=\"ashopboxcontent\"><input type=\"image\" src=\"{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" border=\"0\"></td></tr></table><input type=\"hidden\" name=\"exp\" value=\"$exp\"><input type=\"hidden\" name=\"shop\" value=\"$shop\"><input type=\"hidden\" name=\"nl_random\" value=\"$random\"></form>";
		if ($layout == "2") echo "<br>".NLNOSPAM."<br><br></td></tr></table>
		</div><script type=\"text/javascript\">startAp(); document.getElementById(\"darkBackgroundLayer\").style.display = \"\";</script>";
	}

	// Register subscription...
	else {
		$responderresult = @mysqli_query($db, "SELECT profileid FROM autoresponders WHERE responderid='$newsresponderid'");
		$autoresponderprofileid = @mysqli_result($responderresult, 0, "profileid");
		$querystring = "v=$autoresponderid&w=$autoresponderprofileid&subscription_type=E&id=$newsresponderid&full_name=$nl_fullname&email=$nl_email&posted=true";
		$postheader = "POST /formcapture.php HTTP/1.0\r\nHost: autoresponder-service.com\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
		$fp = @fsockopen ("autoresponder-service.com", 80, $errno, $errstr, 10);
		$res = "";
		if ($fp) {
			@fputs ($fp, $postheader.$querystring);
			//while (!feof($fp)) $res .= fgets ($fp, 1024);
			@fclose ($fp);
		}
		if ($layout == "2") echo "
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
		<div style=\"float:right;position:absolute;top:3px;right:3px\"><a href=\"javascript:void(0)\" onclick=\"hideAp(); document.getElementById('darkBackgroundLayer').style.display = 'none';\" style=\"border:none\"><img src=\"{$buttonpath}images/close.gif\" border=\"0\" alt=\"".NLCLOSE." /></a></div>
		<table class=\"ashopboxtable\" cellspacing=\"0\"><tr><td class=\"ashopboxheader\">&nbsp;&nbsp;&nbsp;".NEWSLETTERSIGNUP."</td></tr>
		<tr><td class=\"ashopboxcontent\"><br>";
		echo "<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\"><tr><td class=\"ashopboxcontent\">".NLTHANKYOU."</td></tr></table>";
		if ($layout == "2") echo "<br><br></td></tr></table>
		</div><script type=\"text/javascript\">startAp(); document.getElementById(\"darkBackgroundLayer\").style.display = \"\";</script>";
	}
}

$layout = "";
?>