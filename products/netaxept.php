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
else $sql = "SELECT * FROM payoptions WHERE gateway='netaxept'";
$result = @mysqli_query($db,"$sql");
$payoption = @mysqli_result($result, 0, "payoptionid");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "admin/gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$secret = @mysqli_result($result, 0, "secret");
$testmode = @mysqli_result($result, 0, "testmode");
if ($testmode) $netaxept_url = "https://epayment-test.bbs.no";
else $netaxept_url = "https://epayment.bbs.no";

// Customer has cancelled, redirect to shopping cart...
if (!empty($responseCode) && $responseCode == "Cancel") {
	header("Location: $ashopurl");
	exit;
}

// Process the order...
if (!empty($transactionId) && !empty($responseCode) && $responseCode == "OK") {
	$result = @mysqli_query($db,"SELECT orderid FROM orders WHERE remoteorderid='$transactionId'");
	$orderid = @mysqli_result($result,0,"orderid");
	$sql = "SELECT * FROM pendingorders WHERE orderid='$orderid'";
	$result = @mysqli_query($db,"$sql");
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
	$remoteorderid=$transactionId;
	$securitycheck = md5("$remoteorderid$secret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$orderid'";
	$result = @mysqli_query($db,"$sql");

	// Build AUTH request...
	$authurl = "$netaxept_url/Netaxept/Process.aspx?merchantId=$merchantid&token=$secret&transactionId=$transactionId&operation=AUTH";

	// Send AUTH request to Netaxept's REST API...
	$error = "";
	if (function_exists('curl_version')) {
		$curlversion = curl_version();
		if ((is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS"))) || strstr($curlversion, "SSL")) {
			$ch = curl_init();
			if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
			curl_setopt($ch, CURLOPT_URL,$authurl);
			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			$result = curl_exec ($ch);
			$error = curl_error($ch);
			curl_close ($ch);
		} else $error = "NOCURL";
	} else $error = "NOCURL";

	// Parse XML response...
	$netaxept_authresponse = "";
	$netaxept_authid = "";
	$netaxept_merchantid = "";
	if (empty($error) && !empty($result)) {
		if (strpos($result," <ResponseCode>")) {
			$subresultarray = explode(" <ResponseCode>",$result);
			$subresultarray = explode("</ResponseCode>",$subresultarray[1]);
			$netaxept_authresponse = $subresultarray[0];
			if (strpos($result," <AuthorizationId>")) {
				$subresultarray = explode(" <AuthorizationId>",$result);
				$subresultarray = explode("</AuthorizationId>",$subresultarray[1]);
				$netaxept_authid = $subresultarray[0];
			}
			if (strpos($result," <MerchantId>")) {
				$subresultarray = explode(" <MerchantId>",$result);
				$subresultarray = explode("</MerchantId>",$subresultarray[1]);
				$netaxept_merchantid = $subresultarray[0];
			}
		} else if (strpos($result,"<Message>")) {
			$subresultarray = explode("<Message>",$result);
			$subresultarray = explode("</Message>",$subresultarray[1]);
			$error = $subresultarray[0];
		}
	}

	if ($netaxept_authresponse != "OK" && strpos($result,"<ResponseText>")) {
		$subresultarray = explode("<ResponseText>",$result);
		$subresultarray = explode("</ResponseText>",$subresultarray[1]);
		$error = $subresultarray[0];
	}

	if ($netaxept_authresponse == "OK" && $netaxept_merchantid == $merchantid) {
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
		header ("Location: $ashopurl/order.php?payopt=$payoption&ofinv=$orderid");
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

// Redirect the customer to Netaxept for payment...
if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
	if ($address2) $address .= ", $address2";
	$amount = number_format($amount,2,'.','');

	// Get the country code...
	foreach ($countries as $shortcountry => $longcountry) if ($country == $longcountry) $country = $shortcountry;

	// Payment should be in cents...
	$netaxept_amount = number_format(($amount)*100,0,'','');

	// Currency code should be upper case...
	$netaxept_currency = strtoupper($ashopcurrency);

	// Build register request...
	$registerurl = "$netaxept_url/Netaxept/Register.aspx?merchantId=$merchantid&token=$secret&orderNumber=$invoice&amount=$netaxept_amount&CurrencyCode=$netaxept_currency&language=sv_SE&redirectUrl=$ashopurl/netaxept.php";

	// Send register request to Netaxept's REST API...
	$error = "";
	if (function_exists('curl_version')) {
		$curlversion = curl_version();
		if ((is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS"))) || strstr($curlversion, "SSL")) {
			$ch = curl_init();
			if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
			curl_setopt($ch, CURLOPT_URL,$registerurl);
			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			$result = curl_exec ($ch);
			$error = curl_error($ch);
			curl_close ($ch);
		} else $error = "NOCURL";
	} else $error = "NOCURL";

	// Parse XML response...
	$netaxept_transactionid = "";
	if (empty($error) && !empty($result)) {
		if (strpos($result,"<TransactionId>")) {
			$subresultarray = explode("<TransactionId>",$result);
			$subresultarray = explode("</TransactionId>",$subresultarray[1]);
			$netaxept_transactionid = $subresultarray[0];
		} else if (strpos($result,"<Message>")) {
			$subresultarray = explode("<Message>",$result);
			$subresultarray = explode("</Message>",$subresultarray[1]);
			$error = $subresultarray[0];
		}
	}

	// Order has been registered with Netaxept, store it and redirect...
	if (!empty($netaxept_transactionid)) {
		@mysqli_query($db,"INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
		@mysqli_query($db,"UPDATE orders SET remoteorderid='$netaxept_transactionid' WHERE orderid='$invoice'");
		$redirecturl = "$netaxept_url/Terminal/default.aspx?merchantId=$merchantid&transactionId=$netaxept_transactionid";
		header("Location: $redirecturl");
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