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

// Get payment option information...
$sql = "SELECT * FROM payoptions WHERE gateway='transfirst'";
$result = @mysqli_query($db, "$sql");
$gateway = @mysqli_result($result, 0, "gateway");
$payoption = @mysqli_result($result, 0, "payoptionid");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$payoptionsecret = @mysqli_result($result, 0, "transactionkey");
$testmode = @mysqli_result($result, 0, "testmode");
$bgcolor = @mysqli_result($result, 0, "bgcolor");

if (isset($TransID) && isset($RefNo) && isset($Auth) && isset($MerchantID)) {
	$sql = "SELECT * FROM pendingorders WHERE orderid='$RefNo'";
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
	$remoteorderid=$TransID;
	$securitycheck = md5("$remoteorderid$payoptionsecret");

	if ($Auth != "Declined" && $MerchantID == $merchantid) {
		$responsemsg = "Success";
		$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$responsemsg&invoice=$RefNo&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
		header ("Location: $postbackurl?payopt=$payoption&ofinv=$RefNo");
		exit;
	} else {
		// Apply selected theme...
		$templatepath = "/templates";
		if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "../themes/$ashoptheme/theme.cfg.php";
		if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
		if ($lang && is_array($themelanguages)) {
			if (!in_array("$lang",$themelanguages)) unset($lang);
		}

		// Include language file...
		if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
		include "../language/$lang/orderform.inc.php";
		$shop = -1;

		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<p align=\"center\"><br><br><span class=\"ashopmessageheader\">".CARDINVALID."</span></p><p align=\"center\"><span class=\"ashopmessage\">$Notes<br><br><a href=\"javascript:history.go(-2)\">".TRYAGAIN."</a></span></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	}
} else if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	echo "<html>
	<head>
	<title>Redirecting to TransFirst Payment...</title>
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
	<form name=\"payform\" method=\"post\" action=\"https://webservices.primerchants.com/billing/TransactionCentral/EnterTransaction.asp?\">
	<input type=\"hidden\" name=\"MerchantID\" value=\"$merchantid\" />
	<input type=\"hidden\" name=\"RegKey\" value=\"$payoptionsecret\" />
	<input type=\"hidden\" name=\"RefID\" value=\"$invoice\" />
	<input type=\"hidden\" name=\"TransType\" value=\"CC\" />
	<input type=\"hidden\" name=\"Amount\" value=\"$amount\" />
	<input type=\"hidden\" name=\"USER1\" value=\"$products\" />
	<input type=\"hidden\" name=\"RURL\" value=\"$paymenturl2\" />";
	if ($testmode) echo "
	<input type=\"hidden\" name=\"test\" value=\"yes\" />";
	echo "
	</form>
	</body>
	</html>";
}
?>