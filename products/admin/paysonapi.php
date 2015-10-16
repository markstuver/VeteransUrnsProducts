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

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;

// Get payment option information...
$sql = "SELECT * FROM payoptions WHERE gateway='paysonapi'";
$result = @mysqli_query($db, "$sql");
$gateway = "paysonapi";
include "gatewayssek/paysonapi.gw";
$payoption = @mysqli_result($result, 0, "payoptionid");
$merchantid = @mysqli_result($result, 0, "merchantid");
$payoptionsecret = @mysqli_result($result, 0, "secret");
$transactionkey = @mysqli_result($result, 0, "transactionkey");
$payoptionfee = @mysqli_result($result, 0, "fee");
$testmode = @mysqli_result($result, 0, "testmode");

// Include the right PaySon library...
if ($testmode) require_once 'paysonlib/paysonapiTest.php';
else require_once 'paysonlib/paysonapi.php';

// Setup API object...
$credentials = new PaysonCredentials("$payoptionsecret", "$transactionkey");
$api = new PaysonApi($credentials);

if (isset($_POST["custom"]) && isset($_POST["purchaseId"]) && isset($_POST["type"]) && isset($_POST["status"])) {
	$response = $api->validate($_POST);
	if(!$response->isVerified()) exit;
	$sql = "SELECT * FROM pendingorders WHERE orderid='{$_POST["custom"]}'";
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
	$remoteorderid=$_POST["purchaseId"];
	$securitycheck = md5("$remoteorderid$payoptionsecret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$orderid'";
	$result = @mysqli_query($db, "$sql");

	$responsemsg = "Success";
	$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$responsemsg&invoice=$RefNr&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
	$response = @fwrite ($fp, $header . $querystring);
	@fclose ($fp);
	@mysqli_close($db);
	exit;
} else if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	if ($address2) $address .= ", $address2";

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	$amount = number_format($amount,2,',','');

	$returnUrl = $ashopurl."/order.php?payopt=$payoption&ofinv=$invoice";
	$cancelUrl = $ashopurl;
	$ipnUrl = $ashopurl."/admin/paysonapi.php";

	$receiver = new Receiver("$merchantid", $amount);
	$receivers = array($receiver);

	$sender = new Sender("$email", "$firstname", "$lastname");

	$payData = new PayData($returnUrl, $cancelUrl, $ipnUrl, $ashopname, $sender, $receivers);

	$payData->setCustom($invoice);

	$orderItems = array();
	$productsincart = ashop_parseproductstring($db, $basket);
	foreach($productsincart as $productnumber => $thisproduct) {
		$itemdescription = $thisproduct["name"];
		$itemprice = $thisproduct["price"];
		$itemquantity = $thisproduct["quantity"];
		$itemtaxable = $thisproduct["taxable"];
		if ($itemtaxable == 0 || $thisproduct["disabletax"]) $itemtax = 0.00;
		else if ($itemtaxable == 1) $itemtax = round($taxpercentage/100,2);
		else if ($itemtaxable == 2) $itemtax = round($taxpercentage2/100,2);
		$itemsku = $thisproduct["sku"];
		if (empty($itemsku)) $itemsku = $thisproduct["productid"];
		$taxmultiplier = 1+$itemtax;
		if ($displaywithtax == 2) $itemprice = $itemprice/$taxmultiplier;
		$orderItems[] = new OrderItem($itemdescription, $itemprice, $itemquantity, $itemtax, $itemsku);
	}
	$handlingcost = ashop_gethandlingcost($taxandshipping);
	if ($handlingcost["shipping"]) $orderItems[] = new OrderItem("Frakt",$handlingcost["shipping"], 1, 0.0, "Frakt");
	if ($payoptionfee) $orderItems[] = new OrderItem("Avgift",$payoptionfee, 1, 0.0, "Avgift");
	$payData->setOrderItems($orderItems);

	$constraints = array(FundingConstraint::INVOICE);
	$payData->setFundingConstraints($constraints);

	$payData->setFeesPayer("PRIMARYRECEIVER");
	$payData->setCurrencyCode($ashopcurrency);
	$payData->setLocaleCode($defaultlang);
	$payData->setGuaranteeOffered("OPTIONAL");

	$payResponse = $api->pay($payData);

	if ($payResponse->getResponseEnvelope()->wasSuccessful()) header("Location: " . $api->getForwardPayUrl($payResponse));
	else print_r($payResponse->getResponseEnvelope()->getErrors());
}
?>