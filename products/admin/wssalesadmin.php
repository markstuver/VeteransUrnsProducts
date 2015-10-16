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

error_reporting(E_ALL ^ E_NOTICE);

include "config.inc.php";
include "ashopfunc.inc.php";
if ($noinactivitycheck == "false") {
	if ($msg) $noinactivitycheck = "true";
	else $noinactivitycheck = "false";
}

include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";

// Validate variables...
if (!is_numeric($resultpage)) unset($resultpage);
if (!is_numeric($admindisplayitems)) unset($admindisplayitems);
else {
	$c_admindisplayitems = $admindisplayitems;
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("c_admindisplayitems","$admindisplayitems");
}
if (!is_numeric($c_admindisplayitems)) unset($c_admindisplayitems);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Pause/resume a mailing...
if ($pause) @mysqli_query($db, "UPDATE mailing SET paused='1' WHERE type='wholesale'");
else if ($resume) @mysqli_query($db, "UPDATE mailing SET paused=NULL WHERE type='wholesale'");

// Check if a mailing is being sent...
$mailingresult = @mysqli_query($db, "SELECT * FROM mailing WHERE type='wholesale'");
$mailinginprogress = @mysqli_num_rows($mailingresult);
/*
if (!empty($mailinginprogress)) {
	$mailingid = @mysqli_result($mailingresult,0,"mailingid");
	$paused = @mysqli_result($mailingresult,0,"paused");
	$sentresult = @mysqli_query($db, "SELECT * FROM maillog WHERE mailingid='$mailingid'");
	$totalsent = @mysqli_num_rows($sentresult);

	if ($paused) $pauseresumeform = "<p><form action=\"wssalesadmin.php\" method=\"post\"><input type=\"submit\" name=\"resume\" value=\"".RESUME."\"></form></p>";
	else $pauseresumeform = "<p><form action=\"wssalesadmin.php\" method=\"post\"><input type=\"submit\" name=\"pause\" value=\"".PAUSE."\"></form></p>";

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
			parameters: 'mailingtype=wholesale&dummy='+ new Date().getTime(), 
			onSuccess: reportprogress
		}
	);
}

function startmailing() {
	var myAjax = new Ajax.Request(
		'mailuser.php', 
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

// Check if an eMerchant session is active...
$emcheckresult = @mysqli_query($db, "SELECT activity FROM emerchant_user WHERE username='admin'");
$emactivity = @mysqli_result($emcheckresult, 0, "activity");
$emactivity = explode(" ",$emactivity);
$date = date("Y/m/d", time()+$timezoneoffset);
if ($emactivity[0] == $date) $emactive = TRUE;
else $emactive = FALSE;

// Check if penny auctions are used...
$bidderresult = @mysqli_query($db, "SELECT productid FROM floatingprice LIMIT 1");
if (@mysqli_num_rows($bidderresult)) $auctionbidderslink = " [<a href=\"bidderadmin.php\" class=\"sm\">".AUCTIONBIDDERS."</a>]";
else $auctionbidderslink = "";

echo "$header
<script language=\"JavaScript\" type=\"text/javascript\">
<!--
function newWindow(newContent)
{
	winContent = window.open(newContent, 'c572bf4', 'top=109,width=800,height=550, toolbar=no,scrollbars=yes, resizable=no');
	x = (screen.availWidth-800)/2;
	y = (screen.availHeight-550)/2;
	winContent.moveTo(x,y);
}
function selectAll()
{
	if (document.customermailform.switchall.checked == true) {
		for (var i = 0; i < document.customermailform.elements.length; i++) {
			if (document.customermailform.elements[i].checked != true) {
				document.customermailform.elements[i].checked = true;
			}
		}
	} else {
		for (var i = 0; i < document.customermailform.elements.length; i++) {
			if (document.customermailform.elements[i].checked == true) {
				document.customermailform.elements[i].checked = false;
			}
		}
	}
}
-->
</script>
<div class=\"heading\">".CUSTOMERSANDMESSAGING." <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><center>";

if ($mailinginprogress) echo "<p><b>".MAILINGINPROGRESS."</b> <input type=\"button\" value=\"".VIEWSTATUS."\" onclick=\"document.location.href='mailingadmin.php?type=wholesale'\"></p>";

if ($msg == "sent") {
	echo "<span class=\"confirm\">".MESSAGESENT;
	if ($log) echo " <a href=\"../previews/$log\" target=\"_blank\">".VIEWLOG."</a>";
	echo "</span><br><br>";
}
else if ($msg == "activated") echo "<p align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#009000\"><b>".ACCOUNTACTIVATIONCOMPLETE."</b></font></p>";
echo "<span class=\"formtitle\">[<a href=\"salesadmin.php\" class=\"sm\">".ALLCUSTOMERS."</a>] ".WHOLESALECUSTOMERS;
if (file_exists("$ashoppath/emerchant/quote.php")) echo " [<a href=\"salesadmin.php?recurring=true\" class=\"sm\">".RECURRINGBILLINGCUSTOMERS."</a>]";
echo "$auctionbidderslink</span><br><br>";
echo "<table width=\"700\"><tr><td><form action=\"wssalesadmin.php?resultpage=$resultpage&admindisplayitems=$admindisplayitems\" method=\"post\" name=\"customerfilterform\" style=\"margin-bottom: 0px;\"><span class=\"text\">".FILTERBYNAME.": <input type=\"text\" name=\"namefilter\" value=\"$namefilter\" size=\"10\"> ".ANDOREMAIL.": <input type=\"text\" name=\"emailfilter\" value=\"$emailfilter\" size=\"10\"> <input type=\"submit\" value=\"".FILTER."\"></span></form></td>";
if (!$recurring) echo "<td align=\"right\"><span class=\"text\">".EXPORTTOCSV.": </span></td><td><form action=\"exportcustomers.php?ws=true\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" value=\"".DOWNLOAD."\"></form></td>";
echo "</tr></table><br>";
if (!$mailinginprogress) echo "<form action=\"mailuser.php\" method=\"post\" name=\"customermailform\" style=\"margin-bottom: 0px;\">";
echo "
      <table width=\"80%\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" bgcolor=\"#D0D0D0\">
      <tr class=\"reporthead\">";
if (!$mailinginprogress) echo "<td><input type=\"checkbox\" name=\"switchall\" onClick=\"selectAll();\"></td>";
echo "<td align=\"left\">".IDBUSINESS."</td><td align=\"left\">".CONTACTNAME."</td><td align=\"left\">".EMAIL."</td><td width=\"70\" align=\"center\">".ACTION."</td></tr>";

// Get customer information from database...
$sql="SELECT * FROM customer WHERE firstname IS NOT NULL AND email IS NOT NULL AND level>'0' AND level IS NOT NULL";
if ($namefilter || $emailfilter) $sql .= " AND ";
if ($namefilter) {
	$sql .= "(firstname  LIKE '%$namefilter%' OR lastname LIKE '%$namefilter%' OR businessname LIKE '%$namefilter%')";
	if ($emailfilter) $sql .= " AND email LIKE '%$emailfilter%'";
} else if ($emailfilter) $sql .= " email LIKE '%$emailfilter%'";
$sql .= " ORDER BY customerid";
$result = @mysqli_query($db, "$sql");
$numberofrows = intval(@mysqli_num_rows($result));
if (!$admindisplayitems) {
	if ($c_admindisplayitems) $admindisplayitems = $c_admindisplayitems;
	else $admindisplayitems = 10;
}
$numberofpages = ceil($numberofrows/$admindisplayitems);
if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $admindisplayitems;
else {
	$resultpage = 1;
	$startrow = 0;
}
$startpage = $resultpage - 9;
if ($numberofpages - $resultpage < 10) {
	$pagesleft = $numberofpages - $resultpage;
	$startpage = $startpage - (10 - $pagesleft);
}
if ($startpage < 1) $startpage = 1;
$stoprow = $startrow + $admindisplayitems;
@mysqli_data_seek($result, $startrow);
$thisrow = $startrow;
while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
    $firstname = $row["firstname"];
    $lastname = $row["lastname"];
	$businessname = $row["businessname"];
	$password = $row["password"];
    $customerid = $row["customerid"];
    $email = $row["email"];
	$thisrow++;
	echo "<tr class=\"reportline\">";
	if (!$mailinginprogress) echo "<td width=\"20\" align=\"left\"><input type=\"checkbox\" name=\"customer$customerid\"></td>";
	echo "<td nowrap align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><a href=\"edituser.php?customerid=$customerid\">$customerid</a>, ";
	if ($businessname) echo "$businessname";
	else echo "$firstname $lastname";
	echo "</font></td><td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$firstname $lastname</font></td><td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">";
	if ($emactive) echo "<a href=\"javascript:newWindow('../emerchant/composemessage.php?customer=$customerid')\">$email</a>";
	else echo "<a href=\"mailto:$email\">$email</a>";
	echo "</font></td><td width=\"70\" nowrap align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"edituser.php?customerid=$customerid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $customerid\" title=\"".PROFILEFOR." $customerid\" border=\"0\"></a>&nbsp;";
	if ($password) echo "<a href=\"salesreport.php?customerid=$customerid&generate=true&reporttype=wholesale\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $customerid\" title=\"".SALESHISTORYFOR." $customerid\" border=\"0\"></a>&nbsp;";
	else echo "<a href=\"mailuser.php?customer$customerid=on&activate=true\"><img src=\"images/icon_activate.gif\" alt=\"".ACTIVATEWHOLESALEACCOUNT." $customerid\" title=\"".ACTIVATEWHOLESALEACCOUNT." $customerid\" border=\"0\"></a>&nbsp;";
	echo "<a href=\"edituser.php?customerid=$customerid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETECUSTOMER." $customerid ".FROMDB."\" title=\"".DELETECUSTOMER." $customerid ".FROMDB."\" border=\"0\"></a></font></td></tr>";
}

echo "</table>\n";
if ($numberofrows > 5) {
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
	if ($numberofpages > 1) {
		echo "<b>".PAGE.": </b>";
		if ($resultpage > 1) {
			if ($resultpage > 10) echo "<a href=\"wssalesadmin.php?recurring=$recurring&resultpage=1&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\"><b>".FIRSTPAGE."</b></a> ";
			$previouspage = $resultpage-1;
			echo "<<<a href=\"wssalesadmin.php?recurring=$recurring&resultpage=$previouspage&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"wssalesadmin.php?recurring=$recurring&resultpage=$i&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\">";
			else echo "<span style=\"font-size: 18px;\">";
			echo "$i";
			if ($i != $resultpage) echo "</a>";
			else echo "</span>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"wssalesadmin.php?recurring=$recurring&resultpage=$nextpage&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\"><b>".NEXTPAGE."</b></a>>>";
		}
		if ($resultpage < ($numberofpages - 10)) echo " <a href=\"wssalesadmin.php?recurring=$recurring&resultpage=$numberofpages&admindisplayitems=$admindisplayitems&namefilter=$namefilter&emailfilter=$emailfilter\"><b>".LASTPAGE."</b></a> &nbsp;&nbsp;";
	}
	echo " ".DISPLAY.": <select name=\"admindisplayitems\" onChange=\"document.location.href='wssalesadmin.php?recurring=$recurring&resultpage=$resultpage&namefilter=$namefilter&emailfilter=$emailfilter&admindisplayitems='+customermailform.admindisplayitems.value;\"><option value=\"$numberofrows\">".SELECT."</option><option value=\"5\">5</option><option value=\"10\">10</option><option value=\"20\">20</option><option value=\"40\">40</option><option value=\"$numberofrows\">".ALL."</option></select> ".CUSTOMERS2."</td></tr></table>
	";
}

if (!$mailinginprogress) {
	echo "<table align=\"center\" cellpadding=\"10\"><tr><td><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><p>".SUBJECT.": <input type=\"text\" name=\"subject\" size=\"40\"></p><p>".MESSAGE.":<br><textarea name=\"message\" cols=\"60\" rows=\"10\"></textarea><br><span class=\"sm\">[".SUPPORTSCODES.": %firstname%, %lastname%, %email%, %address%, %state%,<br>%zip%, %city%, %country%, %phone%]</p><p><input type=\"radio\" name=\"mailformat\" value=\"html\"";
	if ($prefcusmailformat == "html") echo " checked";
	echo "> ".HTMLFORMAT." <input type=\"radio\" name=\"mailformat\" value=\"text\"";
	if ($prefcusmailformat == "text" || !$prefcusmailformat) echo "checked";
	echo "> ".PLAINTEXT."</p><p><input type=\"submit\" name=\"mail\" class=\"widebutton\" value=\"".MAILTOSELECTED."\"> <input type=\"submit\" name=\"mailall\"  class=\"widebutton\" value=\"".MAILTOALL."\"></p></font></form></td></tr></table>";
}
echo "</center>$footer";
?>