<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "admin/checklicense.inc.php";
include "admin/ashopconstants.inc.php";
include "admin/customers.inc.php";

if (empty($enablepartyplanner)) {
	header("Location: $ashopurl");
	exit;
}

// Check for GD...
$checkgd = TRUE;
include "includes/captcha.inc.php";

// If GD is available generate random code for security check...
if ($gdversion == 2 && empty($_COOKIE["customersessionid"]) && empty($_COOKIE["wssessionid"])) {
	$activatesecuritycheck = TRUE;
	// Generate new random code...
	mt_srand ((double)microtime()*1000000);
	$maxrandom = 1000000;
	$randomcode = mt_rand(0, $maxrandom);
} else $activatesecuritycheck = FALSE;

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/hostparty.inc.php";

// Read wholesale session cookie if this is a wholesale customer...
if (!empty($_COOKIE["wssessionid"])) $_COOKIE["customersessionid"] = $_COOKIE["wssessionid"];

// Validate variables...
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get customer information from database...
if (!empty($_COOKIE["customersessionid"])) {
	$sql="SELECT * FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'";
	$result = @mysqli_query($db, "$sql");

	$customerid = @mysqli_result($result, 0, "customerid");
	$affiliateid = @mysqli_result($result, 0, "affiliateid");
} else if (!empty($_COOKIE["wssessionid"])) {
	$sql="SELECT * FROM customer WHERE sessionid='{$_COOKIE["wssessionid"]}'";
	$result = @mysqli_query($db, "$sql");

	$customerid = @mysqli_result($result, 0, "customerid");
	$affiliateid = @mysqli_result($result, 0, "affiliateid");
} else if (!empty($email)) {
	$sql="SELECT * FROM customer WHERE email='$email'";
	$result = @mysqli_query($db, "$sql");
	$affiliateid = $affiliate;
	if (@mysqli_num_rows($result)) {
		header("Location: login.php?redirect=hostparty");
		exit;
	}
} else {

	$affiliateid = $affiliate;


}
if (empty($affiliateid) && !empty($affiliate)) $affiliateid = $affiliate;

// Make sure the customer is referred by an affiliate...
if (empty($affiliateid) || !is_numeric($affiliateid)) {
	header("Location: affiliate.php?redirect=hostparty.php");
	exit;
}

// Generate date string...
$date = "";
if ($lang == "sv") {
	if (isset($year) && is_numeric($year) && isset($month) && is_numeric($month) && isset($day) && is_numeric($day) && isset($hour) && is_numeric($hour) && isset($minute) && is_numeric($minute)) {
		$date = "$year-$month-$day $hour:$minute";
	}
} else {
	if (isset($year) && is_numeric($year) && isset($month) && is_numeric($month) && isset($day) && is_numeric($day) && isset($hour) && is_numeric($hour) && isset($minute) && is_numeric($minute) && !empty($ampm) && ($ampm == "AM" || $ampm == "PM")) {
		$date = "$year-$month-$day $hour:$minute $ampm";
	}
}

// Store updated data...
if (($Submit_x || $Submit) && !empty($date)) {

	// Check if the right security check code has been provided...
	if ($activatesecuritycheck && (!$securitycheck || $securitycheck != generatecode($random))) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/signup-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/signup.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
		<p><span class=\"ashopmessage\">".INCORRECTSECURITYCODE."</span></p>
		<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/signup-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/signup.html");
		exit;
	}

	// Register new customer if the host does not exist in the customers table...
	if (empty($customerid)) {

		// Verify the customer's IP number with minFraud...
		if (!empty($minfraudkey) || !empty($minfraudgeoipkey)) {
			$ipnumber = $_SERVER["REMOTE_ADDR"];
			if (ashop_minfraudproxycheck($ipnumber) != "0.00") {
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/signup-$lang.html");
				else ashop_showtemplateheader("$ashoppath$templatepath/signup.html");
				echo "<table class=\"ashopmessagetable\">
				<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".SORRY."</span></p>
				<p><span class=\"ashopmessage\">".PROXYDETECTED."</span></p>
				<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/signup-$lang.html");
				else ashop_showtemplatefooter("$ashoppath$templatepath/signup.html");
				exit;
			}
		}

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

		$firstname = @mysqli_real_escape_string($db, $firstname);
		$lastname = @mysqli_real_escape_string($db, $lastname);
		$email = @mysqli_real_escape_string($db, $email);
		// Encrypt password if encryption key is available...
		if (!empty($customerencryptionkey) && !empty($password)) $customerpassword = ashop_encrypt($password, $customerencryptionkey);
		else $customerpassword = $password;
		$date = date("Y/m/d H:i:s");
		$hash = md5($date.$username.$password."ashopisgreat");

		$sql = "INSERT INTO customer (username, firstname, lastname, email, password, allowemail, affiliateid, sessionid, activity, ip) VALUES ('$email', '$firstname', '$lastname', '$email', '$customerpassword', '1', '$affiliateid', '$hash', '$date', '{$_SERVER["REMOTE_ADDR"]}')";
		$result = @mysqli_query($db, "$sql");
		$customerid = @mysqli_insert_id($db);
		if (!@mysqli_num_rows($checkshippingresult)) $sql = "INSERT INTO shipping (shippingfirstname, shippinglastname, customerid) VALUES ('$firstname', '$lastname', '$customerid')";

		// Set session cookie to automatically login the new customer...
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		SetCookie("customersessionid", $hash);

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
	}

	// Store the party...
	if (!empty($partyid) && is_numeric($partyid)) {

		@mysqli_query($db, "UPDATE party SET description='$description', location='$location', date='$date' WHERE partyid='$partyid'");

	} else {

		@mysqli_query($db, "INSERT INTO party (customerid, affiliateid, description, location, date) VALUES ('$customerid', '$affiliateid', '$description', '$location', '$date')");

	}

	// Redirect the customer to their list of parties...
	header("Location: customerparties.php");
	exit;
}

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/partyplanner.html")) $templatepath = "/members/files/$ashopuser";

