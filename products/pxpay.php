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

// Validate transaction_id...
if (!empty($transactionId) && !ashop_is_md5($transactionId)) $transactionId = "";

if ($_SERVER['HTTPS'] == "on") $secureconnection = TRUE;
else $secureconnection = FALSE;

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
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/checkout.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/cart.html")) $templatepath = "/members/files/$ashopuser";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get payment option information...
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid='$payoption'";
else $sql = "SELECT * FROM payoptions WHERE gateway='pxpay'";
$result = @mysqli_query($db, "$sql");
$payoption = @mysqli_result($result, 0, "payoptionid");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "admin/gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$secret = @mysqli_result($result, 0, "secret");

// Process the order...
if (!empty($_GET["result"]) && !empty($_GET["userid"]) && $_GET["userid"] == $merchantid) {

	// Build result request...
	$pxpayxmlrequest = "<ProcessResponse><PxPayUserId>$merchantid</PxPayUserId><PxPayKey>$secret</PxPayKey><Response>{$_GET["result"]}</Response></ProcessResponse>";

	// Send decrypt request to DPS PxPay...
	$error = "";
	if (function_exists('curl_version')) {
		$curlversion = curl_version();
		if ((is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS"))) || strstr($curlversion, "SSL")) {
			$ch = curl_init();
			if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
			curl_setopt($ch, CURLOPT_URL,"https://sec.paymentexpress.com/pxpay/pxaccess.aspx");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $pxpayxmlrequest);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			$result = curl_exec ($ch);
			$error = curl_error($ch);
			curl_close ($ch);
		} else $error = "NOCURL";
	} else $error = "NOCURL";

	// Parse XML response...
	$pxpay_result = "";
	$pxpay_authid = "";
	$pxpay_amount = "";
	$pxpay_orderid = "";
	if (empty($error) && !empty($result)) {
		if (strpos($result,"<Success>")) {
			$subresultarray = explode("<Success>",$result);
			$subresultarray = explode("</Success>",$subresultarray[1]);
			$pxpay_result = $subresultarray[0];
			if (strpos($result,"<DpsTxnRef>")) {
				$subresultarray = explode("<DpsTxnRef>",$result);
				$subresultarray = explode("</DpsTxnRef>",$subresultarray[1]);
				$pxpay_authid = $subresultarray[0];
			}
			if (strpos($result,"<AmountSettlement>")) {
				$subresultarray = explode("<AmountSettlement>",$result);
				$subresultarray = explode("</AmountSettlement>",$subresultarray[1]);
				$pxpay_amount = $subresultarray[0];
			}
			if (strpos($result,"<TxnId>")) {
				$subresultarray = explode("<TxnId>",$result);
				$subresultarray = explode("</TxnId>",$subresultarray[1]);
				$pxpay_orderid = $subresultarray[0];
			}
		} else if (strstr($result,"<Request valid=\"0\">")) {
			$error = "Invalid payment request!";
		} else {
			$error = "No readable response from DPS PxPay!";
		}
	}

	// Parse error, if any, from response...
	if ($pxpay_result != "1" && strpos($result,"<ResponseText>")) {
		$subresultarray = explode("<ResponseText>",$result);
		$subresultarray = explode("</ResponseText>",$subresultarray[1]);
		$error = $subresultarray[0];
		if ($error == "DO NOT HONOUR") $error = "Payment declined!";
	}

	$sql = "SELECT * FROM pendingorders WHERE orderid='$pxpay_orderid'";
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
	$remoteorderid=$pxpay_authid;
	$securitycheck = md5("$remoteorderid$secret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$pxpay_orderid'";
	$result = @mysqli_query($db, "$sql");

	if ($pxpay_result == "1" && $pxpay_amount == $amount) {
		$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=Success&invoice=$pxpay_orderid&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
		header ("Location: $ashopurl/order.php?payopt=$payoption&ofinv=$pxpay_orderid");
	} else if ($error) {
		if ($secureconnection) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
			else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
		} else {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		}
		echo "<p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>".ERROR."</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$error<br><br><a href=\"checkout.php\">".TRYAGAIN."</a></span></font></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	}
	exit;
}

// Redirect the customer to DPS for payment...
if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
	if ($address2) $address .= ", $address2";
	$amount = number_format($amount,2,'.','');

	// Get the country code...
	foreach ($countries as $shortcountry => $longcountry) if ($country == $longcountry) $country = $shortcountry;

	// Currency code should be upper case...
	$pxpay_currency = strtoupper($ashopcurrency);

	// Build XML request...
	$pxpayxmlrequest = "<GenerateRequest><UrlFail>$ashopurl/pxpay.php</UrlFail><UrlSuccess>$ashopurl/pxpay.php</UrlSuccess><AmountInput>$amount</AmountInput><EnableAddBillCard></EnableAddBillCard><PxPayUserId>$merchantid</PxPayUserId><PxPayKey>$secret</PxPayKey><Opt></Opt><TxnType>Purchase</TxnType><CurrencyInput>$pxpay_currency</CurrencyInput><TxnData1></TxnData1><TxnData2></TxnData2><TxnData3></TxnData3><MerchantReference></MerchantReference><EmailAddress></EmailAddress><BillingId></BillingId><TxnId>$invoice</TxnId></GenerateRequest>";

	// Send register request to DPS PxPay...
	$error = "";
	if (function_exists('curl_version')) {
		$curlversion = curl_version();
		if ((is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS"))) || strstr($curlversion, "SSL")) {
			$ch = curl_init();
			if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
			curl_setopt($ch, CURLOPT_URL,"https://sec.paymentexpress.com/pxpay/pxaccess.aspx");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $pxpayxmlrequest);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			$result = curl_exec ($ch);
			$error = curl_error($ch);
			curl_close ($ch);
		} else $error = "NOCURL";
	} else $error = "NOCURL";

	// Parse XML response...
	$pxpay_paymenturl = "";
	if (empty($error) && !empty($result)) {
		if (strpos($result,"<URI>")) {
			$subresultarray = explode("<URI>",$result);
			$subresultarray = explode("</URI>",$subresultarray[1]);
			$pxpay_paymenturl = html_entity_decode($subresultarray[0]);
		} else if (strstr($result,"<Request valid=\"0\">")) {
			$error = "Invalid payment request!";
		} else {
			$error = "No readable response from DPS PxPay!";
		}
	}

	// Order has been registered with DPS, store it and redirect...
	if (!empty($pxpay_paymenturl)) {
		@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
		header("Location: $pxpay_paymenturl");
		exit;
	} else if ($error) {
		if ($secureconnection) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
			else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
		} else {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		}
		echo "<p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>".ERROR."</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$error<br><br><a href=\"checkout.php\">".TRYAGAIN."</a></span></font></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	}
}
?>