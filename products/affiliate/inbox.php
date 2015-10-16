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

include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";
include "checklogin.inc.php";
include "../admin/ashopconstants.inc.php";

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/af_inbox.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE sessionid='$affiliatesesid'";
$result = @mysqli_query($db, "$sql");

// Get the correct password for this affiliate...
$correctpasswd = @mysqli_result($result, 0, "password");

// Store affiliate information in variables...
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$affiliateid = @mysqli_result($result, 0, "affiliateid");
$referredby = @mysqli_result($result, 0, "referedby");

// Validate the read parameter...
if (!empty($read) && is_numeric($read)) {
	$readresult = @mysqli_query($db, "SELECT * FROM affiliatepm WHERE affiliatepmid='$read' AND toaffiliateid='$affiliateid'");
	if (!@mysqli_num_rows($readresult)) $read = 0;
	else @mysqli_query($db, "UPDATE affiliatepm SET hasbeenread='1' WHERE affiliatepmid='$read'");
}

// Delete a PM...
if (!empty($deletepm) && is_numeric($deletepm)) @mysqli_query($db, "DELETE FROM affiliatepm WHERE affiliatepmid='$deletepm' AND toaffiliateid='$affiliateid'");

// Get number of unread PMs...
$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND (hasbeenread='' OR hasbeenread='0' OR hasbeenread IS NULL) ORDER BY sentdate DESC";
$unreadresult = @mysqli_query($db, "$sql");
$unreadcount = @mysqli_num_rows($unreadresult);

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/affiliate.html");

echo "<br><table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr><td align=\"left\"><span class=\"ashopaffiliateheader\">".WELCOME." $firstname $lastname! ".AFFILIATEID.": $affiliateid</span></td>$salesreplink</tr></table>
	<table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"affiliate.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".STATISTICS."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"affiliate.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".STATISTICS."\"></a></td>";
echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changeprofile.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".VIEWPROFILE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changepassword.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".CHANGEPASS."\"></a></td>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"parties.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".PARTIES."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"login.php?logout\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"login.php?logout\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
echo "</tr></table>
	<table align=\"center\" width=\"400\"><tr>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"orderhistory.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".ORDERHISTORY."\"></a></td>";
if ($activateleads) {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"leads.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LEADS."\"></a></td>";
} else {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".DOWNLINE."\"></a></td>";
}