// Get party details for editing...
$partyid = "";
$partylocation = "";
$partydescription = "";
$partyyear = "";
$partymonth = "";
$partyday = "";
$partyhour = "";
$partyminute = "";
$partyampm = "";
if (!empty($edit) && is_numeric($edit) && !empty($customerid) && is_numeric($customerid)) {
	$partyresult = @mysqli_query($db, "SELECT * FROM party WHERE partyid='$edit' AND customerid='$customerid' AND (ended!='1' OR ended IS NULL)");
	if (@mysqli_num_rows($partyresult)) {
		$partyrow = @mysqli_fetch_array($partyresult);
		$partyid = $partyrow["partyid"];
		$partylocation = $partyrow["location"];
		$partydescription = $partyrow["description"];
		$partydate = $partyrow["date"];
		$partydatearray = explode(" ",$partydate);
		$partydate = $partydatearray[0];
		$partytime = $partydatearray[1];
		$partyampm = $partydatearray[2];
		$partydatearray = explode("-",$partydate);
		$partyyear = $partydatearray[0];
		$partymonth = $partydatearray[1];
		$partyday = $partydatearray[2];
		$partytimearray = explode(":",$partytime);
		$partyhour = $partytimearray[0];
		$partyminute = $partytimearray[1];
	}
}

// Check if a mobile device is being used...
$device = ashop_mobile();

// Show header using template partyplanner.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/partyplanner-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/partyplanner-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/partyplanner.html");

$thisyear = date("Y",time());
$tenyearsfromnow = $thisyear+10;

echo "
<br /><table class=\"ashopsignupframe\">
  <tr><td align=\"center\"> 

      <p><span class=\"ashopsignupheader\">".HOSTAPARTY."</span></p>";
if (!empty($partyid)) echo "<p align=\"left\"><span class=\"ashopcustomertext2\">".EDITPARTYMESSAGE."</span></p>";
else echo "<p align=\"left\"><span class=\"ashopcustomertext2\">".HOSTAPARTYMESSAGE."</span></p>";
echo "
      </td>
  </tr>
  <tr align=\"center\"> 
    <td>";
echo "
      <table class=\"ashopsignupbox\">
        <tr align=\"center\"> 
          <td>
            <form action=\"hostparty.php\" method=\"post\" name=\"partyform\"";
if ($device == "mobile") echo " data-ajax=\"false\"";			
			echo ">";
