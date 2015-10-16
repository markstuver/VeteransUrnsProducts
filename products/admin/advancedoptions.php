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

if ($cancel) {
	header("Location: settings.php");
	exit;
}
include "template.inc.php";
// Get language module...
include "language/$adminlang/configure.inc.php";
include "ashopconstants.inc.php";

// Open database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Perform requested unlock...
if ($changeconfig && ($unlockmove || $unlockhtml)) {
	$changeconfig = 0;
	if ($unlockmove) @mysqli_query($db, "UPDATE user SET movelock='0'");
	else if ($unlockhtml) @mysqli_query($db, "UPDATE user SET htmllock='0'");
}

// Check if any features are currently locked...
$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1'");
if (@mysqli_num_rows($result)) $productmovecheck = "<b>".LOCKED."</b> <input type=\"submit\" name=\"unlockmove\" value=\"".UNLOCK."\">";
else $productmovecheck = "<b>".UNLOCKED."</b>";
$result = @mysqli_query($db, "SELECT * FROM user WHERE htmllock='1'");
if (@mysqli_num_rows($result)) $pagegeneratorcheck = "<b>".LOCKED."</b> <input type=\"submit\" name=\"unlockhtml\" value=\"".UNLOCK."\">";
else $pagegeneratorcheck = "<b>".UNLOCKED."</b>";

if (!$changeconfig) {
        echo "$header
<div class=\"heading\">
        ".ADVANCEDSHOPPARAMETERS."</div>
        <table align=\"center\" cellpadding=\"10\"><tr><td>
        <form action=\"advancedoptions.php?changeconfig=1\" method=\"post\" name=\"configurationform\" enctype=\"multipart/form-data\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\">";
}