if (!empty($read) && $read > 0) {
	echo "<td align=\"center\"><a class=\"";
	if ($activateleads) echo "ashopaffiliatebuttonsmall";
	else echo "ashopaffiliatebutton";
	echo "\" href=\"inbox.php\"><input class=\"";
	if ($activateleads) echo "ashopaffiliatebuttonsmall";
	else echo "ashopaffiliatebutton";
	echo "\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td></tr></table>";
	$received = @mysqli_result($readresult, 0, "sentdate");
	$received = substr($received,0,-3);
	$senderid = @mysqli_result($readresult, 0, "fromaffiliateid");
	if ($senderid == -1) $sender = SHOPADMIN;
	else {
		$senderresult = @mysqli_query($db, "SELECT firstname, lastname, referedby FROM affiliate WHERE affiliateid='$senderid'");
		$senderfirstname = @mysqli_result($senderresult,0,"firstname");
		$senderlastname = @mysqli_result($senderresult,0,"lastname");
		$senderreferredby = @mysqli_result($senderresult,0,"referedby");
		if (!empty($senderfirstname) && !empty($senderlastname)) $sender = $senderfirstname." ".$senderlastname;
		else if (!empty($senderfirstname)) $sender = $senderfirstname;
		else $sender = $senderlastname;
		if ($senderid == $referredby) $sender .= " ".SPONSOR;
		else if ($senderreferredby == $affiliateid) $sender .= " [".DOWNLINE."]";
		else $sender .= " ".UPLINE;
	}
	$subject = @mysqli_result($readresult, $i, "subject");
	$message = @mysqli_result($readresult, $i, "message");
	echo "
	<p><table class=\"ashopaffiliatemessagebox\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\">
	<tr><td align=\"right\" width=\"100\"><span class=\"ashopaffiliatemessagestext3\">&nbsp;".RECEIVED.":</span></td><td align=\"left\"><span class=\"ashopaffiliatemessagestext2\">$received</span></td></tr><td align=\"right\"><span class=\"ashopaffiliatemessagestext3\">".FROM.":</span></td><td align=\"left\"><span class=\"ashopaffiliatemessagestext2\">$sender</span></td></tr><tr><td align=\"right\"><span class=\"ashopaffiliatemessagestext3\">&nbsp;".SUBJECT.":</span></td><td align=\"left\"><span class=\"ashopaffiliatemessagestext2\">$subject</span></td></tr><td align=\"right\"><span class=\"ashopaffiliatemessagestext3\">&nbsp;</span></td><td align=\"left\"><span class=\"ashopaffiliatemessagestext2\"><hr>$message<br><br></span></td></tr>
	<tr><td>&nbsp;</td><td align=\"right\"><form action=\"inbox.php\" method=\"post\"><input type=\"submit\" value=\"Delete\"><input type=\"hidden\" name=\"deletepm\" value=\"$read\"></form></table></p>";

} else {
	echo "
	<td align=\"center\"><input class=\"";
	if ($activateleads) echo "ashopaffiliatebuttonsmall";
	else echo "ashopaffiliatebutton";
	echo "\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\" disabled></td></tr></table>";

	$nomessages = TRUE;

	// Get message list from database...
	if (@mysqli_num_rows($unreadresult) != 0) {
		$nomessages = FALSE;
		echo "<br><span class=\"ashopaffiliateheader\">".UNREAD."</span>
		<p><table class=\"ashopaffiliatemessagesbox\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\">
		<tr class=\"ashopaffiliatemessagesrow\"><td align=\"left\" width=\"130\"><span class=\"ashopaffiliatemessagestext1\">&nbsp;".RECEIVED."</span></td><td align=\"left\" width=\"160\"><span class=\"ashopaffiliatemessagestext1\">".FROM."</span></td><td align=\"left\"><span class=\"ashopaffiliatemessagestext1\">&nbsp;".SUBJECT."</span></td></tr>";
		for ($i = 0; $i < @mysqli_num_rows($unreadresult);$i++) {
			$received = @mysqli_result($unreadresult, $i, "sentdate");
			$received = substr($received,0,-3);
			$pmid = @mysqli_result($unreadresult, $i, "affiliatepmid");
			$senderid = @mysqli_result($unreadresult, $i, "fromaffiliateid");
			if ($senderid == -1) $sender = SHOPADMIN;
			else {
				$senderresult = @mysqli_query($db, "SELECT firstname, lastname, referedby FROM affiliate WHERE affiliateid='$senderid'");
				$senderfirstname = @mysqli_result($senderresult,0,"firstname");
				$senderlastname = @mysqli_result($senderresult,0,"lastname");
				$senderreferredby = @mysqli_result($senderresult,0,"referedby");
				if (!empty($senderfirstname) && !empty($senderlastname)) $sender = $senderfirstname." ".$senderlastname;
				else if (!empty($senderfirstname)) $sender = $senderfirstname;
				else $sender = $senderlastname;
				if ($senderid == $referredby) $sender .= " ".SPONSOR;
				else if ($senderreferredby == $affiliateid) $sender .= " [".DOWNLINE."]";
				else $sender .= " ".UPLINE;
			}
			$subject = @mysqli_result($unreadresult, $i, "subject");
			echo "<tr><td align=\"left\"><span class=\"ashopaffiliatemessagestext2\">$received</span></td><td><span class=\"ashopaffiliatemessagestext2\">$sender</span></td><td><span class=\"ashopaffiliatemessagestext2\"><a href=\"inbox.php?read=$pmid\">$subject</a></span></td></tr>";
		}
		echo "</table></p>";
	}
	
	// Get message list from database...
	$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND hasbeenread='1' ORDER BY sentdate DESC";
	$result = @mysqli_query($db, "$sql");
	$unreadcount = @mysqli_num_rows($result);
	if (@mysqli_num_rows($result) != 0) {
		$nomessages = FALSE;
		echo "<br><span class=\"ashopaffiliateheader\">".MESSAGES."</span>
		<p><table class=\"ashopaffiliatemessagesbox\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\">
		<tr class=\"ashopaffiliatemessagesrow\"><td align=\"left\" width=\"130\"><span class=\"ashopaffiliatemessagestext1\">&nbsp;".RECEIVED."</span></td><td align=\"left\" width=\"160\"><span class=\"ashopaffiliatemessagestext1\">".FROM."</span></td><td align=\"left\"><span class=\"ashopaffiliatemessagestext1\">&nbsp;".SUBJECT."</span></td></tr>";
		for ($i = 0; $i < @mysqli_num_rows($result);$i++) {
			$received = @mysqli_result($result, $i, "sentdate");
			$received = substr($received,0,-3);
			$pmid = @mysqli_result($result, $i, "affiliatepmid");
			$senderid = @mysqli_result($result, $i, "fromaffiliateid");
			if ($senderid == -1) $sender = SHOPADMIN;
			else {
				$senderresult = @mysqli_query($db, "SELECT firstname, lastname, referedby FROM affiliate WHERE affiliateid='$senderid'");
				$senderfirstname = @mysqli_result($senderresult,0,"firstname");
				$senderlastname = @mysqli_result($senderresult,0,"lastname");
				$senderreferredby = @mysqli_result($senderresult,0,"referedby");
				if (!empty($senderfirstname) && !empty($senderlastname)) $sender = $senderfirstname." ".$senderlastname;
				else if (!empty($senderfirstname)) $sender = $senderfirstname;
				else $sender = $senderlastname;
				if ($senderid == $referredby) $sender .= " ".SPONSOR;
				else if ($senderreferredby == $affiliateid) $sender .= " [".DOWNLINE."]";
				else $sender .= " ".UPLINE;
			}
			$subject = @mysqli_result($result, $i, "subject");
			echo "<tr><td align=\"left\"><span class=\"ashopaffiliatemessagestext2\">$received</span></td><td><span class=\"ashopaffiliatemessagestext2\">$sender</span></td><td><span class=\"ashopaffiliatemessagestext2\"><a href=\"inbox.php?read=$pmid\">$subject</a></span></td></tr>";
		}
		echo "</table></p>";
	}

	if ($nomessages) echo "<br><span class=\"ashopaffiliateheader\">".NOMESSAGES."</span>";
}

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");

// Close database...
@mysqli_close($db);
?>