if ($device == "mobile") {
	if (empty($customerid)) {
		echo "
			<div data-role=\"fieldcontain\"><label for=\"firstname\">".FIRSTNAME.":</label><input type=\"text\" name=\"firstname\" id=\"firstname\" size=\"30\" /></div>
			<div data-role=\"fieldcontain\"><label for=\"lastname\">".LASTNAME.":</label><input type=\"text\" name=\"lastname\" id=\"lastname\" size=\"30\" /></div>
			<div data-role=\"fieldcontain\"><label for=\"email\">".EMAIL.":</label><input type=\"text\" name=\"email\" id=\"email\" size=\"30\" /></div>";
	}
	echo "
			<div data-role=\"fieldcontain\">
			<fieldset data-role=\"controlgroup\" data-type=\"horizontal\"><legend>".DATEANDTIME.":</legend>
				    <select name=\"year\">";
					for ($y = $thisyear; $y < $tenyearsfromnow; $y++) {
						echo "<option value=\"$y\"";
						if ($partyyear == $y) echo " selected";
						echo ">$y</option>\n";
					}
					echo "
					</select>
					<select name=\"month\"><option value=\"01\""; if ($partymonth == "01") echo " selected"; echo ">".JAN."</option><option value=\"02\""; if ($partymonth == "02") echo " selected"; echo ">".FEB."</option><option value=\"03\""; if ($partymonth == "03") echo " selected"; echo ">".MAR."</option><option value=\"04\""; if ($partymonth == "04") echo " selected"; echo ">".APR."</option><option value=\"05\""; if ($partymonth == "05") echo " selected"; echo ">".MAY."</option><option value=\"06\""; if ($partymonth == "06") echo " selected"; echo ">".JUN."</option><option value=\"07\""; if ($partymonth == "07") echo " selected"; echo ">".JUL."</option><option value=\"08\""; if ($partymonth == "08") echo " selected"; echo ">".AUG."</option><option value=\"09\""; if ($partymonth == "09") echo " selected"; echo ">".SEP."</option><option value=\"10\""; if ($partymonth == "10") echo " selected"; echo ">".OCT."</option><option value=\"11\""; if ($partymonth == "11") echo " selected"; echo ">".NOV."</option><option value=\"12\""; if ($partymonth == "12") echo " selected"; echo ">".DEC."</option></select>
					<select name=\"day\">";
					for ($i = 1; $i < 32; $i++) {
						if ($day < 10) $day = "0".$i;
						else $day = $i;
						echo "<option value=\"$day\"";
						if ($day == $partyday) echo " selected";
						echo ">$i</option>";
					}					echo "</select>";
					if ($lang != "sv") {
						echo "
						<select name=\"ampm\"><option value=\"AM\""; if ($partyampm == "AM") echo " selected"; echo ">AM</option><option value=\"PM\""; if ($partyampm == "AM") echo " selected"; echo ">PM</option></select>";
					}
					echo "
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
						echo "<option value=\"$thishour\"";
						if ($thishour == $partyhour) echo " selected";
						echo ">$thishour</option>\n";
					}
					echo "</select>
					<select name=\"minute\">";
					for ($m = 0; $m <= 59; $m++) {
						if ($m < 10) $thisminute = "0".$m;
						else $thisminute = $m;
						echo "<option value=\"$thisminute\"";
						if ($thisminute == $partyminute) echo " selected";
						echo ">$thisminute</option>\n";
					}
					echo "</select>
					</fieldset>
			</div>
			<div data-role=\"fieldcontain\"><label for=\"lastname\">".LOCATION.":</label><textarea cols=\"36\" rows=\"4\" name=\"location\" />$partylocation</textarea></div>
			<div data-role=\"fieldcontain\"><label for=\"email\">".COMMENTS.":</label><textarea cols=\"36\" rows=\"4\" name=\"description\" />$partydescription</textarea></div>
