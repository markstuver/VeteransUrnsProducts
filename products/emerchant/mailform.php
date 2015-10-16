<?php

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
	if ($action != "generatecode" && empty($_POST["random"])) {
		mt_srand ((double)microtime()*1000000);
		$maxrandom = 1000000;
		$random = mt_rand(0, $maxrandom);
	} else if ($action == "generatecode") {
		$checkcode = generatecode($random);
		$image = ImageCreateFromJPEG("$ashoppath/admin/images/codebg.jpg");
		$text_color = ImageColorAllocate($image, 80, 80, 80);
		Header("Content-type: image/jpeg");
		ImageString ($image, 5, 12, 2, $checkcode, $text_color);
		ImageJPEG($image, NULL, 75);
		ImageDestroy($image);
		exit;
	}
}

if ($email && $message) {
	if ($email && isset($_POST["confirmemail"]) && $email != $confirmemail) $errmsg = "<p><font size=\"2\" face=\"$font\" color=\"#FF0000\"><span class=\"fontsize2\">Email confirmation did not match! Try again!</span></font></p>";
	else if ($activatesecuritycheck && (!$securitycheck || $securitycheck != generatecode($random))) $errmsg = "<p><font size=\"2\" face=\"$font\" color=\"#FF0000\"><span class=\"fontsize2\">Security code did not match! Try again!</span></font></p>";
	else {
		// Include extra fields...
		$extrafields = "";
		$basketstring = "";
		if (is_array($_POST)) foreach($_POST as $key=>$value) if ($key != "confirmemail" && $key != "subject" && $key != "random" && $key != "securitycheck" && $key != "message" && !strstr($key,"submit")) {
			$bigkey = strtoupper(substr($key,0,1)).substr($key,1);
			$multivalue = "";
			if (is_array($value)) {
				foreach($value as $multikey=>$value) $multivalue .= $value.", ";
				$multivalue = substr($multivalue, 0, -2);
				$extrafields .= "$bigkey: $multivalue\n";
			} else {
				if (strstr($key,"product")) {
					$productid = str_replace("product","",$key);
					if (is_numeric(trim($value))) $quantity = trim($value);
					$basketstring .= $quantity."b".$productid."a";
				} else $extrafields .= "$bigkey: $value\n";
			}
		}

		if ($extrafields) $message = $extrafields."\n".$message;

		$timestamp = time()+$timezoneoffset;
		if ($firstname) {
			if ($lastname) $name = "$firstname $lastname";
			else $name = $firstname;
		} else $name = $lastname;
		if (!$subject) $subject = "Mailform message";

		// Store message in eMerchant if available...
		if (file_exists("$ashoppath/emerchant/quote.php")) {
			// Open database...
			$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
			@mysqli_query($db, "INSERT INTO emerchant_inbox (received, name, email, subject) VALUES ('$timestamp', '$name', '$email', '$subject')");
			$mailid = @mysqli_insert_id($db);

			if ($message) {
				$fp = @fopen ("$ashoppath/emerchant/mail/in1-$mailid", "w");
				if ($fp) {
					fwrite($fp, $message);
					fclose($fp);
				}
			}
			@mysqli_close($db);
		} else {
			$subject = ashop_mailsafe($subject);
			$email = ashop_mailsafe($email);
			$nomailheaders = preg_match("/(content-type:|to:|cc:|bcc:)/i", $message) == 0;
			if (!$nomailheaders) $message = "";
			$name = ashop_mailsafe($name);
			// Send message as regular email...
			$headers = "From: $name<$email>\nX-Sender: <$email>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$email>\n";
			@ashop_mail("$ashopemail","$subject","$message","$headers");
		}

		// Redirect to payment if needed...
		if ($basketstring) {
			$querystring = "basket=$basketstring&";
			if ($email) $querystring .= "destemail=$email&";
			if ($business) $querystring .= "destbusiness=$business&";
			if ($firstname) $querystring .= "destfirstname=$firstname&";
			if ($lastname) $querystring .= "destlastname=$lastname&";
			if ($address) $querystring .= "destaddress=$address&";
			if ($address2) $querystring .= "destaddress2=$address2&";
			if ($zip) $querystring .= "destzip=$zip&";
			if ($city) $querystring .= "destcity=$city&";
			if ($state) $querystring .= "deststate=$state&";
			if ($country) $querystring .= "destcntry=$country&";
			if ($vat) $querystring .= "destvat=$vat&";
			if ($phone) $querystring .= "destphone=$phone&";
			$querystring .= "action=checkout";
			header("Location: ../shipping.php?$querystring");
			exit; 
		}

		// Show thank you message...
		$errmsg = "<p align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#006600\"><b>Your message has been sent! Thank you!</b></font></p><span style=\"visibility: hidden;\">";
		$errmsg2 = "</span>";
	}
} else if (count($_POST) && !$_GET["ashopsupport"]) $errmsg = "<p><font size=\"2\" face=\"$font\" color=\"#FF0000\"><span class=\"fontsize2\">You must enter your email address and a message! Try again!</span></font></p>";

