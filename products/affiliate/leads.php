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

if (!$activateleads) {
	header("Location: affiliate.php");
	exit;
}

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/af_leads.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE sessionid='$affiliatesesid'";
$result = @mysqli_query($db, "$sql");

// Store affiliate information in variables...
$afffirstname = @mysqli_result($result, 0, "firstname");
$afflastname = @mysqli_result($result, 0, "lastname");
$affiliateid = @mysqli_result($result, 0, "affiliateid");

// Download leads as a CSV...
if (!empty($download) || !empty($view)) {
$leadsinterests = @mysqli_real_escape_string($db, $leadsinterests);
if ($leadscountry == "none") $leadscountry = "";
$leadscountry = @mysqli_real_escape_string($db, $leadscountry);
if ($leadsstate == "none") $leadsstate = "";
$leadsstate = @mysqli_real_escape_string($db, $leadsstate);
foreach ($countries as $shortcountry => $longcountry) {
	if ($leadscountry == $shortcountry || $leadscountry == $longcountry) {
		$leadsshortcountry = $shortcountry;
		$leadslongcountry = $longcountry;
	}
}
foreach ($uscanstates as $shortstate => $longstate) {
	if ($leadsstate == $shortcountry || $leadsstate == $longcountry) {
		$leadsshortstate = $shortcountry;
		$leadslongstate = $longcountry;
	}
}
$leadsinterests = strtoupper($leadsinterests);
if (!empty($leadsinterests)) {
	$sql="SELECT DISTINCT customer.customerid FROM customer, orders WHERE customer.affiliateid='$affiliateid' AND customer.customerid=orders.customerid AND UPPER(orders.description) LIKE '%$leadsinterests%'";
	if (!empty($leadscountry)) {
		$sql.=" AND (customer.country='$leadscountry'";
		if (!empty($leadsshortcountry)) $sql .= " OR customer.country='$leadsshortcountry'";
		if (!empty($leadslongcountry)) $sql .= " OR customer.country='$leadslongcountry'";
		$sql.= ")";
	}
	if (!empty($leadsstate)) {
		$sql.=" AND (customer.state='$leadsstate'";
		if (!empty($leadsshortstate)) $sql .= " OR customer.state='$leadsshortstate'";
		if (!empty($leadslongstate)) $sql .= " OR customer.state='$leadslongstate'";
		$sql.= ")";
	}
	$sql.=" ORDER BY customer.lastname";
} else {
	$sql="SELECT DISTINCT customer.customerid FROM customer WHERE affiliateid='$affiliateid' ";
	if (!empty($leadscountry)) {
		$sql.=" AND (customer.country='$leadscountry'";
		if (!empty($leadsshortcountry)) $sql .= " OR customer.country='$leadsshortcountry'";
		if (!empty($leadslongcountry)) $sql .= " OR customer.country='$leadslongcountry'";
		$sql.= ")";
	}
	if (!empty($leadsstate)) {
		$sql.=" AND (customer.state='$leadsstate'";
		if (!empty($leadsshortstate)) $sql .= " OR customer.state='$leadsshortstate'";
		if (!empty($leadslongstate)) $sql .= " OR customer.state='$leadslongstate'";
		$sql.= ")";
	}
	$sql.=" ORDER BY lastname";
}
$result = @mysqli_query($db, "$sql");
$leadslist = "";
if (@mysqli_num_rows($result) != 0) {
	if (!empty($download)) {
		header ("Content-Type: application/octet-stream");
		header ("Content-Disposition: attachment; filename=leads.csv");
		echo NAME.";".EMAIL.";".PHONE.";".ORDERS."\n";
	} else $leadslist = "<p><table class=\"ashopaffiliateleadsbox\" width=\"800\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\"><tr class=\"ashopaffiliateleadsrow\"><td><span class=\"ashopaffiliateleadstext1\">".NAME."</span></td><td><span class=\"ashopaffiliateleadstext1\">".EMAIL."</span></td><td><span class=\"ashopaffiliateleadstext1\">".PHONE."</span></td><td><span class=\"ashopaffiliateleadstext1\">".ORDERS."</span></td></tr>";
	for ($i = 0; $i < @mysqli_num_rows($result);$i++) {
		$customerid = @mysqli_result($result, $i, "customerid");
		$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
		$firstname = @mysqli_result($customerresult, 0, "firstname");
		$lastname = @mysqli_result($customerresult, 0, "lastname");
		if (!empty($firstname) && !empty($lastname)) $fullname = "$firstname $lastname";
		else if (!empty($firstname)) $fullname = $firstname;
		else if (!empty($lastname)) $fullname = $lastname;
		else $fullname = "Unknown";
		$email = @mysqli_result($customerresult, 0, "email");
		$phone = @mysqli_result($customerresult, 0, "phone");
		$orderresult = @mysqli_query($db, "SELECT orderid FROM orders WHERE customerid='$customerid' AND paid!='' AND paid IS NOT NULL");
		$ordercount = @mysqli_num_rows($orderresult);
		if (!empty($download)) echo "$fullname;$email;$phone;$ordercount\n";
		else $leadslist .= "<tr><td><span class=\"ashopaffiliatetext3\">$fullname</span></td><td><span class=\"ashopaffiliatetext3\"><a href=\"mailto:$email\">$email</a></span></td><td><span class=\"ashopaffiliatetext3\">$phone</span></td><td align=\"center\"><span class=\"ashopaffiliatetext3\">$ordercount</span></td></tr>";
	}
	if (!empty($download)) exit;
	else $leadslist .= "</table></p>";
} else $msg = "noleads";
}

