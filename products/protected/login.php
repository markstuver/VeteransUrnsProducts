<?php
// AShop Deluxe
// Copyright 2002-2008 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "config.inc.php";
include "$ashopdeluxepath/admin/config.inc.php";
include "$ashopdeluxepath/admin/ashopfunc.inc.php";

if ($QUERY_STRING == "logout") {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	SetCookie("ashopsession");
	header("Location: login.html");
	exit;
}

if (!$subscriptionid || !$username || !$password || !$redirect) exit;

// Open database...
$db = @mysql_connect("$databaseserver", "$databaseuser", "$databasepasswd");
@mysql_select_db("$databasename",$db);


$date = date("Y/m/d H:i:s");
$username=strtolower($username);

// Get details about this subscription...
$sql = "SELECT * FROM product WHERE prodtype = 'subscription' AND productid='$subscriptionid'";
$result = @mysql_query($sql, $db);
$length = @mysql_result($result, 0, "length");
$firsttime = time()+$timezoneoffset - ($length * 86400);

$sql = "SELECT orders.paid FROM customer, orders WHERE (products LIKE '%b$subscriptionid"."a%' OR products LIKE '%b$subscriptionid"."d%') AND customer.email = '$username' AND orders.password = '$password' AND customer.customerid=orders.customerid";
$result = @mysql_query($sql, $db);
if (!@mysql_num_rows($result)) {
	@mysql_close($db);
    header("Location: retrylogin.html");
} else {
	$orderdatearray = explode(" ",@mysql_result($result, 0, "paid"));
    @mysql_close($db);
	$orderdate = explode ("-",$orderdatearray[0]);
	$ordertime = explode (":",$orderdatearray[1]);
	$orderedtimestamp = mktime($ordertime[0],$ordertime[1],$ordertime[2],$orderdate[1],$orderdate[2],$orderdate[0]);
	if ($orderedtimestamp < $firsttime) exit;
	else {
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		$sessionstring = ashop_endecrypt("ashopsession$ashoppath","$subscriptionid|$username|$password");
		SetCookie("ashopsession", "$sessionstring");
		header("Location: $redirect");
	}
}
?>