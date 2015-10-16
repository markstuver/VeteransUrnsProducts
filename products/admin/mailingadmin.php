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
include "ashopconstants.inc.php";

// Validate variables...
if (empty($type)) {
	header("Location: salesreport.php");
	exit;
}
if (!empty($type) && $type != "customer" && $type != "member" && $type != "affiliate" && $type != "wholesale") {
	header("Location: salesreport.php");
	exit;
}

if (!$dmshowcustomers && $userid != "1") {
	header("Location: salesreport.php");
	exit;
}

include "template.inc.php";
// Get language module...
if ($type == "customer") include "language/$adminlang/customers.inc.php";
else if ($type == "member") include "language/$adminlang/members.inc.php";
else if ($type == "affiliate") include "language/$adminlang/affiliates.inc.php";
else if ($type == "wholesale") include "language/$adminlang/customers.inc.php";

// Get context help for this page...
$contexthelppage = "salesadmin";
include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Pause/resume a mailing...
if ($pause) @mysqli_query($db, "UPDATE mailing SET paused='1' WHERE type='$type'");
else if ($resume) @mysqli_query($db, "UPDATE mailing SET paused=NULL WHERE type='$type'");

// Check if a mailing is being sent...
$mailingresult = @mysqli_query($db, "SELECT * FROM mailing WHERE type='$type'");
$mailinginprogress = @mysqli_num_rows($mailingresult);
if (!empty($mailinginprogress)) {
	$mailingid = @mysqli_result($mailingresult,0,"mailingid");
	$paused = @mysqli_result($mailingresult,0,"paused");
	$sentresult = @mysqli_query($db, "SELECT * FROM maillog WHERE mailingid='$mailingid'");
	$totalsent = @mysqli_num_rows($sentresult);

	if ($paused) $pauseresumeform = "<p><form action=\"mailingadmin.php\" method=\"post\"><input type=\"hidden\" name=\"type\" value=\"$type\" /><input type=\"submit\" name=\"resume\" value=\"".RESUME."\"></form></p>";
	else $pauseresumeform = "<p><form action=\"mailingadmin.php\" method=\"post\"><input type=\"hidden\" name=\"type\" value=\"$type\" /><input type=\"submit\" name=\"pause\" value=\"".PAUSE."\"></form></p>";

	// Set the mailing script...
	if ($type == "customer") $mailingscript = "mailcustomer.php";
	else if ($type == "member") $mailingscript = "mailmember.php";
	else if ($type == "affiliate") $mailingscript = "mailaffiliate.php";
	else if ($type == "wholesale") $mailingscript = "mailuser.php";

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
			parameters: 'mailingtype=$type&dummy='+ new Date().getTime(), 
			onSuccess: reportprogress
		}
	);
}

function startmailing() {
	var myAjax = new Ajax.Request(
		'$mailingscript', 
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
} else {
	if ($type == "customer") header("Location: salesadmin.php?msg=sent");
	else if ($type == "member") header("Location: memberadmin.php?msg=sent");
	else if ($type == "affiliate") header("Location: affiliateadmin.php?msg=sent");
	else if ($type == "wholesale") header("Location: wssalesadmin.php?msg=sent");	
}
?>