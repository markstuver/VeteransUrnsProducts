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

include "config.inc.php";
include "ashopfunc.inc.php";
include "ashopconstants.inc.php";
require_once 'ideal.class.php';

// Validate transaction_id...
if (!empty($transaction_id) && !ashop_is_md5($transaction_id)) $transaction_id = "";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get payment option information...
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid='$payoption'";
else $sql = "SELECT * FROM payoptions WHERE gateway='ideal'";
$result = @mysqli_query($db, "$sql");
$payoption = @mysqli_result($result, 0, "payoptionid");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$secret = @mysqli_result($result, 0, "secret");
$testmode = @mysqli_result($result, 0, "testmode");
$iDEAL = new Mollie_iDEAL_Payment ($merchantid);
if ($testmode) $iDEAL->setTestmode(true);

// Process the order...
if (!empty($transaction_id)) {
	$result = @mysqli_query($db, "SELECT orderid FROM orders WHERE remoteorderid='$transaction_id'");
	$orderid = @mysqli_result($result,0,"orderid");
	$sql = "SELECT * FROM pendingorders WHERE orderid='$orderid'";
	$result = @mysqli_query($db, "$sql");
	$row = @mysqli_fetch_array($result);
	$email = $row["email"];
	$firstname = $row["firstname"];
	$lastname = $row["lastname"];
	$address = $row["address"];
	$city = $row["city"];
	$zip = $row["zip"];
	$state = $row["state"];
	$country = $row["country"];
	$phone = $row["phone"];
	$amount = $row["amount"];
	$products = $row["products"];
	$description = $row["description"];
	$affiliate = $row["affiliate"];
	$remoteorderid=$transaction_id;
	$securitycheck = md5("$remoteorderid$secret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$orderid'";
	$result = @mysqli_query($db, "$sql");
	$iDEAL->checkPayment($transaction_id);
	$cart_cost = $iDEAL->getAmount();
	$cart_cost = number_format($cart_cost/100,2,'.','');
	$store_id = $iDEAL->getPartnerId();
	$paidstatus = $iDEAL->getPaidStatus();
	$bankstatus = $iDEAL->getBankStatus();

	if ($paidstatus && $store_id == $merchantid && $cart_cost == $amount) {
		$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=Success&invoice=$orderid&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
		$fp = @fsockopen ("$urldomain", 80, $errno, $errstr, 10);
		if ($fp) {
			fputs ($fp, $header . $querystring);
			$response = "";
			while (!feof($fp)) $response .= fread ($fp, 8192);
			fclose ($fp);
		}
		@fclose ($fp);
		@mysqli_close($db);
	} else if ($bankstatus == "Cancelled" || $bankstatus == "Expired" || $bankstatus == "Failure") @mysqli_query($db, "UPDATE orders SET status='$bankstatus' WHERE orderid='$orderid'");
	exit;
}

// Redirect the customer to iDEAL bank for payment...
if ($invoice && $bank_id) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
	if ($address2) $address .= ", $address2";
	$amount = number_format($amount,2,'.','');

	// Get the country code...
	foreach ($countries as $shortcountry => $longcountry) if ($country == $longcountry) $country = $shortcountry;

	// Payment should be in cents...
	$ideal_amount = number_format(($amount)*100,0,'','');

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");

	if ($iDEAL->createPayment($bank_id, $ideal_amount, $description, "$ashopurl/order.php?payopt=$payoption", "$ashopurl/admin/ideal.php")) {
		$remoteorderid = $iDEAL->getTransactionId();
		if (!empty($remoteorderid)) {
			@mysqli_query($db, "UPDATE orders SET remoteorderid='$remoteorderid' WHERE orderid='$invoice'");
			header("Location: " . $iDEAL->getBankURL());
			exit;
		} else echo "An error occurred. Please contact the webshop. Error message: ".$iDEAL->getErrorMessage();
	} else echo "An error occurred. Please contact the webshop. Error message: ".$iDEAL->getErrorMessage();
}
?>