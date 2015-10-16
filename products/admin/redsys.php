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
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid='$payoption'";
else $sql = "SELECT * FROM payoptions WHERE gateway='redsys'";
$result = @mysqli_query($db, "$sql");
$payoption = @mysqli_result($result, 0, "payoptionid");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$secret = @mysqli_result($result, 0, "secret");
$transactionkey = @mysqli_result($result, 0, "transactionkey");
$testmode = @mysqli_result($result, 0, "testmode");
if ($testmode) $redsys_url = "https://sis-t.redsys.es:25443/sis/realizarPago";
else $redsys_url = "https://sis.redsys.es/sis/realizarPago";

// Redirect the customer to Redsys for credit card payment...
if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
	if ($address2) $address .= ", $address2";
	$amount = number_format($amount,2,'.','');

	// Get the country code...
	foreach ($countries as $shortcountry => $longcountry) if ($country == $longcountry) $country = $shortcountry;

	// Compute hash to sign form data...
	$sis_order = $invoice;
	while (strlen($sis_order) < 4) $sis_order = "0".$sis_order;
	$sis_amount = number_format(($amount)*100,0,'','');
	$sis_signaturemessage = $sis_amount.$sis_order.$merchantid."978"."0".$postbackurl.$secret;
	$sis_signature = strtoupper(sha1($sis_signaturemessage));

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	echo "<html>
	<head>
	<title>Redirecting to Redsys...</title>
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
	<form name=\"payform\" method=\"post\" action=\"$redsys_url\">
	<input type=\"hidden\" name=\"Ds_Merchant_MerchantCode\" value=\"$merchantid\" />
	<input type=\"hidden\" name=\"Ds_Merchant_ProductDescription\" value=\"$description\" />
	<input type=\"hidden\" name=\"Ds_Merchant_MerchantURL\" value=\"$postbackurl\" />
	<input type=\"hidden\" name=\"Ds_Merchant_UrlOK\" value=\"$postbackurl?payopt=$payoption&ofinv=$invoice&fromshop=$shop&returnurl=$returnurl\">
	<input type=\"hidden\" name=\"Ds_Merchant_Currency\" value=\"978\">\n
	<input type=\"hidden\" name=\"Ds_Merchant_TransactionType\" value=\"0\">\n
	<input type=\"hidden\" name=\"Ds_Merchant_Terminal\" value=\"$transactionkey\">\n
	<input type=\"hidden\" name=\"Ds_Merchant_Amount\" value=\"$sis_amount\">\n
	<input type=\"hidden\" name=\"Ds_Merchant_Order\" value=\"$sis_order\">\n
	<input type=\"hidden\" name=\"Ds_Merchant_MerchantData\" value=\"$products\">\n
	<input type=\"hidden\" name=\"Ds_Merchant_Cardholder\" value=\"$firstname $lastname\">\n
	<input type=\"hidden\" name=\"Ds_Merchant_MerchantSignature\" value=\"$sis_signature\">";
	if ($returnurl) echo "<input type=\"hidden\" name=\"Ds_Merchant_UrlKO\" value=\"$returnurl\">\n";
	else if ($shop > 1) echo "<input type=\"hidden\" name=\"Ds_Merchant_UrlKO\" value=\"$ashopurl/index.php?shop=$shop\">\n";
	else echo "<input type=\"hidden\" name=\"Ds_Merchant_UrlKO\" value=\"$ashopurl\">\n";
	echo "
	</form>
	</body>
	</html>";
}
?>