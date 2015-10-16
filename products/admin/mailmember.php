<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

@set_time_limit(0);
include "checklicense.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/members.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Initiate password hashing...
include "$ashoppath/includes/PasswordHash.php";
$passhasher = new PasswordHash(8, FALSE);

// Store prefered mail format in a cookie...
if ($mailformat) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("prefmemmailformat","$mailformat", mktime(0,0,0,12,1,2020));
}

// Initiate mass mailing...
if ($mailall && !$recurring) {
	$result = @mysqli_query($db, "SELECT * FROM mailing WHERE type='member'");
	if (!@mysqli_num_rows($result)) {
		if ($subject && $message) {
			@mysqli_query($db, "INSERT INTO mailing (type, format, subject, message) VALUES ('member','$mailformat','$subject','$message')");
			header("Location: mailingadmin.php?type=member");
			exit;
		} else exit;
	} else {
		$subject = @mysqli_result($result,0,"subject");
		$message = @mysqli_result($result,0,"message");
		$mailformat = @mysqli_result($result,0,"format");
		$mailingid = @mysqli_result($result,0,"mailingid");
		$logfilename = @mysqli_result($result,0,"logfile");
		$lastmailtimestamp = @mysqli_result($result,0,"timestamp");
		// Check if a previous mailing has stalled...
		if ($lastmailtimestamp && time()-$lastmailtimestamp < 15) {
			echo "$logfilename";
			exit;
		} else {
			// Generate a unique session key to identify this mailing run...
			$alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
			srand ((double) microtime() * 1000000);
			$pwLength = "25";
			for($i = 1; $i <=$pwLength; $i++) $sessionkey .= $alphaNum[(rand(0,31))];
			@mysqli_query($db, "UPDATE mailing SET sessionkey='$sessionkey' WHERE mailingid='$mailingid'");
		}
	}
}

$message = stripslashes($message);
$subject = stripslashes($subject);

// Convert GET parameters to POST...
if (is_array($_GET)) foreach ($_GET as $key=>$value) $_POST["$key"] = $value;

// Function for generating unique passwords...
function makePassword() {
	$alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
	srand ((double) microtime() * 1000000);
	$pwLength = "7"; // this sets the limit on how long the password is.
	for($j = 1; $j <=$pwLength; $j++) {
		$newPass .= $alphaNum[(rand(0,31))];
	}
	return ($newPass);
}

// Set log date...
$logdate = date("Y-m-d H:i:s",time()+$timezoneoffset);

// Start log file...
if ($activate != "true" && is_dir("$ashoppath/previews") && is_writable("$ashoppath/previews")) {
	if ($mailall && $logfilename) $logfp = @fopen("$ashoppath/previews/$logfilename", "a");
	else {
		$timestamp = time()+$timezoneoffset;
		$logfilename = "maillog{$timestamp}.html";
		@mysqli_query($db, "UPDATE mailing SET logfile='$logfilename' WHERE type='member'");
		$logfp = @fopen("$ashoppath/previews/$logfilename", "w");
		if ($logfp) @fwrite($logfp, "<html><body>The message: $subject<br>was sent on $logdate to the following recipients:<br><br>");
	}
}

// Convert line breaks to make the message readable in regular email clients...
$message = str_replace("\r\n","\n",$message);
$message = str_replace("\n\r","\n",$message);
//$message = str_replace("\n","\n\r",$message);