";
} else {
	echo "
              <table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" width=\"540\">
";
if (empty($customerid)) {
	echo "
                <tr> 
                  <td align=\"right\" width=\"30%\"><span class=\"ashopcustomertext3\">".FIRSTNAME.":</span></td>
                  <td width=\"70%\" align=\"left\"> 
                    <input type=\"text\" name=\"firstname\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopcustomertext3\">".LASTNAME.":</span></td>
                  <td align=\"left\"> 
                    <input type=\"text\" name=\"lastname\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopcustomertext3\">".EMAIL.":</span></td>
                  <td align=\"left\"> 
                    <input type=\"text\" name=\"email\" size=\"30\" />
                  </td>
                </tr>";
}
echo "
                <tr> 
                  <td align=\"right\" width=\"40%\"><span class=\"ashopcustomertext3\">".DATEANDTIME."</span></td>
                  <td width=\"70%\" align=\"left\"> 
				    <select name=\"year\">";
					for ($y = $thisyear; $y < $tenyearsfromnow; $y++) {
						echo "<option value=\"$y\"";
						if ($partyyear == $y) echo " selected";
						echo ">$y</option>\n";
					}
					echo "
					</select>
					<select name=\"month\"><option value=\"01\""; if ($partymonth == "01") echo " selected"; echo ">".JAN."</option><option value=\"02\""; if ($partymonth == "02") echo " selected"; echo ">".FEB."</option><option value=\"03\""; if ($partymonth == "03") echo " selected"; echo ">".MAR."</option><option value=\"04\""; if ($partymonth == "04") echo " selected"; echo ">".APR."</option><option value=\"05\""; if ($partymonth == "05") echo " selected"; echo ">".MAY."</option><option value=\"06\""; if ($partymonth == "06") echo " selected"; echo ">".JUN."</option><option value=\"07\""; if ($partymonth == "07") echo " selected"; echo ">".JUL."</option><option value=\"08\""; if ($partymonth == "08") echo " selected"; echo ">".AUG."</option><option value=\"09\""; if ($partymonth == "09") echo " selected"; echo ">".SEP."</option><option value=\"10\""; if ($partymonth == "10") echo " selected"; echo ">".OCT."</option><option value=\"11\""; if ($partymonth == "11") echo " selected"; echo ">".NOV."</option><option value=\"12\""; if ($partymonth == "12") echo " selected"; echo ">".DEC."</option></select>
					<select name=\"day\">";
					for ($i = 1; $i < 32; $i++) {
						if ($day < 10) $day = "0".$i;
						else $day = $i;
						echo "<option value=\"$day\"";
						if ($day == $partyday) echo " selected";
						echo ">$i</option>";
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
						echo "<option value=\"$thishour\"";
						if ($thishour == $partyhour) echo " selected";
						echo ">$thishour</option>\n";
					}
					echo "</select>
					<select name=\"minute\">";
					for ($m = 0; $m <= 59; $m++) {
						if ($m < 10) $thisminute = "0".$m;
						else $thisminute = $m;
						echo "<option value=\"$thisminute\"";
						if ($thisminute == $partyminute) echo " selected";
						echo ">$thisminute</option>\n";
					}
					echo "</select>";
					if ($lang != "sv") echo "
					<select name=\"ampm\"><option value=\"AM\">AM</option><option value=\"PM\">PM</option></select>";
					echo "
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopcustomertext3\">".LOCATION."</span></td>
                  <td align=\"left\"> 
                    <textarea cols=\"36\" rows=\"4\" name=\"location\" />$partylocation</textarea>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopcustomertext3\">".COMMENTS.":</span></td>
                  <td align=\"left\"> 
                    <textarea cols=\"36\" rows=\"4\" name=\"description\" />$partydescription</textarea>
                  </td>
                </tr>";
}
if ($activatesecuritycheck) {
	if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"securitycode\">".SECURITYCODE.":</label> <img src=\"includes/captcha.inc.php?action=generatecode&amp;random=$randomcode\" border=\"1\" id=\"securitycode\" alt=\"Security Code\" title=\"Security Code\" /></div>
	<div data-role=\"fieldcontain\"><label for=\"securitycheck\">".TYPESECURITYCODE.":</label><input type=\"text\" name=\"securitycheck\" id=\"securitycheck\" size=\"10\" /><input type=\"hidden\" name=\"random\" value=\"$randomcode\" /></div>";
	else echo "<tr><td align=\"right\"><span class=\"ashopcustomertext3\">".SECURITYCODE.":</span></td><td valign=\"top\" align=\"left\"><img src=\"includes/captcha.inc.php?action=generatecode&amp;random=$randomcode\" border=\"1\" alt=\"Security Code\" title=\"Security Code\" /></td></tr><tr><td align=\"right\"><span class=\"ashopcustomertext3\">".TYPESECURITYCODE.":</span></td><td valign=\"top\" align=\"left\"><input type=\"text\" name=\"securitycheck\" size=\"10\" /><input type=\"hidden\" name=\"random\" value=\"$randomcode\" /></td></tr>";
}
if (!empty($partyid)) echo "<input type=\"hidden\" name=\"partyid\" value=\"$partyid\" />";

if ($device == "mobile") echo "<input type=\"submit\" name=\"Submit\" data-role=\"button\" value=\"".SUBMIT."\" />";
else echo "<tr> 
                  <td colspan=\"2\" align=\"center\"><br /><input type=\"image\" src=\"{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" style=\"border: none;\" alt=\"".SUBMIT."\" name=\"Submit\" /></td>
                </tr>
              </table>
";
if (!empty($shop) && $shop > 1) echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\" />";
echo "
            </form>
			</td>
			</tr>
      </table>
    </td>
  </tr>
</table>";

// Show footer using template partyplanner.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/partyplanner-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner.html");
?>