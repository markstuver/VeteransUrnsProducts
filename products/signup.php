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

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";
include "admin/customers.inc.php";

// Validate confirmation code...
if (!empty($_GET["cid"]) && !ashop_is_md5($_GET["cid"])) $_GET["cid"] = "";

// Check for GD...
$checkgd = TRUE;
include "includes/captcha.inc.php";

// If GD is available generate random code for security check...
if ($gdversion == 2) $activatesecuritycheck = TRUE;
else $activatesecuritycheck = FALSE;

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/signup.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check Facebook login, if activated...
$facebookactivated = FALSE;
$verified = FALSE;
$facebookid = "";
if (!empty($facebookappid) && !empty($facebooksecret)) {
	include "includes/facebook/facebook.php";
	$facebook = new Facebook(array('appId'  => $facebookappid,'secret' => $facebooksecret));
	$accesstoken = $facebook->getAccessToken();
	$facebookactivated = TRUE;
	$facebookuser = get_facebook_user($accesstoken);
	if (!empty($facebookuser->email)) {
		$firstname = $facebookuser->first_name;
		$lastname = $facebookuser->last_name;
		$email = $facebookuser->email;
		$verified = $facebookuser->verified;
		if ($verified == "1") $verified = TRUE;
		else $verified = FALSE;
		$facebookid = $facebookuser->id;
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
		if ($firstname && $email) $activatesecuritycheck = FALSE;
	}
}

// Get pending customer information to confirm email...
$confirmed = FALSE;
if ($_GET["cid"]) {
	$pendingcustomerresult = @mysqli_query($db, "SELECT * FROM pendingcustomer WHERE confirmationcode='{$_GET["cid"]}'");
	if (@mysqli_num_rows($pendingcustomerresult)) {
		$pendingcustomerrow = @mysqli_fetch_array($pendingcustomerresult);
		$username = $pendingcustomerrow["user"];
		$firstname = $pendingcustomerrow["firstname"];
		$lastname = $pendingcustomerrow["lastname"];
		$email = $pendingcustomerrow["email"];
		$password = $pendingcustomerrow["password"];
		$allowemail = $pendingcustomerrow["allowemail"];
		@mysqli_query($db, "DELETE FROM pendingcustomer WHERE confirmationcode='{$_GET["cid"]}'");
		$confirmed = TRUE;
		$activatesecuritycheck = FALSE;
	} else {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/signup-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/signup.html");
		echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
		<p><span class=\"ashopmessage\">".CIDDOESNOTEXIST."</span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/signup-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/signup.html");
		exit;
	}
}

// Use verified parameter at Facebook as confirmation...
if ($facebookactivated && $verified) $confirmed = TRUE;

// Check for spam injection...
$firstname = ashop_mailsafe($firstname);
$firstname = ashop_cleanfield($firstname);
if (strlen($firstname) < 2) $firstname = "";
$lastname = ashop_mailsafe($lastname);
$lastname = ashop_cleanfield($lastname);
if (strlen($lastname) < 2) $lastname = "";
$email = ashop_mailsafe($email);
$email = ashop_cleanfield($email);
$email = str_replace(" ","+",$email);
if (strlen($email) < 2) $email = "";
if (!empty($firstname) && !empty($lastname)) $fullname = $firstname." ".$lastname;
else if (!empty($firstname)) $fullname = $firstname;
else $fullname = $lastname;
// Check if the customer's email or IP number is banned...
$ipnumber = $_SERVER["REMOTE_ADDR"];
$bannedcheck = @mysqli_query($db, "SELECT * FROM customerblacklist WHERE blacklistitem='$email' OR WHERE blacklistitem='$ipnumber'");
$emaildomain = substr($email,strpos($email,"@")+1);
$domainbannedcheck = @mysqli_query($db, "SELECT * FROM customerblacklist WHERE blacklistitem='$emaildomain'");
if (@mysqli_num_rows($bannedcheck) || @mysqli_num_rows($domainbannedcheck)) {
	$firstname = "";
	$lastname = "";
	$email = "";
}
$password = ashop_mailsafe($password);
$password = ashop_cleanfield($password);
if (strlen($password) < 2) $password = "";
$username = $email;
if ($allowemail == "on" || $allowemail == 1) $allowemail = 1;
else $allowemail = 0;
if ($confirmed) $confirmpassword = $password;

