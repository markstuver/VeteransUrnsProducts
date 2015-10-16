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

include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";
include "../admin/ashopconstants.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/checkout.inc.php";

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/checkout.html")) $templatepath = "/members/files/$ashopuser";

if ( ! function_exists('signInfo') ) {
	function signInfo ($merchantnumber, $gatewaynumber, $orderid, $amount, $secret, $currency)
	{
		global $ashopurl;
		$amount = number_format($amount,2,'.','');
		$signinfo = hash('sha256', $merchantnumber.$gatewaynumber.$orderid.$currency.$amount."$ashopurl/payment/payenjoy.php".$secret);
		return $signinfo;
	}
}

if ( ! function_exists('retsignInfo') ) {
	function retsignInfo ($merchantnumber, $gatewaynumber, $tradenumber, $orderid, $currency, $amount, $orderstatus, $orderinfo, $secret)
	{
		$signinfo = hash('sha256', $merchantnumber.$gatewaynumber.$tradenumber.$orderid.$currency.$amount.$orderstatus.$orderinfo.$secret);
		return $signinfo;
	}
}

// Get payment option information...
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid='$payoption'";
else $sql = "SELECT * FROM payoptions WHERE gateway='payenjoy'";
$result = @mysqli_query($db, "$sql");
$payoption = @mysqli_result($result, 0, "payoptionid");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "../admin/gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$payoptionsecret = @mysqli_result($result, 0, "secret");
$testmode = @mysqli_result($result, 0, "testmode");
$transactionkey = @mysqli_result($result, 0, "transactionkey");
$payenjoycurrency = strtoupper($ashopcurrency);
if ($testmode) $payenjoyurl = "https://secureshopingmall.com/TestInterface";
else $payenjoyurl = "https://secureshopingmall.com/Interface";

if ($merNo == $merchantid && $gatewayNo == $transactionkey && isset($orderNo) && is_numeric($orderNo) && isset($tradeNo) && $orderStatus == 1) {
	$sql = "SELECT * FROM pendingorders WHERE orderid='$orderNo'";
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
	$remoteorderid=$tradeNo;
	$securitycheck = md5("$remoteorderid$payoptionsecret");
	$checkretsign = retsignInfo ($merNo, $gatewayNo, $tradeNo, $orderNo, $payenjoycurrency, $orderAmount, $orderStatus, $orderInfo, $payoptionsecret);
	$checkretsign = strtoupper($checkretsign);
	if (empty($signInfo) || $signInfo != $checkretsign) exit;

	if ($orderAmount == $amount) {
		$responsemsg = "Success";
		$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$responsemsg&invoice=$orderNo&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
		$header .= "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
		$fp = @fsockopen ("$urldomain", 80, $errno, $errstr, 10);
		$response = @fwrite ($fp, $header . $querystring);
		@fclose ($fp);
		$sql = "DELETE FROM pendingorders WHERE orderid='$orderNo'";
		$result = @mysqli_query($db, "$sql");
		@mysqli_close($db);
		header("Location: $ashopsurl/order.php?payopt=$payoption&ofinv=$orderNo");
	} else {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/payment-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/payment.html");
		echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">Paid amount did not match order amount!<br /><br /><a href=\"$ashopurl/checkout.php\">".TRYAGAIN."</a>!</span></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/payment-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/payment.html");
		exit;
	}
} else if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	if ($address2) $address .= ", $address2";

	$amount = number_format($amount,2,'.','');

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/payment-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/payment.html");
	echo "<iframe src=\"$ashopsurl/payment/payenjoy.php?iframe=true&pendingorder=$invoice&products=$products\" style=\"seamless:seamless; width: 600px; height: 500px; border: none;\"></iframe>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/payment-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/payment.html");

} else if ($pendingorder) {

	// Read preliminary customer info...
	$result = @mysqli_query($db, "SELECT * FROM pendingorders WHERE orderid='$pendingorder'");
	$row = @mysqli_fetch_array($result);

	$country = $row["country"];
	$invoice = $pendingorder;
	$amount = $row["amount"];
	$firstname = $row["firstname"];
	$lastname = $row["lastname"];
	$email = $row["email"];
	$address = $row["address"];
	$zip = $row["zip"];
	$city = $row["city"];
	$state = $row["state"];
	$phone = $row["phone"];

	$amount = number_format($amount,2,'.','');

	if (!empty($merchantid) && !empty($payoptionsecret) && !empty($transactionkey) && !empty($amount) && !empty($invoice)) $signInfo = signInfo($merchantid, $transactionkey, $invoice, $amount, $payoptionsecret, $payenjoycurrency);
	else $signInfo = "";

	@mysqli_close($db);

	foreach ($countries as $shortcountry => $longcountry) if ($country == $longcountry) $payenjoycountry = $shortcountry;

	echo "<html>
	<head>
	<title>".REDIRECTFORM."</title>
	".CHARSET."
	<link rel=\"stylesheet\" href=\"../includes/ashopcss.inc.php\" type=\"text/css\">
	<script type=\"text/javascript\">
	<!--
	window.onload = function (evt) { document.forms[0].submit(); }
	//-->
	</script>
	</head>
	<body>
	<br /><br /><br /><p align=\"center\"><span class=\"ashopcarttext\" style=\"font-size: 16px;\">".REDIRECTSERV."</span></p>
	<form name=\"payform\" method=\"post\" action=\"$payenjoyurl\">
	<input type=\"hidden\" name=\"merNo\" value=\"$merchantid\" />
	<input type=\"hidden\" name=\"orderNo\" value=\"$invoice\" />
	<input type=\"hidden\" name=\"orderAmount\" value=\"$amount\" />
	<input type=\"hidden\" name=\"orderCurrency\" value=\"$payenjoycurrency\">
	<input type=\"hidden\" name=\"gatewayNo\" value=\"$transactionkey\">
	<input type=\"hidden\" name=\"signInfo\" value=\"$signInfo\">
	<input type=\"hidden\" name=\"customerID\" value=\"$customerid\" />
	<input type=\"hidden\" name=\"firstName\" value=\"$firstname\">
	<input type=\"hidden\" name=\"lastName\" value=\"$lastname\">
	<input type=\"hidden\" name=\"email\" value=\"$email\">
	<input type=\"hidden\" name=\"address\" value=\"$address\">
	<input type=\"hidden\" name=\"zip\" value=\"$zip\">
	<input type=\"hidden\" name=\"city\" value=\"$city\">
	<input type=\"hidden\" name=\"state\" value=\"$state\">
	<input type=\"hidden\" name=\"country\" value=\"$payenjoycountry\">
	<input type=\"hidden\" name=\"phone\" value=\"$phone\">
	<input type=\"hidden\" name=\"interfaceInfo\" value=\"ashop\">
	<input type=\"hidden\" name=\"paymentMethod\" value=\"Credit Card\">
	<input type=\"hidden\" name=\"returnUrl\" value=\"$ashopurl/payment/payenjoy.php\">
	</form>
	</body>
	</html>";
} else {
	echo "
	<html><head><link rel=\"stylesheet\" href=\"../includes/ashopcss.inc.php\" type=\"text/css\" /></head><body>
	<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">$orderInfo<br /><br /><a href=\"$ashopurl/checkout.php\" target=\"_parent\">".TRYAGAIN."</a>!</span></p></body></html>";
	exit;
}
?>