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

error_reporting(E_ALL ^ E_NOTICE);
include "config.inc.php";
include "ashopfunc.inc.php";
$adminlang = "en";
include "language/$adminlang/login.inc.php";
if (!$adminpanelcolor) $adminpanelcolor = "7589e7";
else $adminpanelcolor = str_replace("#","","$adminpanelcolor");
unset($shop);
unset($userid);
if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
$p3psent = TRUE;
setcookie("userid");
setcookie("catmemberid");
setcookie("shopfilter");
setcookie("shop");
if (!empty($sesid) && !ashop_is_md5($sesid)) $sesid = "";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Redirect to product activation if the admin is already logged in...
if ($sesid && $prodactivate && is_numeric($prodactivate) && !$username && !$password) {
	$result = @mysqli_query($db, "SELECT userid FROM user WHERE sessionid='$sesid' AND userid='1'");
	if (@mysqli_num_rows($result)) {
		header("Location: editcatalogue.php?pid=$prodactivate");
		exit;
	}
}

// Handle logout...
if ($_SERVER["QUERY_STRING"] == "logout") {
	@mysqli_query($db, "UPDATE user SET activity=NULL, movelock='0' WHERE sessionid='$sesid'");
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("sesid");
	if (strstr($SERVER_SOFTWARE, "IIS")) {
		echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=login.php\"></head></html>";
		exit;
	} else header("Location: login.php");
}

// Check that the right URL is being used or redirect...
if (!isset($REQUEST_URI) and isset($_SERVER['SCRIPT_NAME'])) {
    $REQUEST_URI = $_SERVER['SCRIPT_NAME'];
    if (isset($_SERVER['QUERY_STRING']) and !empty($_SERVER['QUERY_STRING'])) $REQUEST_URI .= '?' . $_SERVER['QUERY_STRING'];
}
if ($_SERVER['HTTPS'] == "on") $url = "https://";
else $url = "http://";
$url .= $HTTP_HOST.$REQUEST_URI;
if (strpos($url,"$ashopurl/admin/login.php") === FALSE && strpos($url,"$ashopsurl/admin/login.php") === FALSE) {
	header("Location: $ashopurl/admin/login.php");
	exit;
}

$numberofusers = @mysqli_query($db, "SELECT * FROM user");

// Initiate password hashing...
include "$ashoppath/includes/PasswordHash.php";
$passhasher = new PasswordHash(8, FALSE);

// Check if the admin has never logged in before...
$admincheckresult = @mysqli_query($db, "SELECT password FROM user WHERE username='ashopadmin' AND sessionid IS NULL AND ip IS NULL");
if (@mysqli_num_rows($admincheckresult)) {
	$adminpasshash = @mysqli_result($admincheckresult,0,"password");
	$passcheck = $passhasher->CheckPassword("ashopadmin", $adminpasshash);
	if ($passcheck) $newadmin = TRUE;
	else $newadmin = FALSE;
} else $newadmin = FALSE;

if (!$username || !$password) {
	echo "<HTML><HEAD>".CHARSET."<title>$ashopname - ".ADMINPANELLOGIN."</title><meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\"><link rel=\"stylesheet\" href=\"admin.css\" type=\"text/css\"></HEAD>
	    <BODY bgcolor=\"#FFFFFF\" text=\"#FFFFFF\">
		<CENTER>
		<table width=\"100%\" height=\"100%\"><tr><td align=\"center\">
		<form action=\"login.php\" method=\"post\">
		<table class=\"loginform\" width=\"300\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"center\" valign=\"top\">
		<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
		<tr class=\"loginformheader\"><td align=\"center\"><img src=\"images/adminlogo.gif\" border=\"0\" alt=\"$ashopurl\"></td></tr>
		<tr><td align=\"center\"><span class=\"heading4\">
		<br>$ashopname";
	if ($retrylogin == "true") echo "<br><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#CC0000\"><b>".WRONGUSERORPASS."<br>".TRYAGAIN."</b></font>";
	if ($alreadylogin == "true") echo "<br><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#CC0000\"><b>".USERALREADYLOGGEDIN."</b></font><input type=\"hidden\" name=\"override\" value=\"true\">";
	if ($QUERY_STRING == "locked") echo "<br><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#CC0000\"><b>".ADMINPANELLOCKED."</b></font>";
	if ($newadmin == TRUE) echo "<br><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#228822\"><b>".FIRSTLOGINWELCOME."</b></font>";
	echo "<br><br></span>
		</td></tr></table>
		<table width=\"200\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
	if (@mysqli_num_rows($numberofusers) > 1) echo "
		<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#000000\"><span class=\"formlabel\">".USERNAME.": </span></font></td><td align=\"left\">
		<input type=\"text\" name=\"username\" value=\"$username\" class=\"loginforminput\"><script language=\"JavaScript\">document.forms[0].username.focus();</script></td></tr>";
	else echo "<input type=\"hidden\" name=\"username\" value=\"ashopadmin\" class=\"loginforminput\">";
	echo "
		<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#000000\"><span class=\"formlabel\">".PASSWORD.": </span></font></td><td align=\"left\">
		<input type=\"password\" name=\"password\" class=\"loginforminput\">";
	if (@mysqli_num_rows($numberofusers) == 1) echo "<script language=\"JavaScript\">document.forms[0].password.focus();</script>";
	echo "</td></tr>";
	echo "<tr><td>&nbsp;</td><td align=\"right\"><br>";
		if ($activate != "") echo "<input type=\"hidden\" name=\"activate\" value=\"$activate\">";
		else if ($process != "") echo "<input type=\"hidden\" name=\"process\" value=\"$process\">";
		else if ($prodactivate != "") echo "<input type=\"hidden\" name=\"prodactivate\" value=\"$prodactivate\">";
		echo "<input type=\"submit\" value=\"".LOGIN."\"><br>
	</td></tr>
	<tr><td colspan=\"2\" align=\"center\"><br><span class=\"loginformlink\"><a href=\"sendpass.php\">".FORGOTYOURPASSWORD."</a></span><br><br></td></tr>";
	echo "</table></td></tr></table>
		</form></td></tr></table></body></html>";
	@mysqli_close($db);
	exit;
}