// Check if all fields were filled in...
if ($firstname=="" || $lastname=="" || $email=="" || $password=="" || $confirmpassword=="") {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/signup-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/signup.html");
	echo "<table class=\"ashopmessagetable\">
	<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
	<p><span class=\"ashopmessage\">".YOUFORGOT."</span></p>
	<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/signup-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/signup.html");
	exit;
}

// Check if the password contains forbidden characters...
if (strstr($password, chr(32)) || strstr($password, chr(33)) || strstr($password, chr(44)) || strstr($password, chr(46)) || strstr($password, chr(63)) || (strlen($password) > 10)) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/signup-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/signup.html");
	echo "<table class=\"ashopmessagetable\">
	<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
	<p><span class=\"ashopmessage\">".THEPASSWORD."</span></p>
	<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/signup-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/signup.html");
	exit;
}

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

// Check if the customer already exists...
$sql="SELECT * FROM customer WHERE email='$email' AND password IS NOT NULL AND password!=''";
$result = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($result) != 0 && (empty($facebookid) || !$verified)) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/signup-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/signup.html");
	echo "<table class=\"ashopmessagetable\">
	<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".SORRY."</span></p>
	<p><span class=\"ashopmessage\">".ALREADYINUSE."</span></p>
	<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/signup-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/signup.html");
	exit;
}

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

// Set confirmation code if email confirmation is on...
if ($customerconfirm && !$confirmed) {
	$confirmationcode = md5($password);
	if (!empty($email)) @mysqli_query($db, "DELETE FROM pendingcustomer WHERE email='$email'");
	if (!empty($affiliate) && !is_numeric($affiliate)) $affiliate = "";
	$sql = "INSERT INTO pendingcustomer (username, firstname, lastname, email, password, allowemail, confirmationcode, affiliateid) VALUES ('$email', '$firstname', '$lastname', '$email', '$password', '$allowemail', '$confirmationcode', '$affiliate')";
	$result = @mysqli_query($db, "$sql");

	$message = "<html><head><title>".THANKYOUFORREGISTERING." $ashopname ".CUSTOMERPROFILE."</title></head><body><font face=\"$font\"><p>".YOUARERECEIVING." $ashopname.</p>
	<p>".PLEASEVERIFY." <a href=\"$ashopurl/signup.php?cid=$confirmationcode\">$ashopurl/signup.php?cid=$confirmationcode</a></p></font></body></html>";

	$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
	@ashop_mail("$email",CUSTOMERREGISTRATION." ".un_html($ashopname),"$message","$headers");
	@mysqli_close($db);

	// Allow external programming...
	if (file_exists("$ashoppath/api/customersignup.inc.php")) include "api/customersignup.inc.php";

	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/signup-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/signup.html");
	echo "<table class=\"ashopmessagetable\">
	<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".THANKYOUFORREGISTERING." $ashopname!</span></p>
	<p><span class=\"ashopmessage\">".CHECKMAIL."</span></p>
	</td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/signup-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/signup.html");
	
	exit;
}

