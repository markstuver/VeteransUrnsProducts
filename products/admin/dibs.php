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
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid=$payoption";
else $sql = "SELECT * FROM payoptions WHERE gateway='dibs'";
$result = @mysqli_query($db, "$sql");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "gateways$pathprefix/$gateway.gw";
switch ($ashopcurrency) {
	case "usd":
		$dibscurrency = "840";
	break;
	case "aud":
		$dibscurrency = "036";
	break;
	case "cad":
		$dibscurrency = "124";
	break;
	case "eur":
		$dibscurrency = "978";
	break;
	case "gbp":
		$dibscurrency = "826";
	break;
	case "nok":
		$dibscurrency = "578";
	break;
	case "sek":
		$dibscurrency = "752";
	break;
}
$merchantid = @mysqli_result($result, 0, "merchantid");
$payoptionsecret = @mysqli_result($result, 0, "secret");
$k2 = @mysqli_result($result, 0, "transactionkey");
$testmode = @mysqli_result($result, 0, "testmode");
$bgcolor = @mysqli_result($result, 0, "bgcolor");

if (isset($transact) && isset($orderid)) {
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
	$dibsamount = number_format($amount*100,0);
	$products = $row["products"];
	$description = $row["description"];
	$affiliate = $row["affiliate"];
	$remoteorderid=$transact;
	$securitycheck = md5("$remoteorderid$payoptionsecret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$orderid'";
	$result = @mysqli_query($db, "$sql");

	if ($authkey) {
		$newchecksum = md5($ks.md5($payoptionsecret."merchant=$merchantid&orderid=$orderid&currency=$dibscurrency&amount=$dibsamount"));
		if ($authkey==$newchecksum) {
			echo "Success";
			$responsemsg = "Success";
			$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$responsemsg&invoice=$orderid&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
	}

} else if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	if ($address2) $address .= ", $address2";

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	$dibsamount = number_format($amount*100,0);

	echo "<html>
	<head>
	<title>Redirecting to DIBS Payment...</title>
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
	<form name=\"payform\" method=\"post\" action=\"https://payment.architrade.com/paymentweb/start.action\">
	<input type=\"hidden\" name=\"merchant\" value=\"$merchantid\" />
	<input type=\"hidden\" name=\"color\" value=\"$bgcolor\" />
	<input type=\"hidden\" name=\"orderid\" value=\"$invoice\" />
	<input type=\"hidden\" name=\"lang\" value=\"$lang\" />
	<input type=\"hidden\" name=\"amount\" value=\"$dibsamount\" />
	<input type=\"hidden\" name=\"currency\" value=\"$dibscurrency\" />
	<input type=\"hidden\" name=\"accepturl\" value=\"$ashopsurl/order.php?payopt=$payoption\" />
	<input type=\"hidden\" name=\"callbackurl\" value=\"$ashopurl/admin/dibs.php\" />
	<input type=\"hidden\" name=\"cancelurl\" value=\"$ashopurl\" />";
	if ($testmode) echo "
	<input type=\"hidden\" name=\"test\" value=\"yes\" />";
	echo "
	</form>
	</body>
	</html>";
}
?>