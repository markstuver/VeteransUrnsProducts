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
	$result = @mysqli_query($db, "SELECT * FROM mailing WHERE type='customer'");
	if (!@mysqli_num_rows($result)) {
		if ($subject && $message) {
			@mysqli_query($db, "INSERT INTO mailing (type, format, subject, message) VALUES ('customer','$mailformat','$subject','$message')");
			header("Location: mailingadmin.php?type=customer");
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

// Set log date...
$logdate = date("Y-m-d H:i:s",time()+$timezoneoffset);

// Start log file...
if (is_dir("$ashoppath/previews") && is_writable("$ashoppath/previews")) {
	if ($logfilename) $logfp = @fopen("$ashoppath/previews/$logfilename", "a");
	else {
		$timestamp = time()+$timezoneoffset;
		$logfilename = "maillog{$timestamp}.html";
		@mysqli_query($db, "UPDATE mailing SET logfile='$logfilename' WHERE type='customer'");
		$logfp = @fopen("$ashoppath/previews/$logfilename", "w");
		if ($logfp) @fwrite($logfp, "<html><body>The message: $subject<br>was sent on $logdate to the following recipients:<br><br>");
	}
}

// Convert line breaks to make the message readable in regular email clients...
$message = str_replace("\r\n","\n",$message);
$message = str_replace("\n\r","\n",$message);
//$message = str_replace("\n","\n\r",$message);

// Get sender email if sent by DM member...
if ($userid != "1") {
	$result = @mysqli_query($db, "SELECT * FROM user WHERE userid='$userid'");
	$membermail = @mysqli_result($result, 0, "email");
	if ($membermail) $ashopemail = $membermail;
}

// Mail to selected customers...
if ($mailall) {
	// Check if this is a resumed mailing...
	$resumeresult = @mysqli_query($db, "SELECT MAX(recipientid) as lastrecipient FROM maillog WHERE mailingid='$mailingid'");
	if (@mysqli_num_rows($resumeresult)) $lastrecipient = @mysqli_result($resumeresult,0,"lastrecipient");
	if ($lastrecipient) $sql = "SELECT * FROM customer WHERE firstname != '' AND email != '' AND allowemail='1' AND password != '' AND password IS NOT NULL AND customerid>$lastrecipient ORDER BY customerid ASC";
	else $sql = "SELECT * FROM customer WHERE firstname != '' AND email != '' AND allowemail='1' AND password != '' AND password IS NOT NULL ORDER BY customerid ASC";
	if ($recurring) {
		if ($lastrecipient) $sql = "SELECT DISTINCT customer.customerid, customer.* FROM customer, orders, emerchant_bills WHERE customer.firstname != '' AND customer.email != '' AND customer.allowemail='1' AND orders.customerid=customer.customerid AND orders.orderid=emerchant_bills.orderid AND emerchant_bills.recurring != '' AND customerid>$lastrecipient ORDER BY customerid ASC";
		else $sql = "SELECT DISTINCT customer.customerid, customer.* FROM customer, orders, emerchant_bills WHERE customer.firstname != '' AND customer.email != '' AND customer.allowemail='1' AND orders.customerid=customer.customerid AND orders.orderid=emerchant_bills.orderid AND emerchant_bills.recurring != '' ORDER BY customerid ASC";
	}
	$result = @mysqli_query($db, "$sql");
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$customerid = @mysqli_result($result, $i, "customerid");
		$firstname = @mysqli_result($result, $i, "firstname");
		$lastname = @mysqli_result($result, $i, "lastname");
		$email = @mysqli_result($result, $i, "email");
		$address = @mysqli_result($result, $i, "address");
		$state = @mysqli_result($result, $i, "state");
		$city = @mysqli_result($result, $i, "city");
		$country = @mysqli_result($result, $i, "country");
		$phone = @mysqli_result($result, $i, "phone");
		$password = @mysqli_result($result, $i, "password");
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
			if ($mailformat == "html") $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			else $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";
			@ashop_mail("$email","$subject","$sendmessage","$headers");
			@mysqli_query($db, "INSERT INTO maillog (mailingid, email, recipientid) VALUES ('$mailingid', '$email','$customerid')");

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
		} else {
			// Check if this mailing is paused or if another process has already started taking care of it...
			$checksessionresult = @mysqli_query($db, "SELECT sessionkey, paused FROM mailing WHERE mailingid='$mailingid'");
			$checksessionkey = @mysqli_result($checksessionresult,0,"sessionkey");
			$paused = @mysqli_result($checksessionresult,0,"paused");
			if ($checksessionkey != $sessionkey || $paused) exit;
			@mysqli_query($db, "INSERT INTO maillog (mailingid, email, recipientid) VALUES ('$mailingid', '$email','$customerid')");
		}
		// Sleep for a while to avoid choking the server...
		usleep($massmailthrottle);
	}
	// Delete this mailing since it is completed...
	@mysqli_query($db, "DELETE FROM mailing WHERE mailingid='$mailingid'");
} else {
	foreach ($_POST as $key=>$value) {
		if (strstr($key,"customer") && $value == "on") {
			$customerid = str_replace("customer","",$key);
			$customerid = trim($customerid);
			if ($recurring) {
				$checkbillresult = @mysqli_query($db, "SELECT * FROM orders, emerchant_bills WHERE orders.customerid='$customerid' AND orders.orderid=emerchant_bills.orderid AND emerchant_bills.recurring != '' LIMIT 1");
				if (!@mysqli_num_rows($checkbillresult)) continue;
			}
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
			if ($allowemail) {
				if ($mailformat == "html") $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
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

if ($salesreport) {
	$reportfields = explode("|", $salesreport);
	$reporttype = $reportfields[0];
	$startyear = $reportfields[1];
	$startmonth = $reportfields[2];
	$startday = $reportfields[3];
	$toyear = $reportfields[4];
	$tomonth = $reportfields[5];
	$today = $reportfields[6];
	$orderby = $reportfields[7];
	$ascdesc = $reportfields[8];
	$generate = $reportfields[9];
	if ($msg = "activated") foreach ($_POST as $key => $value) if (strstr($key,"customer")) $customerid = str_replace("customer","",$key);
	if (strstr($SERVER_SOFTWARE, "IIS")) {
		echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=salesreport.php?msg=$msg&reporttype=$reporttype&customerid=$customerid&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate\"></head></html>";
		exit;
	} else header("Location: salesreport.php?msg=$msg&reporttype=$reporttype&customerid=$customerid&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate");
} else {
	if (strstr($SERVER_SOFTWARE, "IIS")) {
		echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=salesadmin.php?msg=sent&log=$logfilename&resultpage=$resultpage&namefilter=$namefilter&emailfilter=$emailfilter&admindisplayitems=$displayitems&recurring=$recurring\"></head></html>";
		exit;
	} else header ("Location: salesadmin.php?msg=sent&log=$logfilename&resultpage=$resultpage&namefilter=$namefilter&emailfilter=$emailfilter&admindisplayitems=$displayitems&recurring=$recurring");
}
?>