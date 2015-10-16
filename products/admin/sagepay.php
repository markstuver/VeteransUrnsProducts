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

function sagepay_simplexor($InString, $Key) {
	$KeyList = array();
	$output = "";
	for($i = 0; $i < strlen($Key); $i++){
		$KeyList[$i] = ord(substr($Key, $i, 1));
	}
	for($i = 0; $i < strlen($InString); $i++) {
		$output.= chr(ord(substr($InString, $i, 1)) ^ ($KeyList[$i % strlen($Key)]));
	}
	return $output;
}

function sagepay_base64decode($scrambled) {
  $output = "";
  $scrambled = str_replace(" ","+",$scrambled);
  $output = base64_decode($scrambled);
  return $output;
}

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;

// Get payment option information...
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid=$payoption";
else $sql = "SELECT * FROM payoptions WHERE gateway='sagepay'";
$result = @mysqli_query($db, "$sql");
$payoption = @mysqli_result($result, 0, "payoptionid");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$payoptionsecret = @mysqli_result($result, 0, "secret");
$testmode = @mysqli_result($result, 0, "testmode");
if ($testmode) $sagepay_url = "https://test.sagepay.com/gateway/service/vspform-register.vsp";
// Simulator URL: $sagepay_url = "https://test.sagepay.com/simulator/vspformgateway.asp";
else $sagepay_url = "https://live.sagepay.com/gateway/service/vspform-register.vsp";
$bgcolor = @mysqli_result($result, 0, "bgcolor");
$sagepay_fields = array();

// Decrypt postback...
if (isset($crypt)) {
	$sagepay_postback = sagepay_simplexor(sagepay_base64decode($crypt),$payoptionsecret);
	$sagepay_fieldsarray = explode("&",$sagepay_postback);
	if (!empty($sagepay_fieldsarray) && is_array($sagepay_fieldsarray)) foreach ($sagepay_fieldsarray as $sagepay_fieldpart) {
		$sagepay_fieldarray = explode("=",$sagepay_fieldpart);
		$sagepay_fields["{$sagepay_fieldarray[0]}"] = $sagepay_fieldarray[1];
	}
}

// Check payment status and process...
if (isset($sagepay_fields["Status"])) {
	$orderid = $sagepay_fields["VendorTxCode"];
	$sagepay_amount = number_format($sagepay_fields["Amount"],2,'.','');
	$remoteorderid=$sagepay_fields["TxAuthNo"];
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
	$amount = number_format($row["amount"],2,'.','');
	$products = $row["products"];
	$description = $row["description"];
	$affiliate = $row["affiliate"];
	$securitycheck = md5("$remoteorderid$payoptionsecret");

	if ($sagepay_fields["Status"]!="OK" || empty($remoteorderid) || $sagepay_amount != $amount) {
		// Apply selected theme...
		$buttonpath = "";
		$templatepath = "/templates";
		if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
		if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
		if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
		if ($lang && is_array($themelanguages)) {
			if (!in_array("$lang",$themelanguages)) unset($lang);
		}

		// Include language file...
		if (!$lang) $lang = $defaultlanguage;
		include "../language/$lang/orderform.inc.php";

		// Show failure message...
		ob_start();
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		echo "<p align=\"center\"><br><br><span class=\"ashopmessageheader\">".CARDINVALID."</span></p><p align=\"center\"><span class=\"ashopmessage\">{$sagepay_fields["StatusDetail"]}<br><br><a href=\"checkout.php\">".TRYAGAIN."</a></span></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		$carthtml = ob_get_contents();
		ob_end_clean();
		$carthtml = str_replace("src=\"","src=\"../",$carthtml);
		$carthtml = str_replace("src='","src='../",$carthtml);
		$carthtml = str_replace("SRC=\"","SRC=\"../",$carthtml);
		$carthtml = str_replace("SRC='","SRC='../",$carthtml);
		$carthtml = str_replace("href=\"","href=\"../",$carthtml);
		$carthtml = str_replace("href='","href='../",$carthtml);
		$carthtml = str_replace("HREF=\"","HREF=\"../",$carthtml);
		$carthtml = str_replace("HREF='","HREF='../",$carthtml);
		$carthtml = str_replace("../http","http",$carthtml);
		echo $carthtml;
		exit;

	} else {
		$sql = "DELETE FROM pendingorders WHERE orderid='$orderid'";
		$result = @mysqli_query($db, "$sql");
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
		$header = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
		$fp = @fsockopen ("$urldomain", 80, $errno, $errstr, 10);
		$response = @fwrite ($fp, $header . $querystring);
		@fclose ($fp);
		@mysqli_close($db);
		header ("Location: $ashopurl/order.php?payopt=$payoption&ofinv=$orderid");
		exit;
	}

} else if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
	if ($address2) $address .= ", $address2";
	$amount = number_format($amount,2,'.','');
	$sagepay_currency = strtoupper($ashopcurrency);
	// Get the country code...
	foreach ($countries as $shortcountry => $longcountry) if ($country == $longcountry) $country = $shortcountry;

	// Generate SagePay order string...
	$sagepay_order = "VendorTxCode=$invoice&Amount=$amount&Currency=$sagepay_currency&Description=$description&SuccessURL=$ashopurl/admin/sagepay.php&FailureURL=$ashopurl/admin/sagepay.php&CustomerName=$fistname $lastname&CustomerEMail=$email&BillingSurname=$lastname&BillingFirstnames=$firstname&BillingAddress1=$address&BillingCity=$city&BillingPostCode=$zip&BillingCountry=$country&BillingState=$state&BillingPhone=$phone&DeliverySurname=$lastname&DeliveryFirstnames=$firstname&DeliveryAddress1=$address&DeliveryCity=$city&DeliveryPostCode=$zip&DeliveryCountry=$country&DeliveryState=$state";

	// Encrypt the order string..
	$sagepay_crypt = base64_encode(sagepay_simplexor($sagepay_order,$payoptionsecret));

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	echo "<html>
	<head>
	<title>Redirecting to SagePay...</title>
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
	<form name=\"payform\" method=\"post\" action=\"$sagepay_url\">
	<input type=\"hidden\" name=\"Vendor\" value=\"$merchantid\" />
	<input type=\"hidden\" name=\"VPSProtocol\" value=\"2.23\" />
	<input type=\"hidden\" name=\"TxType\" value=\"PAYMENT\" />
	<input type=\"hidden\" name=\"Crypt\" value=\"$sagepay_crypt\" />
	</form>
	</body>
	</html>";
}
?>