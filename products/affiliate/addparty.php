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

include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "../admin/ashopconstants.inc.php";
include "../admin/customers.inc.php";

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/af_parties.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE sessionid='$affiliatesesid'";
$result = @mysqli_query($db, "$sql");

// Store affiliate information in variables...
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$affiliateid = @mysqli_result($result, 0, "affiliateid");

// Get number of unread PMs...
$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND (hasbeenread='' OR hasbeenread='0' OR hasbeenread IS NULL)";
$unreadresult = @mysqli_query($db, "$sql");
$unreadcount = @mysqli_num_rows($unreadresult);

// Check input variables...
if (!empty($Submit_x)) {
	if (empty($customeremail) || !ashop_is_email($customeremail)) $msg = FORGOTEMAIL;
	else if (empty($year) || !is_numeric($year)) $msg = FORGOTYEAR;
	else if (empty($month) || !is_numeric($month)) $msg = FORGOTMONTH;
	else if (empty($day) || !is_numeric($day)) $msg = FORGOTDAY;
	else if (empty($hour) || !is_numeric($hour)) $msg = FORGOTHOUR;
	else if (empty($minute) || !is_numeric($minute)) $msg = FORGOTMINUTE;
	else if ($lang != "sv" && (empty($ampm) || ($ampm != "AM" && $ampm != "PM"))) $msg = FORGOTAMPM;
	else if (empty($location)) $msg = FORGOTLOCATION;
	else {
		$customerresult = @mysqli_query($db, "SELECT customerid, affiliateid FROM customer WHERE email='$customeremail'");
		if (!@mysqli_num_rows($customerresult)) {

			// Generate a unique password...
			function makePassword() {
				$alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
				srand ((double) microtime() * 1000000);
				$pwLength = "7"; // this sets the limit on how long the password is.
				for($i = 1; $i <=$pwLength; $i++) {
					$newPass .= $alphaNum[(rand(0,31))];
				}
				return ($newPass);
			}
			$password = makePassword();

			if (strstr($customername," ")) {
				$customernames = explode(" ",$customername);
				$firstname = $customernames[0];
				$lastname = $customernames[1];
			} else {
				$firstname = $customername;
				$lastname = "";
			}

			$firstname = @mysqli_real_escape_string($db, $firstname);
			$lastname = @mysqli_real_escape_string($db, $lastname);
			$email = @mysqli_real_escape_string($db, $customeremail);
			// Encrypt password if encryption key is available...
			if (!empty($customerencryptionkey) && !empty($password)) $customerpassword = ashop_encrypt($password, $customerencryptionkey);
			else $customerpassword = $password;

			$sql = "INSERT INTO customer (username, firstname, lastname, email, password, allowemail, affiliateid) VALUES ('$email', '$firstname', '$lastname', '$email', '$customerpassword', '1', '$affiliateid')";
			$result = @mysqli_query($db, "$sql");
			$customerid = @mysqli_insert_id($db);
			if (!@mysqli_num_rows($checkshippingresult)) $sql = "INSERT INTO shipping (shippingfirstname, shippinglastname, customerid) VALUES ('$firstname', '$lastname', '$customerid')";

			// Send message with password to customer...
			if (file_exists("$ashoppath/templates/messages/signupmessage-$lang.html")) $messagefile = "$ashoppath/templates/messages/signupmessage-$lang.html";
			else $messagefile = "$ashoppath/templates/messages/signupmessage.html";
			$fp = @fopen("$messagefile","r");
			if ($fp) {
				while (!feof ($fp)) $messagetemplate .= fgets($fp, 4096);
				fclose($fp);
			} else {
				$messagetemplate="<html><head><title>".THANKYOUFORJOINING." $ashopname ".CUSTOMERPROFILE."</title></head><body><font face=\"$font\"><p>".THANKYOUFORJOINING." $ashopname ".CUSTOMERPROFILE."</p><p>".YOURUSERNAMEIS." <b>$email</b>".ANDYOURPASSWORD." <b>$password</b></p><p>".LOGINANDSTART." <b><a href=\"$ashopurl/login.php\">$ashopurl/login.php</a></b></p></font></body></html>";
			}
			$message = str_replace("%ashopname%",$ashopname,$messagetemplate);
			$message = str_replace("%username%",$email,$message);
			$message = str_replace("%firstname%",$firstname,$message);
			$message = str_replace("%lastname%",$lastname,$message);
			$message = str_replace("%email%",$email,$message);
			$message = str_replace("%password%",$password,$message);

			// Get current date and time...
			$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
			$message = str_replace("%date%",$date,$message);

			$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$email","$ashopname - ".CUSTOMERPROFILE,"$message","$headers");


		} else {
			$customerid = @mysqli_result($customerresult,0,"customerid");
			$customeraffiliateid = @mysqli_result($customerresult,0,"affiliateid");
			if (!empty($customeraffiliateid) && $customeraffiliateid != $affiliateid) $msg = NOTYOURCUSTOMER;
			else {
				if (empty($customeraffiliateid)) @mysqli_query($db, "UPDATE customer SET affiliateid='$affiliateid' WHERE customerid='$customerid'");
				$date = "$year-$month-$day $hour:$minute $ampm";
			}
		}

		@mysqli_query($db, "INSERT INTO party (customerid, affiliateid, description, location, date) VALUES ('$customerid', '$affiliateid', '$description', '$location', '$date')");

		header("Location: parties.php?msg=partyadded");
		exit;
	}
}

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
	<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"leads.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LEADS."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"inbox.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
} else {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"inbox.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
}
$thisyear = date("Y",time());
$tenyearsfromnow = $thisyear+10;
echo "
	</tr></table>
	<br /><span class=\"ashopaffiliateheader\">".ADDAPARTY."</span><br /><br />";
