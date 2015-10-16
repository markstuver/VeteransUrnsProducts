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
include "../language/$lang/af_changepassword.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE sessionid='$affiliatesesid'";
$result = @mysqli_query($db, "$sql");

// Get the correct password for this affiliate...
$correctpasswd = @mysqli_result($result, 0, "password");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");

// Update password...
if ($newpassword1 && $newpassword2 && $oldpassword) {
	if (($newpassword1 == $newpassword2) && ($oldpassword == $correctpasswd)) {

		// Set current date and time...
		$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

		$sql = "UPDATE affiliate SET password='$newpassword1', updated='$date' WHERE sessionid='$affiliatesesid'";
		$result = @mysqli_query($db, "$sql");
		header("Location: affiliate.php");
		exit;
	} else if ($newpassword1 != $newpassword2) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/affiliate-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/affiliate.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
		<p><span class=\"ashopmessage\">".DIDNOTMATCH."</span></p>
		<p><span class=\"ashopmessage\"><a 
		href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
		exit;
	} else if ($oldpassword != $correctpasswd) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/affiliate-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/affiliate.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".WRONGPASS."</span></p>
		<p><span class=\"ashopmessage\">".INCORRECTPASS."</span></p>
		<p><span class=\"ashopmessage\"><a 
		href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
		exit;
	}
}


// Store affiliate information in variables...
$affiliateid = @mysqli_result($result, 0, "affiliateid");

// Get number of unread PMs...
$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND (hasbeenread='' OR hasbeenread='0' OR hasbeenread IS NULL)";
$unreadresult = @mysqli_query($db, "$sql");
$unreadcount = @mysqli_num_rows($unreadresult);

// Close database...
@mysqli_close($db);

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/affiliate.html");

echo "<br><table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr><td align=\"left\"><span class=\"ashopaffiliateheader\">".WELCOME." $firstname $lastname! ".AFFILIATEID.": $affiliateid</span></td>$salesreplink</tr></table>
	<table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr>";
	if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"affiliate.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"Catalog\"></a></td>";
	else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"affiliate.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"Catalog\"></a></td>";
	echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changeprofile.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".VIEWPROFILE."\"></a></td><td align=\"center\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".CHANGEPASSBTN."\" disabled></td>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"parties.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".PARTIES."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"login.php?logout\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LOGOUT."\"></a></td></tr></table>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"login.php?logout\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LOGOUT."\"></a></td></tr></table>";
/*
	echo "<table align=\"center\" width=\"400\"><tr>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
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
*/
?>

<table class="ashopaffiliatesignupframe">
  <tr><td align="center"> 
      <p><span class="ashopaffiliateheader"><?php echo CHANGEPASS ?></span></p></td>
  </tr>
  <tr align="center"> 
    <td> 
      <table class="ashopaffiliatesignupbox">
        <tr align="center"> 
          <td> 
            <form action="changepassword.php" method="post">
              <table border=0 cellspacing=0 cellpadding=3 width="440">
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo OLDPASS ?>:</span></td>
                  <td width="269"> 
                    <input type="password" name="oldpassword" size=20>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo NEWPASS ?>:</span></td>
                  <td width="269"> 
                    <input type="password" name="newpassword1" size=20>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo CONFIRM ?>:</span></td>
                  <td width="269"> 
                    <input type="password" name="newpassword2" size=20>
                  </td>
                </tr>
			  </table>
              <br>
              <table>
                <tr> 
                  <td colspan=4 align=center> 
                    <p> 
                      <input type="submit" value="<?php echo UPDATE ?>"  name="Submit">
                  </td>
                </tr>
              </table>
            </form>
      </table>
    </td>
  </tr>
</table>

<?php
// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
?>