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

include "$ashoppath/admin/config.inc.php";
include "$ashoppath/admin/ashopfunc.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get login details...
$ipnumber = $_SERVER["REMOTE_ADDR"];
$email = $_SERVER["PHP_AUTH_USER"];
$password = $_SERVER["PHP_AUTH_PW"];
$logintimestamp = time()+$timezoneoffset;
$logindate = date("Y-m-d H:i:s", $logintimestamp);
$referrer = $_SERVER["HTTP_REFERER"];
$subscriptiondir = getcwd();
$subscriptiondir = str_replace("\\","/",$subscriptiondir);
$subscriptiondir = strtolower($subscriptiondir);
$ashoppath = str_replace("\\","/",$ashoppath);
$ashoppath = strtolower($ashoppath);
$subscriptiondir = str_replace($ashoppath,"",$subscriptiondir);
if (substr($subscriptiondir,0,1) == "/") $subscriptiondir = substr($subscriptiondir,1);

// Locate the corresponding order...
$result = @mysqli_query($db, "SELECT productid, length FROM product WHERE subscriptiondir='$subscriptiondir'");
while ($row = @mysqli_fetch_array($result)) {
	$checkproductid = $row["productid"];
	$length = $row["length"];
	$result2 = @mysqli_query($db, "SELECT orderid, customerid, paid FROM orders WHERE password='$password' AND (products LIKE '%b$checkproductid"."a%' OR products LIKE '%b$checkproductid"."d%') ORDER BY date DESC LIMIT 1");
	if (@mysqli_num_rows($result2)) {
		$productid = $checkproductid;
		$orderid = @mysqli_result($result2,0,"orderid");
		$customerid = @mysqli_result($result2,0,"customerid");
		$orderdate = @mysqli_result($result2,0,"paid");
	}
}

// Calculate the current length...
if (!empty($orderdate)) {
	$orderdatearray = explode(" ",$orderdate);
	$datearray = explode("-",$orderdatearray[0]);
	$timearray = explode(":",$orderdatearray[1]);
	$orderyear = $datearray[0];
	$ordermonth = $datearray[1];
	$orderday = $datearray[2];
	$orderhour = $timearray[0];
	$orderminute = $timearray[1];
	$ordersecond = $timearray[2];
	$ordertimestamp = mktime($orderhour,$orderminute,$ordersecond,$ordermonth,$orderday,$orderyear);
	$lengthinseconds = $logintimestamp - $ordertimestamp;
	$subscrlength = floor($lengthinseconds/86400);
	$remainingdays = $length-$subscrlength;
}

// Check previous visit...
$result = @mysqli_query($db, "SELECT ipnumber, logindate FROM membershiplog WHERE productid='$productid' AND email='$email' ORDER BY logindate DESC LIMIT 1");
$previouslogin = @mysqli_result($result,0,"logindate");
$previousipnumber = @mysqli_result($result,0,"ipnumber");
if (!empty($previouslogin)) {
	$previousdatearray = explode(" ",$previouslogin);
	$datearray = explode("-",$previousdatearray[0]);
	$timearray = explode(":",$previousdatearray[1]);
	$previousyear = $datearray[0];
	$previousmonth = $datearray[1];
	$previousday = $datearray[2];
	$previoushour = $timearray[0];
	$previousminute = $timearray[1];
	$previoussecond = $timearray[2];
	$previoustimestamp = mktime($previoushour,$previousminute,$previoussecond,$previousmonth,$previousday,$previousyear);
	// If the IP has changed and the last login was within an hour, refuse access...
	if ($previousipnumber != $ipnumber) {
		if ($logintimestamp-$previoustimestamp < 3600) $orderid = "";
	} else {
		if ($logintimestamp-$previoustimestamp < 3600) exit;
	}
}

// Redirect visitors who should not have access...
if (empty($orderid) || empty($orderdate) || $subscrlength > $length) {
	echo "window.location = \"$ashopurl\";";
	exit;
}

// Register this visit...
@mysqli_query($db, "INSERT INTO membershiplog	(productid,	customerid,	logindate, ipnumber, email, password, remainingdays) VALUES ('$productid','$customerid','$logindate','$ipnumber','$email','$password','$remainingdays')");
?>