if (!empty($msg)) echo "<span class=\"ashopaffiliatetext1\">$msg</span><br /><br />";
echo "
	<form action=\"addparty.php\" method=\"post\" name=\"partyform\">
	<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" width=\"540\">
	<tr> 
	  <td align=\"right\"><span class=\"ashopcustomertext3\">".NAMEOFHOST.":</span></td>
	  <td align=\"left\"> 
	     <input type=\"text\" size=\"48\" name=\"customername\" />
	  </td>
	</tr>
	<tr> 
	  <td align=\"right\"><span class=\"ashopcustomertext3\">".EMAILOFHOST.":</span></td>
	  <td align=\"left\"> 
	     <input type=\"text\" size=\"48\" name=\"customeremail\" />
	  </td>
	</tr>
	<tr> 
	  <td align=\"right\" width=\"40%\"><span class=\"ashopcustomertext3\">".DATEANDTIME.":</span></td>
	  <td width=\"70%\" align=\"left\"> 
	  <select name=\"year\">";
	  for ($y = $thisyear; $y < $tenyearsfromnow; $y++) {
		  echo "<option value=\"$y\">$y</option>\n";
	  }
	  echo "
	  </select>
	  <select name=\"month\"><option value=\"01\" selected>".JAN."</option><option value=\"02\">".FEB."</option><option value=\"03\">".MAR."</option><option value=\"04\">".APR."</option><option value=\"05\">".MAY."</option><option value=\"06\">".JUN."</option><option value=\"07\">".JUL."</option><option value=\"08\">".AUG."</option><option value=\"09\">".SEP."</option><option value=\"10\">".OCT."</option><option value=\"11\">".NOV."</option><option value=\"12\">".DEC."</option></select>
	  <select name=\"day\"><option value=\"01\" selected>1</option>";
	  for ($i = 2; $i < 32; $i++) {
		  echo "<option value=\"";
		  if ($i < 10) echo "0";
		  echo "$i\">$i</option>";
	  }
	  echo "</select> :
	  <select name=\"hour\">";
	  if ($lang == "sv") {
		  $minhour = 0;
		  $maxhour = 23;
	  } else {
		  $minhour = 1;
		  $maxhour = 12;
	  }
	  for ($h = $minhour; $h <= $maxhour; $h++) {
		  if ($h < 10) $thishour = "0".$h;
		  else $thishour = $h;
		  echo "<option value=\"$thishour\">$thishour</option>\n";
	  }
	  echo "</select>
	  <select name=\"minute\">";
	  for ($m = 0; $m <= 59; $m++) {
		  if ($m < 10) $thisminute = "0".$m;
		  else $thisminute = $m;
		  echo "<option value=\"$thisminute\">$thisminute</option>\n";
	  }
	  echo "</select>";
	  if ($lang != "sv") echo "
	  <select name=\"ampm\"><option value=\"AM\">AM</option><option value=\"PM\">PM</option></select>";
	  echo "
	  </td>
	  </tr>
	  <tr> 
	  <td align=\"right\"><span class=\"ashopcustomertext3\">".LOCATION.":</span></td>
	  <td align=\"left\"> 
	     <textarea cols=\"36\" rows=\"4\" name=\"location\" /></textarea>
	  </td>
	  </tr>
	  <tr> 
	    <td align=\"right\"><span class=\"ashopcustomertext3\">".COMMENTS.":</span></td>
		<td align=\"left\"> 
		  <textarea cols=\"36\" rows=\"4\" name=\"description\" /></textarea>
		</td>
	  </tr>
	  <tr> 
	    <td colspan=\"2\" align=\"right\"><br /><input type=\"image\" src=\"../{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" style=\"border: none;\" alt=\"".SUBMIT."\" name=\"Submit\" /> &nbsp;</td>
	  </tr></table></form>";

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");

// Close database...
@mysqli_close($db);
?>