// Determine which template to use...
$templatepath = "";
$currentpath = strtolower(getcwd());
$currentwinpath = str_replace("\\","/",$currentpath);
if ($currentpath == strtolower($ashoppath)."/emerchant" || $currentwinpath == strtolower($ashoppath)."/emerchant") {

	// Apply selected theme...
	$themepath = "/templates";
	if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "$ashoppath/themes/$ashoptheme/theme.cfg.php";
	if ($usethemetemplates == "true") $themepath = "/themes/$ashoptheme";
	if ($lang && is_array($themelanguages)) {
		if (!in_array("$lang",$themelanguages)) unset($lang);
	}

	// Check if a mobile device is being used...
	$device = ashop_mobile();

	if (file_exists($ashoppath."$themepath/contactus-$lang.html")) $templatepath = $ashoppath."$themepath/contactus-$lang.html";
	else if (file_exists($ashoppath."$themepath/contactus.html")) $templatepath = $ashoppath."$themepath/contactus.html";
	else if (file_exists($ashoppath."/emerchant/contactus.html")) $templatepath = $ashoppath."/emerchant/contactus.html";
} else if (file_exists($currentpath."/contactus.html")) $templatepath = $currentpath."/contactus.html";

// Display mail form...
if (!empty($templatepath)) {
	$fp = fopen ($templatepath,"r");
	while (!feof ($fp)) $template .= fgets($fp, 4096);
	fclose($fp);

	// Create customer profile links...
	if (strpos($template,"<!-- AShopcustomerlinks -->") !== false) {
		$temppath = getcwd();
		$layout = 6;
		ob_start();
		chdir($ashoppath);
		include "includes/topform.inc.php";
		$resulthtml = ob_get_contents();
		ob_end_clean();
		chdir($temppath);
		$template = str_replace("<!-- AShopcustomerlinks -->", $resulthtml, $template);
	}

	// Create customer profile links...
	if (strpos($template,"<!-- AShopmenu -->") !== false) {
		$temppath = getcwd();
		ob_start();
		chdir($ashoppath);
		include "includes/menu.inc.php";
		$resulthtml = ob_get_contents();
		ob_end_clean();
		chdir($temppath);
		$template = str_replace("<!-- AShopmenu -->", $resulthtml, $template);
	}

	// Parse logo image tag...
	include "../includes/logo.inc.php";
	$template = str_replace("<!-- AShoplogo -->", $ashoplogohtml, $template);
	$template = str_replace("<!-- AShoperrormessage -->", "$errmsg", $template);
	$template = str_replace("<!-- AShoperrormessage2 -->", "$errmsg2", $template);
	$template = str_replace("<!-- AShopsecurityimage -->", "<img src='mailform.php?action=generatecode&random=$random' border='1' alt='Security Code' title='Security Code'>", $template);
	$template = str_replace("<!-- AShopsecuritycode -->", "$random", $template);
	$template = str_replace("<!-- AShopname -->", "$ashopname", $template);
	$template = str_replace("<!-- AShopaddress -->", "$ashopaddress", $template);
	$template = str_replace("<!-- AShopphone -->", "$ashopphone", $template);
	$template = str_replace("<!-- AShopemail -->", "$ashopemail", $template);
	if (is_array($_POST)) foreach ($_POST as $key => $value) $template = str_replace("<!-- Mailform_$key -->", "$value", $template);
	$template = preg_replace("/<!-- Mailform_[a-zA-Z_]+ -->/", "", $template);
	echo $template;
}
?>