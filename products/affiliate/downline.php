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

@set_time_limit(0);
include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";
include "checklogin.inc.php";

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/af_downline.inc.php";

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

// Get number of unread PMs...
$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND (hasbeenread='' OR hasbeenread='0' OR hasbeenread IS NULL)";
$unreadresult = @mysqli_query($db, "$sql");
$unreadcount = @mysqli_num_rows($unreadresult);

// Set current date and time...
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

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
	<td align=\"center\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".DOWNLINE."\" disabled></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"leads.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LEADS."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"inbox.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
} else {
	echo "	
	<td align=\"center\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".DOWNLINE."\" disabled></td><td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"inbox.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
}
echo "
	</tr></table>";

if (!empty($sendpmto) && !empty($pmsubject) && !empty($pmtext)) echo "<br><span class=\"ashopaffiliatetext1\">".MESSAGESENT."</span><br>";

if ($referredby) {
	// Get sponsor details..
	$sponsorresult = @mysqli_query($db, "SELECT * FROM affiliate WHERE affiliateid='$referredby'");
	$sponsorfirstname = @mysqli_result($sponsorresult,0,"firstname");
	$sponsorlastname = @mysqli_result($sponsorresult,0,"lastname");
	echo "<br><span class=\"ashopaffiliatetext1\">".SPONSOR.": </span><span class=\"ashopaffiliatetext2\">$sponsorfirstname $sponsorlastname</span><br>";
	if (!empty($sendpmto) && $sendpmto == "sponsor" && !empty($pmsubject) && !empty($pmtext)) @mysqli_query($db, "INSERT INTO affiliatepm (toaffiliateid, fromaffiliateid, sentdate, subject, message) VALUES ('$referredby', '$affiliateid', '$date', '$pmsubject', '$pmtext')");
}
echo "<br><form action=\"downline.php\" method=\"post\">
	<table width=\"450\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\">";
if ($referredby) echo "<tr><td align=\"right\" width=\"100\"><span class=\"ashopaffiliatetext1\">".PMYOUR.": </span></td><td align=\"left\"><select name=\"sendpmto\"><option value=\"sponsor\">".PMSPONSOR."</option><option value=\"downline\">".DOWNLINE."</option></select></td></tr>";
else echo "<tr><td colspan=\"2\"><span class=\"ashopaffiliatetext1\">".PMYOUR." ".DOWNLINE."<input type=\"hidden\" name=\"sendpmto\" value=\"downline\"></span></td></tr>
";
echo "<tr><td align=\"right\" width=\"100\"><span class=\"ashopaffiliatetext1\">".SUBJECT.": </span></td><td align=\"left\"><input type=\"text\" name=\"pmsubject\" size=\"50\"></td></tr>
<tr><td align=\"right\" width=\"100\"><span class=\"ashopaffiliatetext1\">".MESSAGE.": </span></td><td align=\"left\"><textarea name=\"pmtext\" cols=\"38\" rows=\"5\"></textarea></td></tr>
<tr><td align=\"right\" width=\"100\">&nbsp;</td><td align=\"right\"><input type=\"submit\" value=\"".SENDPM."\"></td></tr>
</table>
	";

// Get referral statistics...
function generatedownline($affiliateid,$tier=1) {
	global $db, $tier1referrals, $tier2referrals, $date;
	$tierresult = @mysqli_query($db, "SELECT * FROM affiliate WHERE referedby='$affiliateid' ORDER BY signedup DESC");
	$tierreferrals = @mysqli_num_rows($tierresult);
	if ($tier == 1) $tier1referrals += $tierreferrals;
	else if ($tier == 2) $tier2referrals += $tierreferrals;
	while ($tierrow = @mysqli_fetch_array($tierresult)) {
		$sql="SELECT orderid FROM orderaffiliate WHERE affiliateid='{$tierrow["affiliateid"]}' AND (secondtier='0' OR secondtier IS NULL)";
		$tierordersresult = @mysqli_query($db, "$sql");
		$tierorders = @mysqli_num_rows($tierordersresult);
		$tiersigneduparray = explode(" ",$tierrow["signedup"]);
		$tiersignedup = $tiersigneduparray[0];
		$tierlastdatearray = explode(" ",$tierrow["lastdate"]);
		$tierlastdate = $tierlastdatearray[0];
		$tieraffiliateid = $tierrow["affiliateid"];
		$displaytier = $tier+1;
		echo "<tr><td align=\"center\"><input type=\"checkbox\" name=\"affiliate$tieraffiliateid\"></td><td align=\"center\"><span class=\"ashopaffiliatetext3\">$displaytier</span></td><td><span class=\"ashopaffiliatetext3\">{$tierrow["firstname"]} {$tierrow["lastname"]}</span></td><td align=\"center\"><span class=\"ashopaffiliatetext3\">$tierorders</span></td><td><span class=\"ashopaffiliatetext3\">$tiersignedup</span></td><td><span class=\"ashopaffiliatetext3\">$tierlastdate</span></td></tr>";
		if (!empty($_POST["sendpmto"]) && $_POST["sendpmto"] == "downline" && !empty($_POST["pmsubject"]) && !empty($_POST["pmtext"]) && $_POST["affiliate$tieraffiliateid"] == "on") @mysqli_query($db, "INSERT INTO affiliatepm (toaffiliateid, fromaffiliateid, sentdate, subject, message) VALUES ('$tieraffiliateid', '$affiliateid', '$date', '{$_POST["pmsubject"]}', '{$_POST["pmtext"]}')");
		generatedownline($tieraffiliateid,$tier+1);
	}
}

echo "<br><span class=\"ashopaffiliateheader\">".DOWNLINE."</span></center>
<p><table class=\"ashopaffiliatehistorybox\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\"><tr class=\"ashopaffiliatehistoryrow\"><td width=\"20\">&nbsp;</td><td><span class=\"ashopaffiliatehistorytext1\">".REFERRALLEVEL."</span></td><td><span class=\"ashopaffiliatehistorytext1\">".NAME."</span></td><td><span class=\"ashopaffiliatehistorytext1\">".ORDERS."</span></td><td><span class=\"ashopaffiliatehistorytext1\">".REGISTRATIONDATE."</span></td><td><span class=\"ashopaffiliatehistorytext1\">".ACTIVITY."</span></td></tr>";

generatedownline($affiliateid);

echo "</form></table></p>";

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");

// Close database...
@mysqli_close($db);
?>