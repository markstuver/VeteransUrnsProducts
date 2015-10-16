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

@set_time_limit(0);
include "config.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
include "customers.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Store prefered mail format in a cookie...
if ($mailformat) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("prefcusmailformat","$mailformat", mktime(0,0,0,12,1,2020));
}

// Initiate mass mailing...
if ($mailall && !$recurring) {
	$result = @mysqli_query($db, "SELECT * FROM mailing WHERE type='wholesale'");
	if (!@mysqli_num_rows($result)) {
		if ($subject && $message) {
			@mysqli_query($db, "INSERT INTO mailing (type, format, subject, message) VALUES ('wholesale','$mailformat','$subject','$message')");
			header("Location: mailingadmin.php?type=wholesale");
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
	for($i = 1; $i <=$pwLength; $i++) {
		$newPass .= $alphaNum[(rand(0,31))];
	}
	return ($newPass);
}

// Set log date...
$logdate = date("Y-m-d H:i:s",time()+$timezoneoffset);

// Start log file...
if (is_dir("$ashoppath/previews") && is_writable("$ashoppath/previews")) {
	if ($logfilename) $logfp = @fopen("$ashoppath/previews/$logfilename", "a");
	else {
		$timestamp = time()+$timezoneoffset;
		$logfilename = "maillog{$timestamp}.html";
		@mysqli_query($db, "UPDATE mailing SET logfile='$logfilename' WHERE type='wholesale'");
		$logfp = @fopen("$ashoppath/previews/$logfilename", "w");
		if ($logfp) @fwrite($logfp, "<html><body>The message: $subject<br>was sent on $logdate to the following recipients:<br><br>");
	}
}

// Convert line breaks to make the message readable in regular email clients...
$message = str_replace("\r\n","\n",$message);
$message = str_replace("\n\r","\n",$message);
//$message = str_replace("\n","\n\r",$message);

// Mail to selected customers...
if ($mailall) {
	// Check if this is a resumed mailing...
	$resumeresult = @mysqli_query($db, "SELECT MAX(recipientid) as lastrecipient FROM maillog WHERE mailingid='$mailingid'");
	if (@mysqli_num_rows($resumeresult)) $lastrecipient = @mysqli_result($resumeresult,0,"lastrecipient");
	if ($lastrecipient) $sql = "SELECT * FROM customer WHERE firstname IS NOT NULL AND email IS NOT NULL AND level>'0' AND level IS NOT NULL AND customerid>$lastrecipient ORDER BY customerid";
	else $sql="SELECT * FROM customer WHERE firstname IS NOT NULL AND email IS NOT NULL AND level>'0' AND level IS NOT NULL ORDER BY customerid";
	$result = @mysqli_query($db, "$sql");
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$customerid = @mysqli_result($result, $i, "customerid");
		$firstname = @mysqli_result($result, $i, "firstname");
		$lastname = @mysqli_result($result, $i, "lastname");
		$email = @mysqli_result($result, $i, "email");
		$allowemail = @mysqli_result($result, $i, "allowemail");
		$address = @mysqli_result($result, $i, "address");
		$state = @mysqli_result($result, $i, "state");
		$city = @mysqli_result($result, $i, "city");
		$country = @mysqli_result($result, $i, "country");
		$phone = @mysqli_result($result, $i, "phone");
		$wsuser = @mysqli_result($result, $i, "username");
		$sendmessage = str_replace("%firstname%",$firstname,$message);
		$sendmessage = str_replace("%lastname%",$lastname,$sendmessage);
		$sendmessage = str_replace("%email%",$email,$sendmessage);
		$sendmessage = str_replace("%customerid%",$customerid,$sendmessage);
		$sendmessage = str_replace("%address%",$address,$sendmessage);
		$sendmessage = str_replace("%state%",$state,$sendmessage);
		$sendmessage = str_replace("%zip%",$zip,$sendmessage);
		$sendmessage = str_replace("%city%",$city,$sendmessage);
		$sendmessage = str_replace("%country%",$country,$sendmessage);
		$sendmessage = str_replace("%phone%",$phone,$sendmessage);
		$checklog = @mysqli_query($db, "SELECT * FROM maillog WHERE email='$email' AND mailingid='$mailingid'");
		if (!@mysqli_num_rows($checklog)) {
			// Check if this mailing is paused or if another process has already started taking care of it...
			$checksessionresult = @mysqli_query($db, "SELECT sessionkey, paused FROM mailing WHERE mailingid='$mailingid'");
			$checksessionkey = @mysqli_result($checksessionresult,0,"sessionkey");
			$paused = @mysqli_result($checksessionresult,0,"paused");
			if ($checksessionkey != $sessionkey || $paused) exit;
			$timestampsql = "UPDATE mailing SET timestamp='".time()."' WHERE mailingid='$mailingid'";
			@mysqli_query($db, $timestampsql);
			@mysqli_query($db, "INSERT INTO maillog (mailingid, email, recipientid) VALUES ('$mailingid', '$email','$customerid')");
			if ($mailformat == "html") $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			else $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";
			@ashop_mail("$email","$subject","$sendmessage","$headers");
			
			/* Store in eMerchant customer history if eMerchant is installed...
			if (file_exists("$ashoppath/emerchant/quote.php") && $userid == 1) {
				$date = date("Y-m-d",time()+$timezoneoffset);
				@mysqli_query($db, "INSERT INTO emerchant_messages (customerid, user, replyto, date, subject) VALUES ('$customerid', '1', '0', '$date', '$subject')");
				$mailid = @mysqli_insert_id($db);
				if ($sendmessage) {
					$fp = @fopen ("$ashoppath/emerchant/mail/cust-$mailid", "w");
					if ($fp) {
						fwrite($fp, $sendmessage);
						fclose($fp);
					}
				}
			}*/

			// Log this email...
			if ($logfp) {
				if ($firstname) {
					if ($lastname) $fullname = "$firstname $lastname";
					else $fullname = $firstname;
				} else if ($lastname) $fullname = $lastname;
				else $fullname = "Unknown";
				if (file_exists("$ashoppath/emerchant/quote.php") && $userid == 1) @fwrite($logfp, "<a href=\"$ashopurl/emerchant/customer.php?id=$customerid\" target=\"_blank\">$customerid</a>: $fullname &lt;<a href=\"$ashopurl/emerchant/composemessage.php?customer=$customerid\" target=\"_blank\">$email</a>&gt;<br>");
				else @fwrite($logfp, "$customerid: $fullname &lt;$email&gt;<br>");
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
		if (strstr($key,"customer") && $value == "on") {
			$customerid = str_replace("customer","",$key);
			$customerid = trim($customerid);
			$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
			$firstname = @mysqli_result($result, $i, "firstname");
			$lastname = @mysqli_result($result, $i, "lastname");
			$email = @mysqli_result($result, $i, "email");
			$allowemail = @mysqli_result($result, $i, "allowemail");
			$address = @mysqli_result($result, $i, "address");
			$state = @mysqli_result($result, $i, "state");
			$city = @mysqli_result($result, $i, "city");
			$country = @mysqli_result($result, $i, "country");
			$phone = @mysqli_result($result, $i, "phone");
			$wsuser = @mysqli_result($result, $i, "username");
			$sendmessage = str_replace("%firstname%",$firstname,$message);
			$sendmessage = str_replace("%lastname%",$lastname,$sendmessage);
			$sendmessage = str_replace("%email%",$email,$sendmessage);
			$sendmessage = str_replace("%customerid%",$customerid,$sendmessage);
			$sendmessage = str_replace("%address%",$address,$sendmessage);
			$sendmessage = str_replace("%state%",$state,$sendmessage);
			$sendmessage = str_replace("%zip%",$zip,$sendmessage);
			$sendmessage = str_replace("%city%",$city,$sendmessage);
			$sendmessage = str_replace("%country%",$country,$sendmessage);
			$sendmessage = str_replace("%phone%",$phone,$sendmessage);
			if ($allowemail || $activate == "true") {
				if ($activate == "true") {
					// Generate a unique password...
					$password = makePassword();
					// Encrypt password if encryption key is available...
					if (!empty($customerencryptionkey) && !empty($password)) $password = ashop_encrypt($password, $customerencryptionkey);
					$unique = 0;
					while (!$unique) {
						$sql="SELECT password FROM customer WHERE password='$password'";
						$result = mysqli_query($db, "$sql");
						if (@mysqli_num_rows($result) == 0) $unique = 1;
						else {
							$password = makePassword();
							// Encrypt password if encryption key is available...
							if (!empty($customerencryptionkey) && !empty($password)) $password = ashop_encrypt($password, $customerencryptionkey);
						}
					}
					$sql="UPDATE customer SET password='$password' WHERE customerid=$customerid";
					$result = mysqli_query($db, "$sql");
					$subject = YOURWHOLESALEACCOUNT." $ashopname";
					$sendmessage = "<html><head><title>".THANKYOUFORAPPLYING." $ashopname!</title></head><body><font face=\"$font\"><p>".THANKYOUFORAPPLYING." $ashopname!</p><p>".YOURUSERNAMEIS.": <b>$wsuser</b>, ".YOURPASSWORDIS.": <b>$password</b>. ".YOUCANLOGINTOWHOLESALEAT.": <a href=\"$ashopurl/wholesale/login.php\">$ashopurl/wholesale/login.php</a></p></font></body></html>";
					$msg = "activated";
				} else $msg = "sent";
				
				if ($mailformat == "html" || $activate == "true") $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
				else $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";
				@ashop_mail("$email","$subject","$sendmessage","$headers");

				// Store in eMerchant customer history if eMerchant is installed...
				if (file_exists("$ashoppath/emerchant/quote.php") && $userid == 1) {
					$date = date("Y-m-d",time()+$timezoneoffset);
					@mysqli_query($db, "INSERT INTO emerchant_messages (customerid, user, replyto, date, subject) VALUES ('$customerid', '1', '0', '$date', '$subject')");
					$mailid = @mysqli_insert_id($db);
					if ($sendmessage) {
						$fp = @fopen ("$ashoppath/emerchant/mail/cust-$mailid", "w");
						if ($fp) {
							fwrite($fp, $sendmessage);
							fclose($fp);
						}
					}
				}

				// Log this email...
				if ($logfp) {
					if ($firstname) {
						if ($lastname) $fullname = "$firstname $lastname";
						else $fullname = $firstname;
					} else if ($lastname) $fullname = $lastname;
					else $fullname = "Unknown";
					if (file_exists("$ashoppath/emerchant/quote.php") && $userid == 1) @fwrite($logfp, "<a href=\"$ashopurl/emerchant/customer.php?id=$customerid\" target=\"_blank\">$customerid</a>: $fullname &lt;<a href=\"$ashopurl/emerchant/composemessage.php?customer=$customerid\" target=\"_blank\">$email</a>&gt;<br>");
					else @fwrite($logfp, "$customerid: $fullname &lt;$email&gt;<br>");
				}
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
	echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=wssalesadmin.php?msg=$msg&log=$logfilename\"></head></html>";
	exit;
} else header ("Location: wssalesadmin.php?msg=$msg&log=$logfilename");
?>