$date = date("Y/m/d H:i:s");
$username=strtolower($username);

// Brute force attack protection...
$sql = "SELECT * FROM user WHERE username = '$username'";
$result = @mysqli_query($db, $sql);
if (@mysqli_num_rows($result)) { 
	$lock = @mysqli_result($result, 0, "loginlock");
	$thispasswordhash = @mysqli_result($result, 0, "password");
	$thisactivity = @mysqli_result($result, 0, "activity");
	if ($thisactivity) $thisactivitytime = strtotime($thisactivity);
	else $thisactivitytime = 0;
	$inactivitytime = (strtotime($date) - $thisactivitytime)/60;
	if ($lock) {
		if (time() >= $lock + 120) {
			$sql = "UPDATE user SET loginlock = '' WHERE username = '$username'";
			$result = @mysqli_query($db, $sql);
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
		$sql = "UPDATE user SET activity='$loginattempts'";
		$result = @mysqli_query($db, $sql);
		if ($loginattempts > 3) {
			$sql = "UPDATE user SET loginlock = '".time()."', activity='' WHERE username = '$username'";
			$result = @mysqli_query($db, $sql);
			@mysqli_close($db);
			$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$ashopemail","$ashopname - ".INCORRECTLOGINATTEMPTS,INCORRECTLOGINNOTICE1." $ashopurl/admin. ".INCORRECTLOGINNOTICE2.": {$_SERVER["REMOTE_ADDR"]}.","$headers");
			header("Location: login.php?locked");
			exit;
		} else {
			@mysqli_close($db);
			if ($activate) header("Location: login.php?retrylogin=true&activate=$activate");
			else if ($prodactivate) header("Location: login.php?retrylogin=true&prodactivate=$prodactivate");
			else header("Location: login.php?retrylogin=true");
		}
	} else if ($thisactivity && $inactivitytime < 20 && !$override) {
		header("Location: login.php?alreadylogin=true&username=$username&activate=$activate");
		exit;
	} else {
		$hash = md5($date.$username.$password."ashopisgreat");
		$sql = "UPDATE user SET sessionid='$hash', activity='$date', ip='{$_SERVER["REMOTE_ADDR"]}', loginlock='', movelock='0' WHERE username='$username'";
		@mysqli_query($db, $sql);
		@mysqli_close($db);
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		SetCookie("sesid", $hash);
		if ($activate != "") {
			if (strstr($SERVER_SOFTWARE, "IIS")) {
				echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=activate.php?orderid=$activate\"></head></html>";
				exit;
			} else header ("Location: activate.php?orderid=$activate");			
		} else if ($process != "") {
			if (strstr($SERVER_SOFTWARE, "IIS")) {
				echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$ashopsurl/admin/process.php?orderid=$process&sesid=$hash\"></head></html>";
				exit;
			} else header ("Location: $ashopsurl/admin/process.php?orderid=$process&sesid=$hash");			
		} else if ($prodactivate != "") {
			if (strstr($SERVER_SOFTWARE, "IIS")) {
				echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=editcatalogue.php?pid=$prodactivate\"></head></html>";
				exit;
			} else header ("Location: editcatalogue.php?pid=$prodactivate");			
		} else { 
			if (strstr($SERVER_SOFTWARE, "IIS")) {
				echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=index.php\"></head></html>";
				exit;
			} else header("Location: index.php");
		}
	}
} else header("Location: login.php?retrylogin=true");
?>