// Mail to selected members...
if ($mailall) {
	// Check if this is a resumed mailing...
	$resumeresult = @mysqli_query($db, "SELECT MAX(recipientid) as lastrecipient FROM maillog WHERE mailingid='$mailingid'");
	if (@mysqli_num_rows($resumeresult)) $lastrecipient = @mysqli_result($resumeresult,0,"lastrecipient");
	if ($lastrecipient) $sql = "SELECT * FROM user WHERE userid>'$lastrecipient' ORDER BY userid";
	else $sql="SELECT * FROM user WHERE userid!='1' ORDER BY userid";
	$result = @mysqli_query($db, "$sql");
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$firstname = @mysqli_result($result, $i, "firstname");
		$lastname = @mysqli_result($result, $i, "lastname");
		$email = @mysqli_result($result, $i, "email");
		$address = @mysqli_result($result, $i, "address");
		$state = @mysqli_result($result, $i, "state");
		$city = @mysqli_result($result, $i, "city");
		$country = @mysqli_result($result, $i, "country");
		$url = @mysqli_result($result, $i, "url");
		$phone = @mysqli_result($result, $i, "phone");
		$shopname = @mysqli_result($result, $i, "shopname");
		$username = @mysqli_result($result, $i, "username");
		$userid = @mysqli_result($result, $i, "userid");
		$sendmessage = str_replace("%firstname%",$firstname,$message);
		$sendmessage = str_replace("%lastname%",$lastname,$sendmessage);
		$sendmessage = str_replace("%email%",$email,$sendmessage);
		$sendmessage = str_replace("%shopname%",$shopname,$sendmessage);
		$sendmessage = str_replace("%address%",$address,$sendmessage);
		$sendmessage = str_replace("%state%",$state,$sendmessage);
		$sendmessage = str_replace("%zip%",$zip,$sendmessage);
		$sendmessage = str_replace("%city%",$city,$sendmessage);
		$sendmessage = str_replace("%country%",$country,$sendmessage);
		$sendmessage = str_replace("%url%",$url,$sendmessage);
		$sendmessage = str_replace("%phone%",$phone,$sendmessage);
		$sendmessage = str_replace("%username%",$username,$sendmessage);
		$checklog = @mysqli_query($db, "SELECT * FROM maillog WHERE email='$email' AND mailingid='$mailingid'");
		if (!@mysqli_num_rows($checklog)) {
			// Check if this mailing is paused or if another process has already started taking care of it...
			$checksessionresult = @mysqli_query($db, "SELECT sessionkey, paused FROM mailing WHERE mailingid='$mailingid'");
			$checksessionkey = @mysqli_result($checksessionresult,0,"sessionkey");
			$paused = @mysqli_result($checksessionresult,0,"paused");
			if ($checksessionkey != $sessionkey || $paused) exit;
			$timestampsql = "UPDATE mailing SET timestamp='".time()."' WHERE mailingid='$mailingid'";
			@mysqli_query($db, $timestampsql);
			if ($mailformat == "html") $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			else $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";
			@ashop_mail("$email","$subject","$sendmessage","$headers");
			@mysqli_query($db, "INSERT INTO maillog (mailingid, email, recipientid) VALUES ('$mailingid', '$email','$userid')");

			// Log this email...
			if ($logfp) {
				if ($firstname) {
					if ($lastname) $fullname = "$firstname $lastname";
					else $fullname = $firstname;
				} else if ($lastname) $fullname = $lastname;
				else if ($userid == "1") $fullname = "Shop administrator";
				else $fullname = "Unknown";
				@fwrite($logfp, "$userid: $fullname &lt;$email&gt;<br>");
			}
		}
		// Sleep for a while to avoid choking the server...
		usleep($massmailthrottle);
	}
	$msg = "sent";
	// Delete this mailing since it is completed...
	@mysqli_query($db, "DELETE FROM mailing WHERE mailingid='$mailingid'");
	@mysqli_query($db, "DELETE FROM maillog WHERE mailingid='$mailingid'");
} else {
	foreach ($_POST as $key=>$value) {
		if (strstr($key,"user") && $value == "on") {
			$userid = str_replace("user","",$key);
			$userid = trim($userid);
			$result = @mysqli_query($db, "SELECT * FROM user WHERE userid='$userid'");
			$firstname = @mysqli_result($result, $i, "firstname");
			$lastname = @mysqli_result($result, $i, "lastname");
			$email = @mysqli_result($result, $i, "email");
			$address = @mysqli_result($result, $i, "address");
			$state = @mysqli_result($result, $i, "state");
			$city = @mysqli_result($result, $i, "city");
			$country = @mysqli_result($result, $i, "country");
			$url = @mysqli_result($result, $i, "url");
			$phone = @mysqli_result($result, $i, "phone");
			$shopname = @mysqli_result($result, $i, "shopname");
			$username = @mysqli_result($result, $i, "username");
			$userid = @mysqli_result($result, $i, "userid");
			$sendmessage = str_replace("%firstname%",$firstname,$message);
			$sendmessage = str_replace("%lastname%",$lastname,$sendmessage);
			$sendmessage = str_replace("%email%",$email,$sendmessage);
			$sendmessage = str_replace("%shopname%",$shopname,$sendmessage);
			$sendmessage = str_replace("%address%",$address,$sendmessage);
			$sendmessage = str_replace("%state%",$state,$sendmessage);
			$sendmessage = str_replace("%zip%",$zip,$sendmessage);
			$sendmessage = str_replace("%city%",$city,$sendmessage);
			$sendmessage = str_replace("%country%",$country,$sendmessage);
			$sendmessage = str_replace("%url%",$url,$sendmessage);
			$sendmessage = str_replace("%phone%",$phone,$sendmessage);
			$sendmessage = str_replace("%username%",$username,$sendmessage);
			if ($activate == "true") {
				if (!empty($cpanelapiuser) && !empty($cpanelapipass) && !empty($cpanelapiurl)) {
					if (!file_exists("$ashopspath/updates/makeshop")) {
						mkdir("$ashopspath/updates/makeshop");
						@chmod("$ashopspath/updates/makeshop", 0755);
					}
					$makeshophash = md5($ashoppath.$userid);
					$sql="UPDATE user SET password='$makeshophash' WHERE userid='$userid'";
					$result2 = @mysqli_query($db, "$sql");
					$fp = fopen ("$ashopspath/updates/makeshop/$userid","w");
					fwrite($fp, $makeshophash);
					fclose($fp);
					$params = array("lang"=>$lang);
					ashop_postasync("$ashopurl/admin/makeshop.php",$params);
				} else {
					$password = makePassword();
					$passhash = $passhasher->HashPassword($password);
					$unique = 0;
					while (!$unique) {
						$sql="SELECT password FROM user WHERE password='$passhash'";
						$result2 = @mysqli_query($db, "$sql");
						if (@mysqli_num_rows($result2) == 0) $unique = 1;
						else {
							$password = makePassword();
							$passhash = $passhasher->HashPassword($password);
						}
					}
					$sql="UPDATE user SET password='$passhash' WHERE userid='$userid'";
					$result2 = @mysqli_query($db, "$sql");
					$subject = YOURSHOPPINGMALLACCOUNT." $ashopname";
					$sendmessage = "<html><head><title>".THANKYOUFORAPPLYING." $ashopname!</title></head><body><font face=\"$font\"><p>".THANKYOUFORAPPLYING." $ashopname!</p><p>".YOURUSERNAMEIS.": <b>$username</b>, ".YOURPASSWORDIS.": <b>$password</b>.<br>".YOUCANLOGINTOADMINAT.": <a href=\"$ashopurl/admin/login.php\">$ashopurl/admin/login.php</a>.";
					if ($membershops) $sendmessage .= "<br>".YOURSHOPISHERE.": <a href=\"$ashopurl/index.php?shop=$userid\">$ashopurl/index.php?shop=$userid</a>";
					$sendmessage .= "</p></font></body></html>";
					$msg = "activated";
				}
			} else $msg = "sent";
			if ($mailformat == "html" || $activate == "true") $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			else $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";
			if (empty($cpanelapiuser) || empty($cpanelapipass) || empty($cpanelapiurl)) @ashop_mail("$email","$subject","$sendmessage","$headers");

			// Log this email...
			if ($logfp) {
				if ($firstname) {
					if ($lastname) $fullname = "$firstname $lastname";
					else $fullname = $firstname;
				} else if ($lastname) $fullname = $lastname;
				else $fullname = "Unknown";
				@fwrite($logfp, "$userid: $fullname &lt;$email&gt;<br>");
			}
		}
	}
}
if ($logfp) {
	@fwrite($logfp, "</body></html>");
	@fclose($logfp);
	@chmod("$ashoppath/previews/$logfilename", 0666);
}
if ($mailall) {
	echo "$logfilename";
	exit;
}

if (strstr($SERVER_SOFTWARE, "IIS")) {
	echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=memberadmin.php?msg=$msg&log=$logfilename\"></head></html>";
	exit;
} else header ("Location: memberadmin.php?msg=$msg&log=$logfilename");
?>