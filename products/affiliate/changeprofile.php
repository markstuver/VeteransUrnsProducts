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
include "../language/$lang/af_changeprofile.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE sessionid='$affiliatesesid'";
$result = @mysqli_query($db, "$sql");

// Get the correct password for this affiliate...
$correctpasswd = @mysqli_result($result, 0, "password");

// Set current date and time...
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Update profile...
if($Submit) {
	$sql = "UPDATE affiliate SET business='$business', firstname='$firstname', lastname='$lastname', email='$email', address='$address', state='$state', zip='$zip', city='$city', url='$url', phone='$phone', country='$country', paypalid='$paypalid', updated='$date' WHERE sessionid='$affiliatesesid'";
	@mysqli_query($db, "$sql");
}

// Store affiliate information in variables...
$business = @mysqli_result($result, 0, "business");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$email = @mysqli_result($result, 0, "email");
$address = @mysqli_result($result, 0, "address");
$state = @mysqli_result($result, 0, "state");
$zip = @mysqli_result($result, 0, "zip");
$city = @mysqli_result($result, 0, "city");
$url = @mysqli_result($result, 0, "url");
$phone = @mysqli_result($result, 0, "phone");
$country = @mysqli_result($result, 0, "country");
$paypalid = @mysqli_result($result, 0, "paypalid");
$affiliateid = @mysqli_result($result, 0, "affiliateid");

// Get custom fields...
$customfields = "";
$customfieldsresult = @mysqli_query($db, "SELECT * FROM affiliatetags ORDER BY fieldname ASC");
if (@mysqli_num_rows($customfieldsresult)) {
	while ($customfieldrow = @mysqli_fetch_array($customfieldsresult)) {
		$tagid = $customfieldrow["affiliatetagid"];
		$fieldname = $customfieldrow["fieldname"];
		$htmlfieldname = str_replace(" ","__",$fieldname);
		$rows = $customfieldrow["rows"];
		$affinfo = "";
		if($Submit) {
			$affinfo = $_POST["$htmlfieldname"];
			$customfieldinforesult = @mysqli_query($db, "SELECT * FROM affiliatetaginfo WHERE affiliateid='$affiliateid' AND affiliatetagid='$tagid'");
			if (@mysqli_num_rows($customfieldinforesult)) @mysqli_query($db, "UPDATE affiliatetaginfo SET value='$affinfo' WHERE affiliateid='$affiliateid' AND affiliatetagid='$tagid'");
			else @mysqli_query($db, "INSERT INTO affiliatetaginfo (affiliateid,affiliatetagid,value) VALUES ('$affiliateid','$tagid','$affinfo')");
		} else {
			$customfieldinforesult = @mysqli_query($db, "SELECT * FROM affiliatetaginfo WHERE affiliateid='$affiliateid' AND affiliatetagid='$tagid'");
			if (@mysqli_num_rows($customfieldinforesult)) $affinfo = @mysqli_result($customfieldinforesult,0,"value");
		}
		if ($rows == "1") $customfields .= "<tr><td align=\"right\"><span class=\"ashopaffiliatetext3\">$fieldname:</span></td>
		<td><input type=text name=\"$htmlfieldname\" value=\"$affinfo\" size=40></td></tr>";
		else $customfields .= "<tr><td align=\"right\"><span class=\"ashopaffiliatetext3\">$fieldname:</span></td>
		<td><textarea name=\"$htmlfieldname\" cols=\"30\" rows=\"$rows\">$affinfo</textarea></td></tr>";
	}
}

if ($Submit) {
	header("Location: affiliate.php");
	exit;
}

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
echo "<td align=\"center\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".VIEWPROFILE."\" disabled></td><td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changepassword.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".CHANGEPASS."\"></a></td>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"parties.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".PARTIES."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"login.php?logout\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"login.php?logout\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
echo "</tr></table>";
/*
echo "
	<table align=\"center\" width=\"400\"><tr>";
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
      <p><span class="ashopaffiliateheader"><?php echo CHANGEBELOW ?></span></p></td>
  </tr>
  <tr align="center"> 
    <td> 
      <table class="ashopaffiliatesignupbox">
        <tr align="center"> 
          <td> 
            <form action="changeprofile.php" method="post" name="orderform">
              <table border=0 cellspacing=0 cellpadding=3 width="440">
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo BUSINESS ?>:</span></td>
                  <td width="269"> 
                    <input type=text name="business" value="<?php echo $business ?>" size=40>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo FIRSTNAME ?>:</span></td>
                  <td width="269"> 
                    <input type=text name="firstname" value="<?php echo $firstname ?>" size=40>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo LASTNAME ?>:</span></td>
                  <td width="269"> 
                    <input type=text name="lastname" value="<?php echo $lastname ?>" size=40>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo EMAIL ?>:</span></td>
                  <td width="269"> 
                    <input type=text name="email" value="<?php echo $email ?>" size=40>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo ADDRESS ?>:</span></td>
                  <td width="269"> 
                    <input type=text name="address" value="<?php echo $address ?>" size=40>
                  </td>
                </tr>
                <tr>
                  <td align="right" height="25" width="159"><span class="ashopaffiliatetext3"><?php echo CITY ?>:</span></td>
                  <td width="269" height="25"> 
                    <input type=text name="city" value="<?php echo $city ?>" size=40>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo STATE ?>:</span></td>
                  <td width="269"> 
                    <input type=text name="state" value="<?php echo $state ?>" size=40>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo ZIP ?>:</span></td>
                  <td width="269"> 
                    <input type=text name="zip" value="<?php echo $zip ?>" size=40>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo COUNTRY ?>:</span></td>
                  <td width="269"> 
                    <input type=text name="country" value="<?php echo $country ?>" size=40>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo PHONE ?>:</span></td>
                  <td width="269"> 
                    <input type=text name="phone" value="<?php echo $phone ?>" size=40>
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo URL ?>:</span></td>
                  <td width="269" valign="top"> 
                    <input type="text" name="url" value="<?php echo $url ?>" size="40">
                  </td>
                </tr>
                <tr> 
                  <td align="right" width="159"><span class="ashopaffiliatetext3"><?php echo PAYPAL ?>:</span></td>
                  <td width="269" valign="top"> 
                    <input type="text" name="paypalid" value="<?php echo $paypalid ?>" size="40">
                  </td>
                </tr>
				<tr>
				  <td></td><td><span class="ashopaffiliatenotice"><?php echo OPTIONAL ?></span></td>
				</tr>
				<?php if ($customfields) echo $customfields; ?>
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