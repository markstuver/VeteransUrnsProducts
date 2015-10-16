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

// Check for GD...
ob_start(); 
phpinfo(8); 
$phpinfo=ob_get_contents(); 
ob_end_clean(); 
$phpinfo=strip_tags($phpinfo); 
$phpinfo=stristr($phpinfo,"gd version"); 
$phpinfo=stristr($phpinfo,"version"); 
$end=strpos($phpinfo,"\n"); 
$phpinfo=substr($phpinfo,0,$end);
preg_match ("/[0-9]/", $phpinfo, $version);
if(isset($version[0]) && $version[0]>1) $gdversion = 2;
else $gdversion = 0;

include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";

// If GD is available generate random code for security check...
if (function_exists('imagecreatefromjpeg') && function_exists('imagecreatefromgif') && function_exists('imagecreatetruecolor') && $gdversion == 2) {
	$activatesecuritycheck = TRUE;
	if ($action == "generatecode") {
		$checkcode = generatecode($random);
		$image = ImageCreateFromJPEG("$ashoppath/admin/images/codebg.jpg");
		$text_color = ImageColorAllocate($image, 80, 80, 80);
		Header("Content-type: image/jpeg");
		ImageString ($image, 5, 12, 2, $checkcode, $text_color);
		ImageJPEG($image, NULL, 75);
		ImageDestroy($image);
		exit;
	}
} else $activatesecuritycheck = FALSE;

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/ws_signup.inc.php";

// Check for spam injection...
$wsuser = ashop_mailsafe($wsuser);
$wsuser = ashop_cleanfield($wsuser);
if (strlen($wsuser) < 2) $wsuser = "";
$businessname = ashop_mailsafe($businessname);
$businessname = ashop_cleanfield($businessname);
if (strlen($businessname) < 2) $businessname = "";
$businesstype = ashop_mailsafe($businesstype);
$businesstype = ashop_cleanfield($businesstype);
if (strlen($businesstype) < 2) $businesstype = "";
$resellerid = ashop_mailsafe($resellerid);
$resellerid = ashop_cleanfield($resellerid);
if (strlen($resellerid) < 2) $resellerid = "";
$firstname = ashop_mailsafe($firstname);
$firstname = ashop_cleanfield($firstname);
if (strlen($firstname) < 2) $firstname = "";
$lastname = ashop_mailsafe($lastname);
$lastname = ashop_cleanfield($lastname);
if (strlen($lastname) < 2) $lastname = "";
$email = ashop_mailsafe($email);
$email = ashop_cleanfield($email);
$checkemailarray = explode("@",$email);
if (empty($checkemailarray[1])) $email = "";
else {
	if (strstr($checkemailarray[1],"'")) $email = "";
	if (strlen($email) < 2) $email = "";
}
$address = ashop_mailsafe($address);
$address = ashop_cleanfield($address);
if (strlen($address) < 2) $address = "";
$state = ashop_mailsafe($state);
$state = ashop_cleanfield($state);
if (strlen($state) < 2) $state = "";
$province = ashop_mailsafe($province);
$province = ashop_cleanfield($province);
if (strlen($province) < 2) $province = "";
if (empty($state) && !empty($province)) $state = $province;
$zip = ashop_mailsafe($zip);
$zip = ashop_cleanfield($zip);
if (strlen($zip) < 2) $zip = "";
$city = ashop_mailsafe($city);
$city = ashop_cleanfield($city);
if (strlen($city) < 2) $city = "";
$country = ashop_mailsafe($country);
$country = ashop_cleanfield($country);
if (strlen($country) < 2) $country = "";
$phone = ashop_mailsafe($phone);
$phone = ashop_cleanfield($phone);
if (strlen($phone) < 2) $phone = "";
$vat = ashop_mailsafe($vat);
$vat = ashop_cleanfield($vat);
if (strlen($vat) < 2) $vat = "";
$url = ashop_cleanfield($url);
if (substr($url,0,7) != "http://" && substr($url,0,8) != "https://") $url = "http://".$url;
if (strlen($url) < 11) $url = "";

// Check if all fields were filled in...
if (($wsuser=="") || ($businessname=="") || ($businesstype=="") || ($firstname=="") || ($lastname=="") || ($email=="") || ($address=="") || ($state=="") || ($zip=="") || ($city=="") || ($country=="") || ($phone=="")) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/wssignup-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/wssignup.html");
    echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
		<p><span class=\"ashopmessage\">".YOUFORGOT."</span></p><p><span class=\"ashopmessage\">
		 <a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
    exit;
}

// If VAT or ABN is needed, check if this information is missing...
if (($requestvat || $requestabn) && $vat=="") {
	if ($requestvat) $taxnumber = "VAT";
	else if ($requestabn) $taxnumber = "ABN";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/wssignup-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/wssignup.html");
    echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
		<p><span class=\"ashopmessage\">".YOUFORGOTTAX1." $taxnumber ".YOUFORGOTTAX2."</span></p><p><span class=\"ashopmessage\">
		 <a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
    exit;
}

