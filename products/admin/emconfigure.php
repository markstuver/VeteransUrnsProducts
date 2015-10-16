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
include "checklogin.inc.php";

if ($cancel) {
	header("Location: settings.php");
	exit;
}

if ($userid != "1") {
	header("Location: index.php");
	exit;
}
include "template.inc.php";
// Get language module...
include "language/$adminlang/salesoffice.inc.php";
include "ashopconstants.inc.php";
// Get context help for this page...
$contexthelppage = "emconfigure";
include "help.inc.php"; 

// Open database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get current configuration...
$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
while ($row = @mysqli_fetch_array($result)) eval("\${$row["confname"]} = \"{$row["confvalue"]}\";");

// Get list of accepted domains...
$domainlist = "";
$result = @mysqli_query($db, "SELECT * FROM emerchant_domains");
while ($row = @mysqli_fetch_array($result)) $domainlist .= $row["domain"]."\n";

if (!$changeconfig) {
        echo "$header
		<script language=\"JavaScript\">
		<!--
			function switchport(configurationform) {
				if(configurationform.nmailservertype.value == 'pop3') {
					configurationform.npopport.value = '110';
					configurationform.npopport2.value = '110';
				} else {
					configurationform.npopport.value = '143';
					configurationform.npopport2.value = '143';
				}
			}
		-->
		</script>
<div class=\"heading\">".SALESOFFICE." ".CONFIGURATION."</div>
        <table align=\"center\" cellpadding=\"10\"><tr><td>
        <form action=\"emconfigure.php?changeconfig=1\" method=\"post\" name=\"configurationform\">
<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\">
		<tr><td colspan=\"2\" class=\"formtitle\">".SPAMPROTECTION." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a></td></tr>
<tr><td align=\"right\">&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"nspamprotection\" value=\"1\""; if ($spamprotection) echo " checked"; echo "> ".BLOCKEMAIL."</td></tr>
<tr><td align=\"right\" class=\"formlabel\">".ACCEPTEDDOMAINS.":</td><td><textarea name=\"ndomainlist\" cols=\"25\" rows=\"3\">$domainlist</textarea></td></tr><tr><td colspan=\"2\"><hr></td></tr>";
	    if (extension_loaded("imap")) { 
			echo "<tr><td colspan=\"2\" class=\"formtitle\">".MAILSETTINGS." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a></td></tr><tr><td align=\"right\" class=\"formlabel\">".SERVERTYPE.":</td><td><select name=\"nmailservertype\" onChange=\"switchport(configurationform)\"><option value=\"pop3\""; if ($mailservertype == "pop3") echo " selected"; echo ">".POP3."</option><option value=\"imap\""; if ($mailservertype == "imap") echo " selected"; echo ">".IMAP."</option></select></td></tr>";
		} else echo "<input type=\"hidden\" name=\"nmailservertype\" value=\"pop3\">";
		echo "<tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"nmailcheckonlogin\" value=\"1\""; if ($mailcheckonlogin) echo " checked"; echo "> ".FETCHMAILONLOGIN."</td></tr><tr><td colspan=\"2\"><hr></td></tr><tr><td colspan=\"2\" class=\"formtitle\">".CUSTOMERMAILACCOUNT." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></td></tr>
<tr><td align=\"right\" class=\"formlabel\">".EMAILADDRESS.":</td><td><input type=\"text\" name=\"ncustomeremail\" size=\"35\" value=\"$customeremail\"><script language=\"JavaScript\">document.configurationform.ncustomeremail.focus();</script></td></tr>
<tr><td align=\"right\" class=\"formlabel\">".MAILSERVER.":</td><td><input type=\"text\" name=\"npophost\" size=\"35\" value=\"$pophost\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".ACCOUNTUSERNAME.": </td><td><input type=\"text\" name=\"npopuser\" size=\"35\" value=\"$popuser\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".PASSWORD.": </td><td><input type=\"text\" name=\"npoppass\" size=\"35\" value=\"$poppass\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".PORT.": </td><td><input type=\"text\" name=\"npopport\" size=\"35\" value=\"$popport\"></td></tr><tr><td colspan=\"2\"><hr></td></tr>
		<tr><td colspan=\"2\" class=\"formtitle\">".VENDORMAILACCOUNT." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a></td></tr>
<tr><td align=\"right\" class=\"formlabel\">".EMAILADDRESS.":</td><td><input type=\"text\" name=\"nvendoremail\" size=\"35\" value=\"$vendoremail\"></td></tr>
<tr><td align=\"right\" class=\"formlabel\">".MAILSERVER.":</td><td><input type=\"text\" name=\"npophost2\" size=\"35\" value=\"$pophost2\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".ACCOUNTUSERNAME.": </td><td><input type=\"text\" name=\"npopuser2\" size=\"35\" value=\"$popuser2\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".PASSWORD.": </td><td><input type=\"text\" name=\"npoppass2\" size=\"35\" value=\"$poppass2\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".PORT.": </td><td><input type=\"text\" name=\"npopport2\" size=\"35\" value=\"$popport2\"></td></tr><tr><td colspan=\"2\"><hr></td></tr>
		<tr><td colspan=\"2\" class=\"formtitle\">".AUTOMATICBILLING."</td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".DEFAULTDUEDATETO.":</td><td class=\"formlabel\"><input type=\"text\" name=\"ndefaultduedays\" size=\"5\" value=\"$defaultduedays\"> ".DAYSAFTERBILLDATE."</td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".DEFAULTREMIND.":</td><td class=\"formlabel\"><input type=\"text\" name=\"ndefaultreminderdays\" size=\"5\" value=\"$defaultreminderdays\"> ".DAYSBEFOREDUEDATE."</td></tr>
<tr><td align=\"right\" class=\"formlabel\">".DEFAULTPASTDUE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"ndefaultpastduedays\" size=\"5\" value=\"$defaultpastduedays\"> ".DAYSAFTERDUEDATE."</td></tr>
<tr><td align=\"right\" class=\"formlabel\">".DEFAULTRECURRING.":</td><td class=\"formlabel\"><input type=\"text\" name=\"ndefaultsendbilldays\" size=\"5\" value=\"$defaultsendbilldays\"> ".DAYSBEFOREDUEDATE."</td></tr>
<tr bgcolor=\"#F0F0F0\"><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"cancel\" value=\"\"><input type=\"button\" value=\"".CANCEL."\"  onClick=\"javascript:history.back();\"> <input type=\"submit\" value=\"".SUBMIT."\"></td></tr></table></form></table>$footer";
} else {
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$nmailcheckonlogin' WHERE confname='mailcheckonlogin'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$nmailservertype' WHERE confname='mailservertype'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$npophost' WHERE confname='pophost'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$npopuser' WHERE confname='popuser'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$npoppass' WHERE confname='poppass'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$npopport' WHERE confname='popport'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$npophost2' WHERE confname='pophost2'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$npopuser2' WHERE confname='popuser2'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$npoppass2' WHERE confname='poppass2'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$npopport2' WHERE confname='popport2'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$ncustomeremail' WHERE confname='customeremail'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$nvendoremail' WHERE confname='vendoremail'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$nspamprotection' WHERE confname='spamprotection'");
	$domains = explode("\n",$ndomainlist);
	if (is_array($domains)) {
		@mysqli_query($db, "DELETE FROM emerchant_domains");
		foreach ($domains as $thisdomain) {
			$thisdomain = trim($thisdomain);
			if ($thisdomain) @mysqli_query($db, "INSERT INTO emerchant_domains (domain) VALUES ('$thisdomain')");
		}
	}
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$ndefaultreminderdays' WHERE confname='defaultreminderdays'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$ndefaultpastduedays' WHERE confname='defaultpastduedays'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$ndefaultsendbilldays' WHERE confname='defaultsendbilldays'");
	@mysqli_query($db, "UPDATE emerchant_configuration SET confvalue='$ndefaultduedays' WHERE confname='defaultduedays'");
	@mysqli_close($db);
	if ($update) header("Location: emconfigure.php?param=payment");
	else header("Location: emerchant.php");
}
?>