if (!$changeconfig) {
	// Get context help for this page...
		$contexthelppage = "advancedoptions";
		include "help.inc.php";
	echo "<font face=\"Arial, Helvetica, sans-serif\" color=\"#FF0000\" size=\"2\">".ONLYCHANGEIFYOUKNOW."</font><br><br>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".SHOPURL.":</td><td><input type=\"text\" name=\"nashopurl\" size=\"35\" value=\"$ashopurl\"><script language=\"JavaScript\">document.configurationform.nashopurl.focus();</script></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".SECUREURL.":</td><td><input type=\"text\" name=\"nashopsurl\" size=\"35\" value=\"$ashopsurl\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> ".FILESYSTEMPATH.":</td><td><input type=\"text\" name=\"nashoppath\" size=\"35\" value=\"$ashoppath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image11','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image11\" align=\"absmiddle\" onclick=\"return overlib('$tip11');\" onmouseout=\"return nd();\"></a> ".SECUREFILESYSTEMPATH.":</td><td><input type=\"text\" name=\"nashopspath\" size=\"35\" value=\"$ashopspath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a> ".TIMEZONEOFFSET.":</td><td><input type=\"text\" name=\"ntimezoneoffset\" size=\"35\" value=\"$timezoneoffset\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".MAILERTYPE.":</td><td><select name=\"nmailertype\"><option value=\"mailfunction\""; if ($mailertype == "mailfunction") echo " selected"; echo ">".MAILFUNCTION."</option><option value=\"smtp\""; if ($mailertype == "smtp") echo " selected"; echo ">SMTP</option></select></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".MAILERSERVER.":</td><td><input type=\"text\" name=\"nmailerserver\" size=\"35\" value=\"$mailerserver\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".MAILERPORT.":</td><td><input type=\"text\" name=\"nmailerport\" size=\"35\" value=\"$mailerport\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".MAILERUSER.":</td><td><input type=\"text\" name=\"nmaileruser\" size=\"35\" value=\"$maileruser\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".MAILERPASS.":</td><td><input type=\"text\" name=\"nmailerpass\" size=\"35\" value=\"$mailerpass\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12\" align=\"absmiddle\" onclick=\"return overlib('$tip12');\" onmouseout=\"return nd();\"></a> ".PATHTO." <a href=\"$help12\" class=\"helpnav2\" target=\"_blank\">Infinity Responder</a>:</td><td><input type=\"text\" name=\"ninfinitypath\" size=\"35\" value=\"$infinitypath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a> ".PATHTO." <a href=\"$help4\" class=\"helpnav2\" target=\"_blank\">ListMessenger</a>:</td><td><input type=\"text\" name=\"nlistmessengerpath\" size=\"35\" value=\"$listmessengerpath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a> ".URLTO." <a href=\"$help5\" class=\"helpnav2\" target=\"_blank\">ListMail Pro</a>:</td><td><input type=\"text\" name=\"nlistmailurl\" size=\"35\" value=\"$listmailurl\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip6');\" onmouseout=\"return nd();\"></a> ".PATHTO." <a href=\"$help6\" class=\"helpnav2\" target=\"_blank\">punBB</a>:</td><td><input type=\"text\" name=\"nphpbbpath\" size=\"35\" value=\"$phpbbpath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip7');\" onmouseout=\"return nd();\"></a> ".URLTO." punBB:</td><td><input type=\"text\" name=\"nphpbburl\" size=\"35\" value=\"$phpbburl\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a> ".PATHTO." <a href=\"$help9\" class=\"helpnav2\" target=\"_blank\">AutoResponse Plus:</a></td><td><input type=\"text\" name=\"narpluspath\" size=\"35\" value=\"$arpluspath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a> ".PATHTO." <a href=\"$help9\" class=\"helpnav2\" target=\"_blank\">ARP Reach:</a></td><td><input type=\"text\" name=\"narpreachpath\" size=\"35\" value=\"$arpreachpath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image8','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image8\" align=\"absmiddle\" onclick=\"return overlib('$tip8');\" onmouseout=\"return nd();\"></a> ".PATHTO." <a href=\"$help8\" class=\"helpnav2\" target=\"_blank\">PA+</a>:</td><td><input type=\"text\" name=\"npapluspath\" size=\"35\" value=\"$papluspath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image10','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image10\" align=\"absmiddle\" onclick=\"return overlib('$tip10');\" onmouseout=\"return nd();\"></a> ".PATHTO." <a href=\"$help10\" class=\"helpnav2\" target=\"_blank\">Password Robot</a>:</td><td><input type=\"text\" name=\"nprobotpath\" size=\"35\" value=\"$probotpath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image17','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image17\" align=\"absmiddle\" onclick=\"return overlib('$tip17');\" onmouseout=\"return nd();\"></a> <a href=\"http://www.aweber.com\" target=\"_blank\">AWeber</a> <a href=\"https://auth.aweber.com/1.0/oauth/authorize_app/3d71035a\" class=\"helpnav2\" target=\"_blank\">Authorization Code</a>:</td><td><input type=\"text\" name=\"naweberauthcode\" size=\"35\" value=\"$aweberauthcode\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image17','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image17\" align=\"absmiddle\" onclick=\"return overlib('$tip18');\" onmouseout=\"return nd();\"></a> <a href=\"http://www.mailchimp.com\" target=\"_blank\">MailChimp</a> <a href=\"https://us2.admin.mailchimp.com/account/api/\" class=\"helpnav2\" target=\"_blank\">API Key</a>:</td><td><input type=\"text\" name=\"nmailchimpapikey\" size=\"35\" value=\"$mailchimpapikey\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image16','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image16\" align=\"absmiddle\" onclick=\"return overlib('$tip16');\" onmouseout=\"return nd();\"></a> <a href=\"$help16\" class=\"helpnav2\" target=\"_blank\">Email Marketer ".URL."</a>:</td><td><input type=\"text\" name=\"niemurl\" size=\"35\" value=\"$iemurl\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">Email Marketer ".USER.":</td><td><input type=\"text\" name=\"niemuser\" size=\"35\" value=\"$iemuser\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">Email Marketer ".TOKEN.":</td><td><input type=\"text\" name=\"niemtoken\" size=\"35\" value=\"$iemtoken\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">Facebook ".APPLICATIONID.":</td><td><input type=\"text\" name=\"nfacebookappid\" size=\"35\" value=\"$facebookappid\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">Facebook ".SECRET.":</td><td><input type=\"text\" name=\"nfacebooksecret\" size=\"35\" value=\"$facebooksecret\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image15','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image15\" align=\"absmiddle\" onclick=\"return overlib('$tip15');\" onmouseout=\"return nd();\"></a> ".PATHTO." <a href=\"$help15\" class=\"helpnav2\" target=\"_blank\">PAP</a>:</td><td><input type=\"text\" name=\"npappath\" size=\"35\" value=\"$pappath\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image13','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image13\" align=\"absmiddle\" onclick=\"return overlib('$tip13');\" onmouseout=\"return nd();\"></a> ".PRODUCTMOVEIS.":</td><td class=\"formlabel\">$productmovecheck</td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image14','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image14\" align=\"absmiddle\" onclick=\"return overlib('$tip14');\" onmouseout=\"return nd();\"></a> ".SITEMAPGENERATORIS.":</td><td class=\"formlabel\">$pagegeneratorcheck</td></tr>";
} else {

	// Auhtenticate the AWeber authorization code...
	$awebercodecheck = explode("|",$naweberauthcode);
	if (!empty($naweberauthcode) && count($awebercodecheck) == 6) {
		require_once('../includes/aweber/aweber_api.php');

		try {
			$auth = AWeberAPI::getDataFromAweberID($naweberauthcode);
			list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = $auth;
		}
		catch(AWeberAPIException $exc) {
			echo "AWeber Error: ".$exc->message;
			exit;
		}
		$naweberauthcode = $consumerKey."|".$consumerSecret."|".$accessKey."|".$accessSecret."|";
	}

	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopurl' WHERE prefname='ashopurl'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopsurl' WHERE prefname='ashopsurl'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashoppath' WHERE prefname='ashoppath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopspath' WHERE prefname='ashopspath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntimezoneoffset' WHERE prefname='timezoneoffset'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmailertype' WHERE prefname='mailertype'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmailerserver' WHERE prefname='mailerserver'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmailerport' WHERE prefname='mailerport'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmaileruser' WHERE prefname='maileruser'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmailerpass' WHERE prefname='mailerpass'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nlistmessengerpath' WHERE prefname='listmessengerpath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nlistmailurl' WHERE prefname='listmailurl'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nphpbbpath' WHERE prefname='phpbbpath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nphpbburl' WHERE prefname='phpbburl'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$npapluspath' WHERE prefname='papluspath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nprobotpath' WHERE prefname='probotpath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$narpluspath' WHERE prefname='arpluspath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$narpreachpath' WHERE prefname='arpreachpath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ninfinitypath' WHERE prefname='infinitypath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$npappath' WHERE prefname='pappath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$niemurl' WHERE prefname='iemurl'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$niemuser' WHERE prefname='iemuser'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$niemtoken' WHERE prefname='iemtoken'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfacebookappid' WHERE prefname='facebookappid'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfacebooksecret' WHERE prefname='facebooksecret'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$naweberauthcode' WHERE prefname='aweberauthcode'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmailchimpapikey' WHERE prefname='mailchimpapikey'");
	
	// Disable Autoresponder-Service if AWeber is activated...
	if (!empty($naweberauthcode)) @mysqli_query($db, "UPDATE preferences SET prefvalue='' WHERE prefname='activateautoresponder'");
}

if (!$changeconfig) {
	echo "<tr bgcolor=\"#FFFFFF\"><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"cancel\" value=\"\"><input type=\"button\" value=\"".CANCEL."\" onClick=\"document.configurationform.cancel.value='true';document.configurationform.submit();\"> <input type=\"submit\" value=\"".SUBMIT."\"></td></tr></table></form></table>$footer";
} else {
	@mysqli_close($db);
	header("Location: settings.php");
}
?>