// Get number of unread PMs...
$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND (hasbeenread='' OR hasbeenread='0' OR hasbeenread IS NULL)";
$unreadresult = @mysqli_query($db, "$sql");
$unreadcount = @mysqli_num_rows($unreadresult);

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/affiliate.html");

echo "
<script language=\"JavaScript\" src=\"../includes/switchstates.js.php\" type=\"text/javascript\"></script>
<br><table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr><td align=\"left\"><span class=\"ashopaffiliateheader\">".WELCOME." $afffirstname $afflastname! ".AFFILIATEID.": $affiliateid</span></td>$salesreplink</tr></table>
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
echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"orderhistory.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".ORDERHISTORY."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LEADS."\" disabled></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"inbox.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td></tr></table>
	<br><span class=\"ashopaffiliateheader\">".YOURLEADS."</span>";

	if ($msg == "noleads") echo "<br><br><span class=\"ashopaffiliatetext2\">".NOLEADSFOUND."</span>";

// Check if this affiliate has any leads...
$sql="SELECT customerid FROM customer WHERE affiliateid='$affiliateid' ORDER BY lastname";
$result = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($result) != 0) {
	echo "
	<form action=\"leads.php\" method=\"post\" name=\"signupform\">
	<p><table width=\"450\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\">
	<tr><td align=\"right\" width=\"100\"><span class=\"ashopaffiliatetext1\">".COUNTRY.": </span></td><td align=\"left\"><select name=\"leadscountry\" onChange=\"switchStates(document.getElementById('state'),document.signupform.leadsprovince,document.signupform.leadscountry.value);\"><option  value=\"\">".CHOOSECOUNTRY;
foreach ($countries as $shortcountry => $longcountry) {
	if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
	echo "<option value=\"$shortcountry\"";
	if ($leadscountry == $shortcountry) echo " selected";
	echo ">$longcountry\n";
}
echo "</select></td></tr>
	<tr id=\"stateselector\" style=\"display:none\"><td align=\"right\" width=\"100\"><span class=\"ashopaffiliatetext1\">".STATE.": </span></td><td align=\"left\"><select name=\"leadsstate\" id=\"state\"><option value=\"\">choose...</select></td></tr>
	<tr id=\"regionrow\" style=\"display:none\"><td align=\"right\" width=\"100\"><span class=\"ashopaffiliatetext1\">".PROVINCE.": </span></td><td align=\"left\"><input type=text name=\"leadsprovince\" size=20></td></tr>";
	if (!empty($leadscountry)) {
		echo "<script language=\"JavaScript\" type=\"text/javascript\">
		switchStates(document.signupform.leadsstate,document.signupform.leadsprovince,document.signupform.leadscountry.value);";
		if (!empty($leadsstate)) echo "document.signupform.leadsstate.value = '$leadsstate';";
		else if (!empty($leadsprovince)) echo "document.signupform.leadsprovince.value = '$leadsprovince';";
		echo "</script>";
	}
	echo "
	<tr><td align=\"right\" width=\"100\"><span class=\"ashopaffiliatetext1\">".INTERESTS.": </span></td><td align=\"left\"><input type=\"text\" name=\"leadsinterests\" value=\"$leadsinterests\" size=\"50\"></td></tr>
	<tr><td align=\"right\" width=\"100\">&nbsp;</td><td align=\"right\"><input type=\"submit\" name=\"download\" value=\"".DOWNLOAD."\"> <input type=\"submit\" name=\"view\" value=\"".VIEW."\"></td></tr>
	</table></p>
	</form>";
} else echo "
<br><br><span class=\"ashopaffiliatetext2\">".NOLEADS."</span>";

if ($leadslist) echo $leadslist;


// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");

// Close database...
@mysqli_close($db);
?>