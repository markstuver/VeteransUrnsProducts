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

$sessiondb = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if (!empty($affiliatesesid) && !preg_match("/^[0-9a-f]{32}$/", $affiliatesesid)) $affiliatesesid = "";

$date = date("Y/m/d H:i:s");
$sql = "SELECT * FROM affiliate WHERE sessionid = '$affiliatesesid'";
$result = @mysqli_query($sessiondb, $sql);
$activity = @mysqli_result($result,0,"activity");
$affiliateuser = @mysqli_result($result,0,"user");
if ($activity) $activitytime = strtotime($activity);
else $activitytime = 0;
$inactivitytime = (strtotime($date) - $activitytime)/60;
if ((@mysqli_num_rows($result) == 1) && ($inactivitytime < 30)) {
	$sql = "UPDATE affiliate SET activity = '$date' WHERE sessionid = '$affiliatesesid'";
	@mysqli_query($sessiondb, $sql);

	// Check if this is a sales rep...
	$salesreplink = "";
	$salesrepresult = @mysqli_query($db, "SELECT username FROM emerchant_user WHERE username='$affiliateuser'");
	if (@mysqli_num_rows($salesrepresult)) {
		$salesreplink = "<td width=\"100\" align=\"right\"><span class=\"ashopaffiliateheader\"><a href=\"../emerchant/index.php\" target=\"_blank\">Sales Office</a></span></td>";
		$sql = "UPDATE emerchant_user SET activity = '$date' WHERE sessionid = '$sesid'";
		@mysqli_query($sessiondb, $sql);
	}

	@mysqli_close($sessiondb);
} else {
    @mysqli_close($sessiondb);
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("affiliatesesid","",time()-10800,"/");
	header("Location: login.php");
}
?>