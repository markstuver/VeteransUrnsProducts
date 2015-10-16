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

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/af_affiliate.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE sessionid='$affiliatesesid'";
$result = @mysqli_query($db, "$sql");

// Store affiliate information in variables...
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$affiliateid = @mysqli_result($result, 0, "affiliateid");
$correctpasswd = @mysqli_result($result, 0, "password");
$referralcode = @mysqli_result($result, 0, "referralcode");
$username = @mysqli_result($result, 0, "user");

// Get number of unread PMs...
$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND (hasbeenread='' OR hasbeenread='0' OR hasbeenread IS NULL)";
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
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".LINKCODES."\" disabled></td>";
else echo "<td align=\"center\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LINKCODES."\" disabled></td>";
echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"orderhistory.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".ORDERHISTORY."\"></a></td>";
if ($activateleads) {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"leads.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LEADS."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"inbox.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
} else {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"inbox.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
}
echo "
	</tr></table>";
$affiliatelink = "$ashopurl/affiliate.php?id=$affiliateid";
$affiliatelinklength = strlen($affiliatelink);
echo "<p><span class=\"ashopaffiliatetext1\">".YOURLINK.":</span> <input id=\"affiliatelink\" type=\"text\" size=\"$affiliatelinklength\" value=\"$ashopurl/affiliate.php?id=$affiliateid\" onclick=\"document.getElementById('affiliatelink').select();\"></p>";
echo "
	<p><span class=\"ashopaffiliatetext1\">".MANUALCODE.":</span><span class=\"ashopaffiliatetext2\"> $referralcode</span></p>
	<p><span class=\"ashopaffiliatetext2\">";

// Show recruitment link if needed...
if ($secondtieractivated) {
	$sql="SELECT * FROM linkcodes WHERE linkid = 1";
	$result = @mysqli_query($db, "$sql");
	if (@mysqli_num_rows($result)) {
		$thislinktext = @mysqli_result($result, 0, "linktext");
		$newlinktext = str_replace("%affiliatelink%","$ashopurl/affiliate.php?id=$affiliateid&redirect=$ashopurl/affiliate/signupform.php",$thislinktext);
		$newlinktext2 = str_replace("&gt;",">",$newlinktext);
		$newlinktext2 = str_replace("&lt;","<",$newlinktext2);
		$thisfilename = @mysqli_result($result, 0, "filename");
		$thisalt = @mysqli_result($result, $i, "alt");
		echo "<p>".COPYLINK."</p><p><table class=\"ashopaffiliatecodebox\"><tr><td align=\"center\" colspan=\"2\">";
		if ($thisfilename) echo "<img src=\"../banners/$thisfilename\" alt=\"$thisalt\" border=\"0\"><br>";
		echo "<span class=\"ashopaffiliatetext2\">$newlinktext2</span></td></tr><tr><td align=\"right\"><span class=\"ashopaffiliatetext1\">".HTMLCODE."</span></td><td><textarea name=\"linktext\" readonly cols=\"50\" rows=\"5\" align=\"top\">";
		if ($thisfilename) echo "&lt;a href=\"$ashopurl/affiliate.php?id=$affiliateid&redirect=$ashopurl/affiliate/signupform.php\"&gt;&lt;img src=\"$ashopurl/banners/$thisfilename\" alt=\"$thisalt\" border=\"0\"&gt;&lt;/a&gt;&lt;br&gt;";
		echo "$newlinktext</textarea></td></tr></table></p>";
	}
}

echo "<p><span class=\"ashopaffiliatetext2\">".COPYPASTE."</span></p><table class=\"ashopaffiliatecodeframe\"><tr><td class=\"ashopaffiliatecategoriesbox\">		<table class=\"ashopaffiliatecategoriestable\" cellspacing=\"0\">
	  <tr><td class=\"ashopaffiliatecategoriesheader\">&nbsp;&nbsp;".LINKCODES."</td></tr>";

// Set default link code category...
if (empty($linkcat) || !is_numeric($linkcat)) {
	$result = @mysqli_query($db, "SELECT * FROM linkcategories ORDER BY linkcategoryname ASC LIMIT 1");
	$linkcat = @mysqli_result($result,0,"linkcategoryid");
	$showdefault = TRUE;
} else $showdefault = FALSE;

// Get link code categories...
$result = @mysqli_query($db, "SELECT * FROM linkcategories ORDER BY linkcategoryname ASC");
while($row = @mysqli_fetch_array($result)) {
	$linkcategoryid = $row["linkcategoryid"];
	$linkcategoryname = $row["linkcategoryname"];
	if ($linkcat == $linkcategoryid) echo "
	  <tr><td class=\"ashopaffiliateselectedcategory\"><table width=\"100%\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\"><tr><td width=\"16\" valign=\"top\">
			  <img src=\"../images/invisible.gif\" border=\"0\" width=\"12\" vspace=\"3\" alt=\"invisible.gif\"></td><td><a href=\"linkcodes.php?affuser=$affuser&linkcat=$linkcategoryid\" style=\"text-decoration: none\"><span class=\"ashopselectedcategory\">$linkcategoryname</span></a></td></tr></table></td></tr>";
	else echo "
	  <tr><td class=\"ashopaffiliatecategory\"><table width=\"100%\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\"><tr><td width=\"16\" valign=\"top\">
			  <img src=\"../images/invisible.gif\" border=\"0\" width=\"12\" vspace=\"3\" alt=\"invisible.gif\"></td><td><a href=\"linkcodes.php?affuser=$affuser&linkcat=$linkcategoryid\" style=\"text-decoration: none\"><span class=\"ashopcategory\">$linkcategoryname</span></a></td></tr></table></td></tr>";
}

