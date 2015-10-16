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

if (empty($databasepasswd)) include "config.inc.php";
if (!empty($sesid) && !preg_match("/^[0-9a-f]{32}$/", $sesid)) $sesid = "";
if (!empty($emsesid) && !preg_match("/^[0-9a-f]{32}$/", $emsesid)) $emsesid = "";

$sessiondb = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$date = date("Y/m/d H:i:s");
$emsesid = "";
if ($emsesid) {
	$sql = "SELECT * FROM emerchant_user WHERE username='admin' AND sessionid = '$emsesid'";
	$result = @mysqli_query($sessiondb, $sql);
	$activity = @mysqli_result($result,0,"activity");
	if ($activity) $thisactivitytime = strtotime($activity);
	else $thisactivitytime = 0;
	$inactivitytime = (strtotime($date) - $thisactivitytime)/60;
	if (@mysqli_num_rows($result) == 1) {
		@mysqli_query($sessiondb, "UPDATE user SET sessionid='$emsesid' WHERE username='ashopadmin'");
		$sesid = $emsesid;
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		SetCookie("sesid", $emsesid);
	}
}
$sql = "SELECT * FROM user WHERE sessionid = '$sesid'";
$result = @mysqli_query($sessiondb, $sql);
if (!$emsesid) {
	$activity = @mysqli_result($result,0,"activity");
	if ($activity) $activitytime = strtotime($activity);
	else $activitytime = 0;
	$inactivitytime = (strtotime($date) - $activitytime)/60;
}
if (@mysqli_num_rows($result) == 1 && ($inactivitytime < 20 || $noinactivitycheck == "true")) {
	unset($shop);
	$userid = @mysqli_result($result,0,"userid");
	$isadmin = @mysqli_result($result,0,"admin");
	$mallmode = @mysqli_result($result,0,"mallmode");
	if ($mallmode == "deactivated") {
		$memberprodmanage = "";
		$advancedmallmode = "";
	} else if ($mallmode == "simple") {
		$memberprodmanage = "1";
		$advancedmallmode = "";
	} else if ($mallmode == "advanced") {
		$memberprodmanage = "1";
		$advancedmallmode = "1";
	}
	if ($isadmin == "1") $userid = 1;
	if ($userid != 1) $username = @mysqli_result($result,0,"username");
	else $username = "ashopadmin";
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	SetCookie("userid",$userid);
	$shop = $userid;
	$sql = "UPDATE user SET activity = '$date' WHERE sessionid = '$sesid'";
	@mysqli_query($sessiondb, $sql);
	@mysqli_close($sessiondb);
} else {
    @mysqli_close($sessiondb);
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	SetCookie("userid");
	SetCookie("sesid");
	if ($activate) $activatestring = "?activate=$activate";
	if (strstr($SERVER_SOFTWARE, "IIS")) {
		echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=login.php$activatestring\"></head></html>";
		exit;
	} else {
		header("Location: login.php$activatestring");
		exit;
	}
}
?>