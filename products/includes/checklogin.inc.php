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

if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Validate variables...
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

if (empty($_COOKIE["customersessionid"])) {
	header("Location: $ashopurl/signupform.php");
	exit;
}

$date = date("Y/m/d H:i:s");
$sql="SELECT * FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'";
$result = @mysqli_query($db, $sql);
$activity = @mysqli_result($result,0,"activity");
if ($activity) $activitytime = strtotime($activity);
else $activitytime = 0;
$inactivitytime = (strtotime($date) - $activitytime)/60;
if (@mysqli_num_rows($result) == 1) {

	$ashop_customerfirstname = @mysqli_result($result, 0, "firstname");
	$ashop_customerlastname = @mysqli_result($result, 0, "lastname");
	$ashop_customeremail = @mysqli_result($result, 0, "email");
	$ashop_customerallowemail = @mysqli_result($result, 0, "allowemail");
	$ashop_customeraddress = @mysqli_result($result, 0, "address");
	$ashop_customerstate = @mysqli_result($result, 0, "state");
	$ashop_customerzip = @mysqli_result($result, 0, "zip");
	$ashop_customercity = @mysqli_result($result, 0, "city");
	$ashop_customercountry = @mysqli_result($result, 0, "country");
	$ashop_customerphone = @mysqli_result($result, 0, "phone");
	$ashop_customerextrainfo = @mysqli_result($result, 0, "extrainfo");
	$ashop_customerid = @mysqli_result($result, 0, "customerid");
	$ashop_customerusername = @mysqli_result($result, 0, "username");
	$ashop_customerpassword = @mysqli_result($result, 0, "password");
	$ashop_customerbusinessname = @mysqli_result($result, 0, "businessname");
	$ashop_customerlanguage = @mysqli_result($result, 0, "preflanguage");
	$sql="SELECT * FROM shipping WHERE customerid='$ashop_customerid'";
	$shippingresult = @mysqli_query($db, "$sql");
	$ashop_shippingbusiness = @mysqli_result($shippingresult, 0, "shippingbusiness");
	$ashop_shippingfirstname = @mysqli_result($shippingresult, 0, "shippingfirstname");
	$ashop_shippinglastname = @mysqli_result($shippingresult, 0, "shippinglastname");
	$ashop_shippingaddress = @mysqli_result($shippingresult, 0, "shippingaddress");
	$ashop_shippingaddress2 = @mysqli_result($shippingresult, 0, "shippingaddress2");
	$ashop_shippingzip = @mysqli_result($shippingresult, 0, "shippingzip");
	$ashop_shippingcity = @mysqli_result($shippingresult, 0, "shippingcity");
	$ashop_shippingstate = @mysqli_result($shippingresult, 0, "shippingstate");
	$ashop_customervat = @mysqli_result($shippingresult, 0, "vat");
	$ashop_shippingcountry = @mysqli_result($shippingresult, 0, "shippingcountry");

	$sql = "UPDATE customer SET activity = '$date' WHERE sessionid = '{$_COOKIE["customersessionid"]}'";
	@mysqli_query($db, $sql);
	@mysqli_close($db);
} else {
    @mysqli_close($db);
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("customersessionid","",time()-10800,"/");
	header("Location: $ashopurl/login.php");
}
?>