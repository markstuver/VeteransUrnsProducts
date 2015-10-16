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

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;

// Get payment option information...
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid=$payoption";
else $sql = "SELECT * FROM payoptions WHERE gateway='voicepay'";
$result = @mysqli_query($db, "$sql");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$payoptionsecret = @mysqli_result($result, 0, "secret");
$testmode = @mysqli_result($result, 0, "testmode");
$bgcolor = @mysqli_result($result, 0, "bgcolor");
$vpcurrency = strtoupper($ashopcurrency);
if ($testmode) $vptest = 1;
else $vptest = 0;

if ($auth_status == "A" && isset($tran_ref) && isset($cart_id)) {
	$sql = "SELECT * FROM pendingorders WHERE orderid='$cart_id'";
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
	$remoteorderid=$tran_ref;
	$securitycheck = md5("$remoteorderid$payoptionsecret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$cart_id'";
	$result = @mysqli_query($db, "$sql");
	if ($password && $password != $payoptionsecret) exit;

	if ($store_id == $merchantid && $cart_cost == $amount) {
		//$newchecksum = md5("{$cart_id}{$cart_desc}{$amount}{$vpcurrency}{$tran_ref}{$auth_status}");
			echo "Success";
			$responsemsg = "Success";
			$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$responsemsg&invoice=$cart_id&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
			@mysqli_close($db);
	}

} else if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	if ($address2) $address .= ", $address2";

	$amount = number_format($amount,2,'.','');

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	foreach ($countries as $shortcountry => $longcountry) if ($country == $longcountry) $vpcountry = $shortcountry;
	$vpcheck = md5("{$payoptionsecret}:{$merchantid}:{$invoice}:{$amount}:{$vpcurrency}:{$vptest}:{$description}");
	$vpname = "$firstname $lastname";

	echo "<html>
	<head>
	<title>Redirecting to VoicePay...</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\" />
	<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" />
	<meta http-equiv=\"Content-Style-Type\" content=\"text/css\" />
	<script type=\"text/javascript\">
	<!--
	window.onload = function (evt) { document.forms[0].submit(); }
	//-->
	</script>
	</head>
	<body>
	$vpdebug
	<form name=\"payform\" method=\"post\" action=\"https://secure.voice-pay.com/gateway/standard\">
	<input type=\"hidden\" name=\"store_id\" value=\"$merchantid\" />
	<input type=\"hidden\" name=\"cart_id\" value=\"$invoice\" />
	<input type=\"hidden\" name=\"amount\" value=\"$amount\" />
	<input type=\"hidden\" name=\"currency\" value=\"$vpcurrency\" />
	<input type=\"hidden\" name=\"description\" value=\"$description\" />
	<input type=\"hidden\" name=\"check\" value=\"$vpcheck\">
	<input type=\"hidden\" name=\"name\" value=\"$vpname\">
	<input type=\"hidden\" name=\"address\" value=\"$address\">
	<input type=\"hidden\" name=\"postcode\" value=\"$zip\">
	<input type=\"hidden\" name=\"country\" value=\"$vpcountry\">
	<input type=\"hidden\" name=\"email\" value=\"$email\">
	<input type=\"hidden\" name=\"test\" value=\"$vptest\">
	</form>
	</body>
	</html>";
}
?>