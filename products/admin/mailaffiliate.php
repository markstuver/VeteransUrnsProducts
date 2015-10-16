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
	setcookie("prefaffmailformat","$mailformat", mktime(0,0,0,12,1,2020));
}

// Initiate mass mailing...
if ($mailall && !$recurring) {
	$result = @mysqli_query($db, "SELECT * FROM mailing WHERE type='affiliate'");
	if (!@mysqli_num_rows($result)) {
		if ($subject && $message) {
			@mysqli_query($db, "INSERT INTO mailing (type, format, subject, message) VALUES ('affiliate','$mailformat','$subject','$message')");
			header("Location: mailingadmin.php?type=affiliate");
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
		@mysqli_query($db, "UPDATE mailing SET logfile='$logfilename' WHERE type='affiliate'");
		$logfp = @fopen("$ashoppath/previews/$logfilename", "w");
		if ($logfp) @fwrite($logfp, "<html><body>The message: $subject<br>was sent on $logdate to the following recipients:<br><br>");
	}
}

// Convert line breaks to make the message readable in regular email clients...
$message = str_replace("\r\n","\n",$message);
$message = str_replace("\n\r","\n",$message);
//$message = str_replace("\n","\n\r",$message);

// Set current date and time...
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Mail to selected affiliates...
if ($mailall) {
	// Check if this is a resumed mailing...
	$resumeresult = @mysqli_query($db, "SELECT MAX(recipientid) as lastrecipient FROM maillog WHERE mailingid='$mailingid'");
	if (@mysqli_num_rows($resumeresult)) $lastrecipient = @mysqli_result($resumeresult,0,"lastrecipient");
	if ($lastrecipient) $sql = "SELECT * FROM affiliate WHERE affiliateid>$lastrecipient ORDER BY affiliateid";
	else $sql="SELECT * FROM affiliate ORDER BY affiliateid";
	$result = @mysqli_query($db, "$sql");
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$firstname = @mysqli_result($result, $i, "firstname");
		$lastname = @mysqli_result($result, $i, "lastname");
		$email = @mysqli_result($result, $i, "email");
		$affiliateid = @mysqli_result($result, $i, "affiliateid");
		$address = @mysqli_result($result, $i, "address");
		$state = @mysqli_result($result, $i, "state");
		$city = @mysqli_result($result, $i, "city");
		$country = @mysqli_result($result, $i, "country");
		$url = @mysqli_result($result, $i, "url");
		$phone = @mysqli_result($result, $i, "phone");
		$referralcode = @mysqli_result($result, $i, "referralcode");
		$user = @mysqli_result($result, $i, "user");
		$password = @mysqli_result($result, $i, "password");
		$sendmessage = str_replace("%firstname%",$firstname,$message);
		$sendmessage = str_replace("%lastname%",$lastname,$sendmessage);
		$sendmessage = str_replace("%email%",$email,$sendmessage);
		$sendmessage = str_replace("%affiliateid%",$affiliateid,$sendmessage);
		$sendmessage = str_replace("%affiliatelink%","$ashopurl/affiliate.php?id=$affiliateid",$sendmessage);
		$sendmessage = str_replace("%address%",$address,$sendmessage);
		$sendmessage = str_replace("%state%",$state,$sendmessage);
		$sendmessage = str_replace("%zip%",$zip,$sendmessage);
		$sendmessage = str_replace("%city%",$city,$sendmessage);
		$sendmessage = str_replace("%country%",$country,$sendmessage);
		$sendmessage = str_replace("%url%",$url,$sendmessage);
		$sendmessage = str_replace("%phone%",$phone,$sendmessage);
		$sendmessage = str_replace("%referralcode%",$referralcode,$sendmessage);
		$sendmessage = str_replace("%password%",$password,$sendmessage);
		$sendmessage = str_replace("%username%",$user,$sendmessage);
		$checklog = @mysqli_query($db, "SELECT * FROM maillog WHERE email='$email' AND mailingid='$mailingid'");
		if (!@mysqli_num_rows($checklog)) {
			// Check if this mailing is paused or if another process has already started taking care of it...
			$checksessionresult = @mysqli_query($db, "SELECT sessionkey, paused FROM mailing WHERE mailingid='$mailingid'");
			$checksessionkey = @mysqli_result($checksessionresult,0,"sessionkey");
			$paused = @mysqli_result($checksessionresult,0,"paused");
			if ($checksessionkey != $sessionkey || $paused) exit;
			$timestampsql = "UPDATE mailing SET timestamp='".time()."' WHERE mailingid='$mailingid'";
			@mysqli_query($db, $timestampsql);
			@mysqli_query($db, "INSERT INTO maillog (mailingid, email, recipientid) VALUES ('$mailingid', '$email','$affiliateid')");
			if ($mailformat == "html") $headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			else $headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\n";
			if ($mailformat == "pm") {
				@mysqli_query($db, "INSERT INTO affiliatepm (toaffiliateid, fromaffiliateid, sentdate, subject, message) VALUES ('$affiliateid', '-1', '$date', '$subject', '$sendmessage')");
			} else {
				@ashop_mail("$email","$subject","$sendmessage","$headers");
			}

			// Log this email...
			if ($logfp) {
				if ($firstname) {
					if ($lastname) $fullname = "$firstname $lastname";
					else $fullname = $firstname;
				} else if ($lastname) $fullname = $lastname;
				else $fullname = "Unknown";
				@fwrite($logfp, "$affiliateid: $fullname &lt;$email&gt;<br>");
			}
			// Sleep for a while to avoid choking the server...
			usleep($massmailthrottle);
		}
	}
	// Delete this mailing since it is completed...
	@mysqli_query($db, "DELETE FROM mailing WHERE mailingid='$mailingid'");
	@mysqli_query($db, "DELETE FROM maillog WHERE mailingid='$mailingid'");
} else {
	foreach ($_POST as $key=>$value) {
		if (strstr($key,"affiliate") && $value == "on") {
			$affiliateid = str_replace("affiliate","",$key);
			$affiliateid = trim($affiliateid);
			$result = @mysqli_query($db, "SELECT * FROM affiliate WHERE affiliateid='$affiliateid'");
			$firstname = @mysqli_result($result, $i, "firstname");
			$lastname = @mysqli_result($result, $i, "lastname");
			$email = @mysqli_result($result, $i, "email");
			$address = @mysqli_result($result, $i, "address");
			$state = @mysqli_result($result, $i, "state");
			$city = @mysqli_result($result, $i, "city");
			$country = @mysqli_result($result, $i, "country");
			$url = @mysqli_result($result, $i, "url");
			$phone = @mysqli_result($result, $i, "phone");
			$referralcode = @mysqli_result($result, $i, "referralcode");
			$user = @mysqli_result($result, $i, "user");
			$password = @mysqli_result($result, $i, "password");
			$sendmessage = str_replace("%firstname%",$firstname,$message);
			$sendmessage = str_replace("%lastname%",$lastname,$sendmessage);
			$sendmessage = str_replace("%email%",$email,$sendmessage);
			$sendmessage = str_replace("%affiliateid%",$affiliateid,$sendmessage);
			$sendmessage = str_replace("%affiliatelink%","$ashopurl/affiliate.php?id=$affiliateid",$sendmessage);
			$sendmessage = str_replace("%address%",$address,$sendmessage);
			$sendmessage = str_replace("%state%",$state,$sendmessage);
			$sendmessage = str_replace("%zip%",$zip,$sendmessage);
			$sendmessage = str_replace("%city%",$city,$sendmessage);
			$sendmessage = str_replace("%country%",$country,$sendmessage);
			$sendmessage = str_replace("%url%",$url,$sendmessage);
			$sendmessage = str_replace("%phone%",$phone,$sendmessage);
			$sendmessage = str_replace("%referralcode%",$referralcode,$sendmessage);
			$sendmessage = str_replace("%password%",$password,$sendmessage);
			$sendmessage = str_replace("%username%",$user,$sendmessage);
			if ($mailformat == "html") $headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			else $headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\n";

			if ($mailformat == "pm") {
				$sendpm = str_replace("\n","<br>",$sendmessage);
				@mysqli_query($db, "INSERT INTO affiliatepm (toaffiliateid, fromaffiliateid, sentdate, subject, message) VALUES ('$affiliateid', '-1', '$date', '$subject', '$sendpm')");
			} else {
				@ashop_mail("$email","$subject","$sendmessage","$headers");

				// Log this email...
				if ($logfp) {
					if ($firstname) {
						if ($lastname) $fullname = "$firstname $lastname";
						else $fullname = $firstname;
					} else if ($lastname) $fullname = $lastname;
					else $fullname = "Unknown";
					@fwrite($logfp, "$affiliateid: $fullname &lt;$email&gt;<br>");
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
	echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=affiliateadmin.php?msg=sent&log=$logfilename&resultpage=$resultpage&namefilter=$namefilter&urlfilter=$urlfilter&admindisplayitems=$displayitems\"></head></html>";
	exit;
} else header ("Location: affiliateadmin.php?msg=sent&log=$logfilename&resultpage=$resultpage&namefilter=$namefilter&urlfilter=$urlfilter&admindisplayitems=$displayitems");
?>