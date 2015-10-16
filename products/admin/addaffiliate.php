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
include "template.inc.php";
// Get language module...
include "language/$adminlang/affiliates.inc.php";

if ($userid != 1) {
	header("Location: index.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Store updated data...
if ($update) {
	// Avoid duplicate usernames...
	$result = @mysqli_query($db,"SELECT * FROM affiliate WHERE user='$user'");
	if (@mysqli_num_rows($result)) $errormsg = USERNAMEINUSE;
	else {

		// Generate a unique referral code for manual referral...
		$referralcode = substr(strtolower($firstname),0,2).substr(strtolower($lastname),0,3);
		$referralcode .= str_repeat("0",5-strlen($referralcode));
		$refnumber = 1;
		$newreferralcode = $referralcode;
		$referralcodenumber = $referralcode.sprintf("%03d",$refnumber);
		$unique = 0;
		$n = 0;
		$m = ord("a");
		while(!$unique) {
			while(!$unique && $refnumber < 1000) {
				$result = @mysqli_query($db,"SELECT referralcode FROM affiliate WHERE referralcode='$referralcodenumber'");
				if(@mysqli_num_rows($result)) {
					$refnumber++;
					$referralcodenumber = $newreferralcode.sprintf("%03d",$refnumber);
				} else $unique = 1;
			} if(!$unique) {
				$refnumber = 1;
				$newreferralcode = substr_replace($referralcode, chr($m), $n, 1);
				$referralcodenumber = $newreferralcode.sprintf("%03d",$refnumber);
				if($m == ord("z")) {
					$n++;
					$m = ord("a");
				} else $m++;
			}
		}

		// Set current date and time...
		$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

		$sql="INSERT INTO affiliate (user, password, business, firstname, lastname, email, address, state, zip, city, country, url, phone, paypalid, updated, referralcode, commissionlevel, referedby) VALUES ('$user', '$password', '$business', '$firstname', '$lastname', '$email', '$address', '$state', '$zip', '$city', '$country', '$url', '$phone', '$paypalid', '$date', '$referralcode', '$commissionlevel', '$referredby')";
		$result = @mysqli_query($db,"$sql");
		header("Location: affiliateadmin.php"); 
		exit;
	}
}

// Close database...
@mysqli_close($db);


// Show affiliate page in browser...
	if (strpos($header, "title") != 0) {
	    $newheader = substr($header,1,strpos($header, "title")+5);
	    $newheader .= ADDNEWAFFILIATE." - ".substr($header,strpos($header, "title")+6,strlen($header));
    } else {
		$newheader = substr($header,1,strpos($header, "TITLE")+5);
		$newheader .= ADDNEWAFFILIATE." - ".substr($header,strpos($header, "TITLE")+6,strlen($header));
	}

echo "$newheader
<div class=\"heading\">".ADDNEWAFFILIATE."</div><center>";
if ($errormsg) echo "<p align=\"center\" class=\"notconfirm\">$errormsg</p>";
echo "
    <form action=\"addaffiliate.php\" method=\"post\"><input type=\"hidden\" name=\"affiliateid\" value=\"$affiliateid\">
    <table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">

<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".REFERREDBYAFFILIATE.":</font></td>
    <td align=\"left\"><input type=text name=\"referredby\" value=\"$referredby\" size=4></td></tr>

    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".COMMISSIONLEVEL.":</font></td>
    <td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=radio name=\"commissionlevel\" value=\"1\"";
	if ($commissionlevel == "1") echo " checked";
	echo "> ".NORMAL." <input type=radio name=\"commissionlevel\" value=\"2\"";
	if ($commissionlevel == "2") echo " checked";
	echo "> ".UPGRADED."</font></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".USERNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"user\" value=\"$user\" size=15><font size=\"1\" face=\"Arial, Helvetica, sans-serif\"> [".MAXTENCHARS."]</font>
    </td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".PASSWORD.":</font></td>
    <td align=\"left\"><input type=text name=\"password\" value=\"$password\" size=15><font size=\"1\" face=\"Arial, Helvetica, sans-serif\"> [".MAXSEVENCHARS."]</font>
    </td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".BUSINESSNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"business\" value=\"$business\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".FIRSTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"firstname\" value=\"$firstname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".LASTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"lastname\" value=\"$lastname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".EMAIL.":</font></td>
    <td align=\"left\"><input type=text name=\"email\" value=\"$email\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ADDRESS.":</font></td>
    <td align=\"left\"><input type=text name=\"address\" value=\"$address\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CITY.":</font></td>
    <td align=\"left\"><input type=text name=\"city\" value=\"$city\" size=40></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".STATEPROVINCE.":</font></td>
    <td align=\"left\"><input type=text name=\"state\" value=\"$state\" size=40></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ZIP.":</font></td>
    <td align=\"left\"><input type=text name=\"zip\" value=\"$zip\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".COUNTRY.":</font></td>
    <td align=\"left\"><input type=text name=\"country\" value=\"$country\" size=40></td></tr>
	<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PHONE.":</font></td>
    <td align=\"left\"><input type=text name=\"phone\" value=\"$phone\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".URL.":</font></td>
    <td align=\"left\"><input type=\"text\" name=\"url\" value=\"$url\" size=\"40\"></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".PAYPALID.":</font></td>
    <td align=\"left\"><input type=\"text\" name=\"paypalid\" value=\"$paypalid\" size=\"40\"></td></tr>";
	echo "<tr><td></td><td align=\"right\">";
	echo "<input type=\"submit\" value=\"".SUBMIT."\" name=\"update\"></td></tr>
    </table></form>
	</font></center>
	$footer";
?>