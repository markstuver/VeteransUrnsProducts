<?php
// AShop Sales Office
// Copyright 2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.php
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";

if (!isset($alreadylogin)) $alreadylogin = "";
if (!isset($retrylogin)) $retrylogin = "";
if (!isset($username)) $username = "";

// Initiate password hashing...
include "$ashoppath/includes/PasswordHash.php";
$passhasher = new PasswordHash(8, FALSE);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($QUERY_STRING == "logout") {
	@mysqli_query($db, "UPDATE emerchant_user SET activity=NULL, maillock=NULL WHERE sessionid='$sesid'");
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	SetCookie("sesid");
	if (strstr($SERVER_SOFTWARE, "IIS")) {
		echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=login.php\"></head></html>";
		exit;
	} else header("Location: login.php");
}

if (!$username || !$password) {
	echo "<HTML></HEAD><title>$ashopname - Sales Office Login</title><link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\"></HEAD>
	    <BODY bgcolor=\"#FFFFFF\" text=\"#FFFFFF\">
		<CENTER>
		<table width=\"100%\" height=\"100%\"><tr><td align=\"center\">
		<form action=\"login.php\" method=\"post\">
		<table class=\"loginform\" width=\"300\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"center\" valign=\"top\">
		<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
		<tr class=\"loginformheader\"><td align=\"center\"><img src=\"images/salesofficelogo.gif\" border=\"0\" alt=\"$ashopurl\"></a></td></tr>
		<tr><td align=\"center\"><span class=\"loginformheading\">
		<br>$ashopname</span>";
	if ($retrylogin == "true") echo "<br><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#CC0000\"><b>Wrong password!<br>Try again!</b></font>";
	if ($alreadylogin == "true") echo "<br><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#CC0000\"><b>That user is already logged in!<br>Login again to override.</b></font><input type=\"hidden\" name=\"override\" value=\"true\">";
	if ($QUERY_STRING == "locked") echo "<br><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#CC0000\"><b>The administration panel has been locked!<br>Try again in 2 minutes!</b></font>";
	echo "<br><br>
		</td></tr></table>
		<table width=\"200\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
		<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#000000\">Username: </font></td><td align=\"left\">
		<input type=\"text\" name=\"username\" value=\"$username\" class=\"loginforminput\">
		<script language=\"JavaScript\">document.forms[0].username.focus();</script></td></tr>
		<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#000000\">Password: </font></td><td align=\"left\">
		<input type=\"password\" name=\"password\" class=\"loginforminput\"></td></tr>
		<tr><td>&nbsp;</td><td align=\"right\">
		<br><input type=\"submit\" class=\"loginbutton\" value=\"Login\"><br><br></td></tr></table></td></tr></table>
		</form></td></tr></table></body></html>";
	exit;
}

$date = date("Y/m/d H:i:s");
$username=strtolower($username);

// Brute force attack protection...
$sql = "SELECT * FROM emerchant_user WHERE username = '$username'";
$result = @mysqli_query($db, $sql, $db);
if (@mysqli_num_rows($result)) {
	$lock = @mysqli_result($result, 0, "loginlock");
	$thispasswordhash = @mysqli_result($result, 0, "password");
	$thisactivity = @mysqli_result($result, 0, "activity");
	if ($thisactivity) $thisactivitytime = strtotime($thisactivity);
	else $thisactivitytime = 0;
	$inactivitytime = (strtotime($date) - $thisactivitytime)/60;
	if ($lock) {
		if (time() >= $lock + 120) {
			$sql = "UPDATE emerchant_user SET loginlock = '' WHERE username = '$username'";
			$result = @mysqli_query($db, $sql, $db);
		} else {
			@mysqli_close($db);
			header("Location: login.php?locked");
			exit;
		}
	}
	$passcheck = $passhasher->CheckPassword($password, $thispasswordhash);
	if (!$passcheck) {
		$loginattempts = @mysqli_result($result, 0, "activity");
		if (!$loginattempts || strstr($loginattempts,"/")) $loginattempts = 1;
		$loginattempts = $loginattempts + 1;
		$sql = "UPDATE emerchant_user SET activity='$loginattempts'";
		$result = @mysqli_query($db, $sql, $db);
		if ($loginattempts > 3) {
			$sql = "UPDATE emerchant_user SET loginlock = '".time()."', activity='' WHERE username = '$username'";
			$result = @mysqli_query($db, $sql, $db);
			@mysqli_close($db);
			$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$ashopemail","$ashopname - incorrect login attempts","Someone has just made three or more incorrect attempts to login to your AShop Sales Office system at $ashopurl/emerchant. The attempts were made from the following IP number: {$_SERVER["REMOTE_ADDR"]}.","$headers");
			header("Location: login.php?locked");
			exit;
		} else {
			@mysqli_close($db);
			header("Location: login.php?retrylogin=true");
			exit;
		}
	} else if ($thisactivity && $inactivitytime < 30 && !$override) {
		header("Location: login.php?alreadylogin=true&username=$username");
		exit;
	} else {
		$hash = md5($date.$username.$password."ashopisgreat");
		if ($mailcheckonlogin) $checkmail = "?checkmail=true";
		else $checkmail = "";
		$sql = "UPDATE emerchant_user SET sessionid='$hash', activity='$date', ip='{$_SERVER["REMOTE_ADDR"]}', loginlock='', mailcheck='".(time()-1250)."' WHERE username='$username'";
		@mysqli_query($db, $sql);
		@mysqli_close($db);
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		SetCookie("sesid", $hash);
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=index.php$checkmail\"></head></html>";
			exit;
		} else header("Location: index.php$checkmail");
	}
} else {
	@mysqli_close($db);
	header("Location: login.php?retrylogin=true");
	exit;
}
?>