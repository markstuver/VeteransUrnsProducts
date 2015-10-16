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

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";

// Check that this is really from micropayment...
$micropayment_ips = array("193.159.183.235", "193.159.183.244");
if (!in_array($_SERVER["REMOTE_ADDR"],$micropayment_ips)) exit;

// Validate variables...
if (isset($invoice) && !preg_match("/^[A-Za-z0-9]*$/", $invoice)) $invoice = "";
if (isset($auth) && !ashop_is_md5($auth)) $auth = "";
if (isset($amount) && !is_numeric($amount)) $amount = "";
else $amount = number_format($amount,2,'.','');
if (isset($paymethod) && $paymethod != "micropaymenteb2p" && $paymethod != "micropaymentcc" && $paymethod != "micropaymentdd" && $paymethod != "micropaymentbt") $paymethod = "";

// Check that the input variables are set...
if (!isset($function) || ($function != "billing" && $function != "payin" && $function != "init") || !isset($invoice) || !isset($auth) || !isset($amount) || !isset($paymethod)) exit;

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get payment option information...
$result = @mysqli_query($db,"SELECT payoptionid,transactionkey FROM payoptions WHERE gateway='$paymethod'");
if (!@mysqli_num_rows($result)) exit;
$row = @mysqli_fetch_array($result);
$payoption = $row["payoptionid"];
$secret = $row["transactionkey"];

// Send return message on unpaid bank transfers...
if ($paymethod == "micropaymentbt") {
	if (($paystatus != "PAID" && $paystatus != "OVERPAID") || $function != "billing") {
		echo "status=ok\nurl=$ashopurl/order.php?payopt=$payoption&ofinv=$invoice";
		exit;
	}
}

// Get payment details and ask customer to confirm...
$result = @mysqli_query($db,"SELECT * FROM orders WHERE orderid='$invoice'");
$row = @mysqli_fetch_array($result);
$shippingid = $row["customerid"];
$shippingresult = @mysqli_query($db,"SELECT customerid FROM shipping WHERE shippingid='$shippingid'");
$customerid = @mysqli_result($shippingresult,0,"customerid");
$customerresult = @mysqli_query($db,"SELECT * FROM customer WHERE customerid='$customerid'");
$customerrow = @mysqli_fetch_array($customerresult);
$affiliate = $row["affiliateid"];
$firstname = $customerrow["firstname"];
$lastname = $customerrow["lastname"];
$email = $customerrow["email"];
$address = $customerrow["address"];
$city = $customerrow["city"];
$zip = $customerrow["zip"];
$state = $customerrow["state"];
$country = $customerrow["country"];
$phone = $customerrow["phone"];
$checkamount = $row["price"];
$checkamount = number_format(($checkamount)*100,0,'','');
if ($amount != $checkamount) exit;
$products = $payoption."ashoporderstring".$row["products"];
$description = $row["description"];
$remoteorderid = $auth;
$securitycheck = md5("$remoteorderid$secret");
$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=success&invoice=$invoice&scode=$securitycheck&amount={$row["price"]}&products=$products&description=$description&affiliate=$affiliate";
if ($paymentstatus == "Pending") $querystring .= "&pendingpayment=true";
if (strpos($ashopurl, "/", 8)) {
	$urlpath = "/".substr($ashopurl, strpos($ashopurl, "/", 8)+1);
	$urldomain = substr($ashopurl, 0, strpos($ashopurl, "/", 8));
} else {
	$urlpath = "/";
	$urldomain = $ashopurl;
}
if ($urlpath == "/") $scriptpath = "order.php";
else $scriptpath = "/order.php";
$urldomain = str_replace("http://", "", $urldomain);
$header = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
$fp = @fsockopen ("$urldomain", 80);
$response = "";
if ($fp) {
	fputs ($fp, $header . $querystring);
	while (!feof($fp)) $response .= fread ($fp, 8192);
	fclose ($fp);
}
echo "status=ok\nurl=$ashopurl/order.php?payopt=$payoption&ofinv=$invoice";
exit;
?>