// Store customer data...
$firstname = @mysqli_real_escape_string($db, $firstname);
$lastname = @mysqli_real_escape_string($db, $lastname);
$email = @mysqli_real_escape_string($db, $email);
$password = @mysqli_real_escape_string($db, $password);
$password = trim($password);
// Encrypt password if encryption key is available...
if (!empty($customerencryptionkey) && !empty($password)) $customerpassword = ashop_encrypt($password, $customerencryptionkey);
else $customerpassword = $password;
$date = date("Y/m/d H:i:s");
$username=strtolower($username);
$hash = md5($date.$username.$password."ashopisgreat");
$checkexists = @mysqli_query($db, "SELECT * FROM customer WHERE email='$email'");
if (!empty($affiliate) && !is_numeric($affiliate)) $affiliate = "";
$existingpassword = "";
if (@mysqli_num_rows($checkexists)) {
	$customerid = @mysqli_result($checkexists,0,"customerid");
	$existingpassword = @mysqli_result($checkexists,0,"password");
	if (!empty($existingpassword) && !empty($facebookid) && $verified) $sql = "UPDATE customer SET facebookid='$facebookid', sessionid= '$hash', activity='$date', ip='{$_SERVER["REMOTE_ADDR"]}' WHERE customerid='$customerid'";
	else {
		$existingpassword = "";
		$sql = "UPDATE customer SET username='$email', firstname='$firstname', lastname='$lastname', password='$customerpassword', allowemail='$allowemail', sessionid= '$hash', activity='$date', ip='{$_SERVER["REMOTE_ADDR"]}', facebookid='$facebookid' WHERE customerid='$customerid'";
	}
} else $sql = "INSERT INTO customer (username, firstname, lastname, email, password, allowemail, affiliateid, facebookid, sessionid, activity, ip) VALUES ('$email', '$firstname', '$lastname', '$email', '$customerpassword', '$allowemail', '$affiliate', '$facebookid', '$hash', '$date', '{$_SERVER["REMOTE_ADDR"]}')";
$result = @mysqli_query($db, "$sql");
$customerid = @mysqli_insert_id($db);
$checkshippingresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customerid'");
if (!@mysqli_num_rows($checkshippingresult)) $sql = "INSERT INTO shipping (shippingfirstname, shippinglastname, customerid) VALUES ('$firstname', '$lastname', '$customerid')";
else $sql="UPDATE shipping SET shippingfirstname='$firstname', shippinglastname='$lastname' WHERE customerid='$customerid'";
$result = mysqli_query($db, "$sql");

// Sign the customer up with the newsletter autoresponder...
if (!empty($autoresponderid) && is_numeric($autoresponderid) && $activateautoresponder == "1" && !empty($newsresponderid) && is_numeric($newsresponderid)) {
	$responderresult = @mysqli_query($db, "SELECT profileid FROM autoresponders WHERE responderid='$newsresponderid'");
	$autoresponderprofileid = @mysqli_result($responderresult, 0, "profileid");
	$querystring = "v=$autoresponderid&w=$autoresponderprofileid&subscription_type=E&id=$newsresponderid&first_name=$firstname&last_name=$lastname&email=$email&posted=true";
	$postheader = "POST /formcapture.php HTTP/1.0\r\nHost: autoresponder-service.com\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
	$fp = @fsockopen ("autoresponder-service.com", 80, $errno, $errstr, 10);
	$res = "";
	if ($fp) {
		@fputs ($fp, $postheader.$querystring);
		//while (!feof($fp)) $res .= fgets ($fp, 1024);
		@fclose ($fp);
	}
}

// Close database...
@mysqli_close($db);

// Set session cookie to automatically login the new customer...
if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
$p3psent = TRUE;
SetCookie("customersessionid", $hash);

// Send message with password to customer...
if (empty($existingpassword)) {
	if (file_exists("$ashoppath/templates/messages/signupmessage-$lang.html")) $messagefile = "$ashoppath/templates/messages/signupmessage-$lang.html";
	else $messagefile = "$ashoppath/templates/messages/signupmessage.html";
	$fp = @fopen("$messagefile","r");
	if ($fp) {
		while (!feof ($fp)) $messagetemplate .= fgets($fp, 4096);
		fclose($fp);
	} else {
		$messagetemplate="<html><head><title>".THANKYOUFORREGISTERING." $ashopname ".CUSTOMERPROFILE."</title></head><body><font face=\"$font\"><p>".THANKYOUFORREGISTERING." $ashopname ".CUSTOMERPROFILE."</p><p>".YOURUSERNAMEIS." <b>$email</b>".ANDYOURPASSWORD." <b>$password</b></p><p>".LOGINANDSTART." <b><a href=\"$ashopurl/login.php\">$ashopurl/login.php</a></b></p></font></body></html>";
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

// Allow external programming...
if (file_exists("$ashoppath/api/customersignup.inc.php")) include "api/customersignup.inc.php";

// Redirect Facebook logins to storefront...
if (!empty($facebookuser->email)) {
	if (!empty($shop) && $shop > 1) header("Location:index.php?shop=$shop");
	else header("Location:index.php");
	exit;
}

// Show login form...
if (!empty($shop) && $shop > 1) header("Location:login.php?newregistered=true&shop=$shop");
else header("Location:login.php?newregistered=true");
?>