// Check if the username contains forbidden characters...
if (strstr($wsuser, chr(32)) || strstr($wsuser, chr(33)) || strstr($wsuser, chr(44)) || strstr($wsuser, chr(46)) || strstr($wsuser, chr(63)) || (strlen($wsuser) > 10)) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/wssignup-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/wssignup.html");
    echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
		<p><span class=\"ashopmessage\">".THEUSERNAME."</span></p><p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
    exit;
}

// Check if the right security check code has been provided...
if ($activatesecuritycheck && (!$securitycheck || $securitycheck != generatecode($random))) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/wssignup-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/wssignup.html");
    echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
		<p><span class=\"ashopmessage\">".INCORRECTSECURITYCODE."</span></p><p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
    exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check wholesale member data...
$sql="SELECT username FROM customer WHERE username='$wsuser' AND password IS NOT NULL";
$result = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($result) != 0) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/wssignup-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/wssignup.html");
    echo "<table class=\"ashopmessagetable\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".SORRY."</span></p>
		<p><span class=\"ashopmessage\">".ALREADYINUSE."</span></p><p><span class=\"ashopmessage\">
		 <a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");
    exit;
}

// Store wholesale customer data...
if (!empty($affiliate) && !is_numeric($affiliate)) $affiliate = "";
$sql = "INSERT INTO customer (username, firstname, lastname, email, address, state, zip, city, country, phone, businessname, businesstype, resellerid, url, extrainfo, allowemail, affiliateid, level) VALUES ('$wsuser', '$firstname', '$lastname', '$email', '$address', '$state', '$zip', '$city', '$country', '$phone', '$businessname', '$businesstype', '$resellerid', '$url', '$extrainfo', '1', '$affiliate', '1')";
$result = @mysqli_query($db, "$sql");
$wsid = @mysqli_insert_id($db);
$checkshippingresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$wsid'");
if (!@mysqli_num_rows($checkshippingresult)) $sql = "INSERT INTO shipping (shippingfirstname, shippinglastname, shippingaddress, shippingzip, shippingcity, shippingstate, shippingcountry, shippingphone, shippingemail, vat, customerid) VALUES ('$firstname', '$lastname', '$address', '$zip', '$city', '$state', '$country', '$phone', '$email', '$vat', '$wsid')";
else $sql="UPDATE shipping SET shippingfirstname='$firstname', shippinglastname='$lastname', shippingaddress='$address', shippingzip='$zip', shippingcity='$city', shippingstate='$state', shippingcountry='$country', shippingphone='$phone', shippingemail='$email', vat='$vat' WHERE customerid='$wsid'";
$result = mysqli_query($db, "$sql");

// Close database...
@mysqli_close($db);

// Send message to inform webmaster about the new wholesale customer...
$message="<html><head><title>Wholesale customer application</title></head><body><font face=\"$font\"><b>$wsuser</b> has applied for a wholesale account.</font></body></html>";
$headers = "From: $wsuser<$email>\nX-Sender: <$email>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$email>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

@ashop_mail("$ashopemail","Wholesale account application","$message","$headers");


// Send message with password to wholesale customer...
if (file_exists("$ashoppath/templates/messages/wssignupmessage-$lang.html")) $messagefile = "$ashoppath/templates/messages/wssignupmessage-$lang.html";
else $messagefile = "$ashoppath/templates/messages/wssignupmessage.html";
$fp = @fopen ("$messagefile","r");
if ($fp) {
	while (!feof ($fp)) $messagetemplate .= fgets($fp, 4096);
	fclose($fp);
} else {
	$messagetemplate="<html><head><title>".THANKYOUFORJOINING." $ashopname!</title></head><body><font face=\"$font\"><p>".THANKYOUFORJOINING." $ashopname!</p><p>".YOURUSERNAMEIS." <b>$wsuser</b>, ".YOURPASSWORD."</p></font></body></html>";
}
$message = str_replace("%ashopname%",$ashopname,$messagetemplate);
$message = str_replace("%username%",$wsuser,$message);
$message = str_replace("%business%",$businessname,$message);
$message = str_replace("%firstname%",$firstname,$message);
$message = str_replace("%lastname%",$lastname,$message);
$message = str_replace("%email%",$email,$message);
$message = str_replace("%address%",$address,$message);
$message = str_replace("%state%",$state,$message);
$message = str_replace("%zip%",$zip,$message);
$message = str_replace("%city%",$city,$message);
$message = str_replace("%country%",$country,$message);
$message = str_replace("%phone%",$phone,$message);
$message = str_replace("%url%",$url,$message);
// Get current date and time...
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
$message = str_replace("%date%",$date,$message);
$message = str_replace("%comment%","$extrainfo",$message);
$message = str_replace("%resellerid%","$resellerid",$message);

$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
@ashop_mail("$email","$ashopname ".WHOLESALESUBJECT,"$message","$headers");

// Show header using template signup.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/wssignup-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/wssignup.html");

echo "
<br><table width=\"85%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\" align=\"center\">
  <tr><td align=\"left\"> 

      <p><font face=\"$font\" size=\"3\"><b>".YOURAPPLICATION." $ashopname ".HASBEENRECEIVED."</b></p>
      <p><table><tr><td align=\"left\"><font face=\"$font\" size=\"2\">".WILLBEREVIEWED."</font></td></tr></table></p>
      </td>
  </tr></table>";

// Show footer using template signup.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/wssignup-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/wssignup.html");
?>