echo "
	</table>
	</td><td valign=\"top\">";

// Get link code information from database...
$sql="SELECT * FROM linkcodes WHERE linkid > 1 AND linkcategoryid='$linkcat'";
$result = @mysqli_query($db, "$sql");
if (!@mysqli_num_rows($result) && $showdefault) echo "<table class=\"ashopaffiliatecodebox\"><tr><td align=\"center\" colspan=\"2\"><span class=\"ashopaffiliatetext2\"><u>$ashopname</u></span></td></tr><tr><td align=\"right\"><span class=\"ashopaffiliatetext1\">".HTMLCODE."</span></td><td><textarea name=\"linktext\" readonly cols=\"50\" rows=\"5\" align=\"top\">&lt;a href=\"$ashopurl/affiliate.php?id=$affiliateid\"&gt;$ashopname&lt/a&gt;</textarea></td></tr></table><br><br>";
for ($i = 0; $i < @mysqli_num_rows($result); $i++) { 
    $thisredirect = @mysqli_result($result, $i, "redirect");
    $thislinktext = @mysqli_result($result, $i, "linktext");
	$thisredirect = str_replace("%affiliateuser%",$username,$thisredirect);
    $thisfilename = @mysqli_result($result, $i, "filename");
    $thislinkid = @mysqli_result($result, $i, "linkid");
    $thisalt = @mysqli_result($result, $i, "alt");
	$isreplicatedsite = FALSE;
	if (!empty($thisredirect) && empty($thislinktext) && empty($thisfilename)) $isreplicatedsite = TRUE;
	else {
		$thisredirect = str_replace($ashopurl,"",$thisredirect);
		$thisredirect = str_replace($ashopsurl,"",$thisredirect);
		if(substr($thisredirect,0,1) == "/") $thisredirect = substr($thisredirect,1);
	}
	if ($thisredirect) {
		$newlinktext = str_replace("%affiliatelink%","$ashopurl/affiliate.php?id=$affiliateid&redirect=$thisredirect",$thislinktext);
		$newlinktext = str_replace("%affiliatecloaklink%","&lt;a href=\"$ashopurl\" onClick=\"window.open('$ashopurl/affiliate.php?id=$affiliateid&redirect=$thisredirect', 'PGM', 'scrollbars=yes, toolbar=yes, status=yes, menubar=yes location=yes resizable=yes'); return false;\"&gt;",$newlinktext);
	} else {
		$newlinktext = str_replace("%affiliatelink%","$ashopurl/affiliate.php?id=$affiliateid",$thislinktext);
		$newlinktext = str_replace("%affiliatecloaklink%","&lt;a href=\"$ashopurl\" onClick=\"window.open('$ashopurl/affiliate.php?id=$affiliateid', 'PGM', 'scrollbars=yes, toolbar=yes, status=yes, menubar=yes location=yes resizable=yes'); return false;\"&gt;",$newlinktext);
	}
	$newlinktext2 = str_replace("&gt;",">",$newlinktext);
	$newlinktext2 = str_replace("&lt;","<",$newlinktext2);
    echo "<table class=\"ashopaffiliatecodebox\"><tr><td align=\"center\" colspan=\"2\">";
	if ($thisfilename) echo "<img src=\"../banners/$thisfilename\" alt=\"$thisalt\" border=\"0\"><br>";
	if ($isreplicatedsite) echo "&nbsp;</td><tr><td><td align=\"right\" width=\"90\"><span class=\"ashopaffiliatetext1\">".URL."</span></td><td><textarea name=\"linktext\" readonly cols=\"50\" rows=\"2\" align=\"top\">$thisredirect</textarea></td></tr>";
	else {
		echo "<span class=\"ashopaffiliatetext2\">$newlinktext2</span></td></tr><tr><td align=\"right\" width=\"90\"><span class=\"ashopaffiliatetext1\">".HTMLCODE."</span></td><td><textarea name=\"linktext\" readonly cols=\"50\" rows=\"5\" align=\"top\">";
		if ($thisfilename) {
			echo "&lt;a href=\"$ashopurl/affiliate.php?id=$affiliateid";
			if ($thisredirect) echo "&redirect=$thisredirect";
			echo "\"&gt;&lt;img src=\"$ashopurl/banners/$thisfilename\" alt=\"$thisalt\" border=\"0\"&gt;&lt;/a&gt;&lt;br&gt;";
		}
		echo "$newlinktext</textarea></td></tr>";
	}
	echo "</table><br>";
}

	echo "</td></tr></table></font></font><br>";

// Close database...

@mysqli_close($db);

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
?>