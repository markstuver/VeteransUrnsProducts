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
include "language/$adminlang/members.inc.php";

if ($userid != "1") {
	header("Location: index.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Pause/resume a mailing...
if ($pause) @mysqli_query($db, "UPDATE mailing SET paused='1' WHERE type='member'");
else if ($resume) @mysqli_query($db, "UPDATE mailing SET paused=NULL WHERE type='member'");

// Check if a mailing is being sent...
$mailingresult = @mysqli_query($db, "SELECT * FROM mailing WHERE type='member'");
$mailinginprogress = @mysqli_num_rows($mailingresult);
/*
if (!empty($mailinginprogress)) {
	$mailingid = @mysqli_result($mailingresult,0,"mailingid");
	$paused = @mysqli_result($mailingresult,0,"paused");
	$sentresult = @mysqli_query($db, "SELECT * FROM maillog WHERE mailingid='$mailingid'");
	$totalsent = @mysqli_num_rows($sentresult);

	if ($paused) $pauseresumeform = "<p><form action=\"memberadmin.php\" method=\"post\"><input type=\"submit\" name=\"resume\" value=\"".RESUME."\"></form></p>";
	else $pauseresumeform = "<p><form action=\"memberadmin.php\" method=\"post\"><input type=\"submit\" name=\"pause\" value=\"".PAUSE."\"></form></p>";

	echo "$header
<div class=\"heading\">".MAILINGINPROGRESS."</div><center><br><br><br>
<script language=\"JavaScript\" src=\"../includes/prototype.js\" type=\"text/javascript\"></script>
<script language=\"JavaScript\" type=\"text/javascript\">
function reportprogress(ajaxRequest) {
	parameters = ajaxRequest.responseText;
	parametersarray = parameters.split('|');
	sent = parseInt(parametersarray[0]);
	total = parseInt(parametersarray[1]);
	sentmsgs = sent;
	totalmsgs = total;
	if (sent == -1) $('mailingprogress').update('".MESSAGESENT." <a href=\"../previews/'+logfile+'\" target=\"_blank\">".VIEWLOG."</a>');
	$('sentmails').update(sent);
	$('totalmails').update(total);
}

function setlogfile(ajaxRequest) {
	templog = ajaxRequest.responseText;
	if (templog) logfile = templog;
}

function checkprogress() {
	var myAjax = new Ajax.Request(
		'mailstatus.php', 
		{
			method: 'get',
			parameters: 'mailingtype=member&dummy='+ new Date().getTime(), 
			onSuccess: reportprogress
		}
	);
}

function startmailing() {
	var myAjax = new Ajax.Request(
		'mailmember.php', 
		{
			method: 'get',
			parameters: 'mailall=true&dummy='+ new Date().getTime(),
			onSuccess: setlogfile
		}
	);
}
window.setInterval(\"checkprogress()\",3000);
</script>

	<div id=\"mailingprogress\" class=\"confirm\">".SENT.": <span id=\"sentmails\">0</span> ".THEWORDOF.": <span id=\"totalmails\"></span> ".MESSAGES.".$pauseresumeform</div>
<script language=\"JavaScript\" type=\"text/javascript\">
var logfile = '';
var totalmsgs = 0;
var checkmsgs = 0;
checkprogress();
startmailing();
function unstall() {
	if (totalmsgs == checkmsgs && totalmsgs != -1) startmailing();
	else checkmsgs = totalmsgs;
}
window.setInterval(\"unstall()\",15000);
</script>
</td></tr></table></center>$footer
	";
	exit;
}*/

echo "$header
<div class=\"heading\">".SHOPPINGMALLMEMBERS."</div><center>";

if ($mailinginprogress) echo "<p><b>".MAILINGINPROGRESS."</b> <input type=\"button\" value=\"".VIEWSTATUS."\" onclick=\"document.location.href='mailingadmin.php?type=member'\"></p>";

if ($msg == "sent") {
	echo "<br><span class=\"confirm\">".MESSAGESENT;
	if ($log) echo " <a href=\"../previews/$log\" target=\"_blank\">".VIEWLOG."</a>";
	echo "</span><br>";
}
else if ($msg == "activated") echo "<p align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#009000\"><b>".ACCOUNTACTIVATIONCOMPLETE."</b></font></p>";

if (!$mailinginprogress) echo "<form action=\"mailmember.php\" method=\"post\">";

echo "<table width=\"80%\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" bgcolor=\"#D0D0D0\">
      <tr class=\"reporthead\" bgcolor=\"#808080\">";

if (!$mailinginprogress) echo "<td></td>";

echo "<td align=\"left\">".IDSHOP."</td><td align=\"left\">".EMAIL."</td><td width=\"70\" align=\"center\">".ACTION."</td></tr>";

// Get member information from database...
$sql="SELECT * FROM user WHERE shopname IS NOT NULL AND username != 'ashopadmin' AND email IS NOT NULL ORDER BY userid";
$result = @mysqli_query($db, "$sql");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
    $shopname = @mysqli_result($result, $i, "shopname");
    $username = @mysqli_result($result, $i, "username");
    $memberid = @mysqli_result($result, $i, "userid");
    $password = @mysqli_result($result, $i, "password");
    $email = @mysqli_result($result, $i, "email");
	$url = @mysqli_result($result, $i, "url");
	$suspended = @mysqli_result($result, $i, "licensekey");
	echo "<tr class=\"reportline\">";
	if (!$mailinginprogress) echo "<td width=\"20\" align=\"left\"><input type=\"checkbox\" name=\"user$memberid\"></td>";
	echo "<td nowrap align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><a href=\"editmember.php?memberid=$memberid\">$memberid</a>, <a href=\"";
	if (!empty($cpanelapiuser) && !empty($cpanelapipass) && !empty($cpanelapiurl)) echo $url;
	else echo "$ashopurl/index.php?shop=$memberid";
	echo "\" target=\"_blank\">$shopname</a></font></td><td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$email</font></td><td width=\"100\" nowrap align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;<a href=\"editmember.php?memberid=$memberid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $memberid\" title=\"".PROFILEFOR." $memberid\" border=\"0\"></a>&nbsp;<a href=\"";
	if (!empty($cpanelapiuser) && !empty($cpanelapipass) && !empty($cpanelapiurl)) echo $url;
	else echo "editcatalogue.php?memberid=$memberid";
	echo "\"><img src=\"images/icon_catalog.gif\" alt=\"".PRODUCTCATALOGFOR." $memberid\" title=\"".PRODUCTCATALOGFOR." $memberid\" border=\"0\"></a>";
	if (!empty($cpanelapiuser) && !empty($cpanelapipass) && !empty($cpanelapiurl)) {
		if (!$suspended) echo "&nbsp;<a href=\"makeshop.php?suspend=$memberid\"><img src=\"images/icon_suspend.gif\" alt=\"".SUSPEND." $shopname\" title=\"".SUSPEND." $shopname\" border=\"0\"></a>";
		else echo "&nbsp;<a href=\"makeshop.php?unsuspend=$memberid\"><img src=\"images/icon_unsuspend.gif\" alt=\"".UNSUSPEND." $shopname\" title=\"".UNSUSPEND." $shopname\" border=\"0\"></a>";
	} else {
		echo "&nbsp;<a href=\"editmembercat.php?memberid=$memberid\"><img src=\"images/icon_list.gif\" alt=\"".PRODUCTLISTFOR." $memberid\" title=\"".PRODUCTLISTFOR." $memberid\" border=\"0\"></a>";
		if ($password) echo "&nbsp;<a href=\"salesreport.php?memberid=$memberid&generate=true&reporttype=paid\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $memberid\" title=\"".SALESHISTORYFOR." $memberid\" border=\"0\"></a>";
	}
	if (!$password) echo "&nbsp;<a href=\"mailmember.php?user$memberid=on&activate=true\"><img src=\"images/icon_activate.gif\" alt=\"".ACTIVATESHOPPINGMALLACCOUNT." $memberid\" title=\"".ACTIVATESHOPPINGMALLACCOUNT." $memberid\" border=\"0\"></a>";
	echo "&nbsp;<a href=\"editmember.php?memberid=$memberid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEMEMBER." $memberid ".FROMDB."\" title=\"".DELETEMEMBER." $memberid ".FROMDB."\" border=\"0\"></a></font></td></tr>";
}

echo "</table>";

if (!$mailinginprogress) {
	echo "<table align=\"center\" cellpadding=\"10\"><tr><td><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><p><b>".SUBJECT.":</b> <input type=\"text\" name=\"subject\" size=\"40\"></p><p><b>".MESSAGE.":</b><br><textarea name=\"message\" cols=\"60\" rows=\"10\"></textarea><br><span class=\"sm\">[".SUPPORTSCODES.": %shopname%, %firstname%, %lastname%, %email%, %address%,<br>%state%, %zip%, %city%, %country%, %phone%, %password%, %username%]</p><p><input type=\"radio\" name=\"mailformat\" value=\"html\"";
	if ($prefmemmailformat == "html") echo " checked";
	echo "> ".HTMLFORMAT." <input type=\"radio\" name=\"mailformat\" value=\"text\"";
	if ($prefmemmailformat == "text" || !$prefmemmailformat) echo "checked";
	echo "> ".PLAINTEXT."</p><p><input type=\"submit\" class=\"widebutton\" name=\"mail\" value=\"".MAILTOSELECTED."\"> <input type=\"submit\" name=\"mailall\" value=\"".MAILTOALL."\"></p></font></form></td></tr></table>";
}

echo "</center>$footer";
?>