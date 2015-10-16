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

// Validate variables...
if (empty($shop)) $shop = 1;
if (!empty($shop) && !is_numeric($shop)) $shop = 1;

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "../themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/orderform.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/checkout.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get payment option information...
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid='$payoption'";
else $sql = "SELECT * FROM payoptions WHERE gateway='bips'";
$result = @mysqli_query($db, "$sql");
$payoption = @mysqli_result($result, 0, "payoptionid");
$gateway = @mysqli_result($result, 0, "gateway");
$thankyoutext = @mysqli_result($result, 0, "thankyoutext");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "../admin/gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$secret = @mysqli_result($result, 0, "secret");

// Show thank you page...
if (!empty($payopt) && $payopt == $payoption && $ofinv) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/payment-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/payment.html");

	$ofinvresult = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$ofinv'");
	$parsed_products = @mysqli_result($ofinvresult,0,"products");
	$parsed_price = @mysqli_result($ofinvresult,0,"price");
	$descriptionstring = @mysqli_result($ofinvresult,0,"description");
	$parsed_remoteorderid = @mysqli_result($ofinvresult,0,"remoteorderid");
	$orderstatus = @mysqli_result($ofinvresult,0,"status");
	$shop = @mysqli_result($ofinvresult,0,"userid");
	if ($orderstatus == "Cancelled") $thankyoutext = "<br /><br /><table class=\"ashopthankyouframe\"><tr align=\"center\"><td><span class=\"ashopthankyouheader\">Payment failed!</span></td></tr><tr align=\"center\"><td><span class=\"ashopthankyoutext2\">The payment was cancelled by you.</span></td></tr></table>";
	if ($orderstatus == "Expired") $thankyoutext = "<br /><br /><table class=\"ashopthankyouframe\"><tr align=\"center\"><td><span class=\"ashopthankyouheader\">Payment failed!</span></td></tr><tr align=\"center\"><td><span class=\"ashopthankyoutext2\">The payment has expired.</span></td></tr></table>";
	if ($orderstatus == "Failure") $thankyoutext = "<br /><br /><table class=\"ashopthankyouframe\"><tr align=\"center\"><td><span class=\"ashopthankyouheader\">Payment failed!</span></td></tr><tr align=\"center\"><td><span class=\"ashopthankyoutext2\">The payment could not be completed. There are no further details available as to why.</span></td></tr></table>";
	$invoiceid = @mysqli_result($ofinvresult,0,"invoiceid");
	$parsed_invoice = $ofinv;
	if (empty($invoiceid)) $invoiceid = $parsed_invoice;
	$password = @mysqli_result($ofinvresult,0,"password");
	$customerid = @mysqli_result($ofinvresult,0,"customerid");
	$ofcustresult = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
	$parsed_firstname = @mysqli_result($ofcustresult,0,"firstname");
	$parsed_lastname = @mysqli_result($ofcustresult,0,"lastname");
	$parsed_email = @mysqli_result($ofcustresult,0,"email");
	$ofshiptoresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customerid'");
	$row = @mysqli_fetch_array($ofshiptoresult);
	if ($row["shippingbusiness"]) $shipto = "{$row["shippingbusiness"]}<br>\r\n{$row["shippingfirstname "]} {$row["shippinglastname"]}<br>\r\n";
	else $shipto = "{$row["shippingfirstname"]} {$row["shippinglastname"]}<br>\r\n";
	$shipto .= "{$row["shippingaddress"]}<br>\r\n";
	if ($row["shippingaddress2"]) $shipto .= "{$row["shippingaddress2"]}<br>\r\n";
	$shipto .= "{$row["shippingcity"]}, {$row["shippingstate"]} {$row["shippingzip"]}<br>\r\n";
	$shipto .= $countries[$row["shippingcountry"]];
	$ofkeyresult = @mysqli_query($db, "SELECT * FROM unlockkeys WHERE orderid='$ofinv' ORDER BY productid");
	$unlockkeystring = "";
	while ($row = @mysqli_fetch_array($ofkeyresult)) {
		$keyproduct = $row["productid"];
		$productresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$keyproduct'");
		$thisproductname = @mysqli_result($productresult,0,"name");
		$unlockkeystring .= "$thisproductname: <br><b>{$row["keytext"]}</b><br>";
	}
	// Get information about purchased products...
	$productsincart = ashop_parseproductstring($db, $parsed_products);
	if ($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
		$thisproductid = $thisproduct["productid"];
		$checkfiles = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$thisproductid'");
		$files = @mysqli_num_rows($checkfiles);
		if ($files && $thisproduct["download"] != "none") $downloadgoods = 1;
	}

	// Get the name of the referring shop...
	$shops = explode("|",$shop);
	if ($shops[0] != "1") {
		$shopresult = @mysqli_query($db, "SELECT shopname FROM user WHERE userid='{$shops[0]}'");
		$shopname[1] = ashop_mailsafe(stripslashes(@mysqli_result($result,0,"shopname")));
	}

	// Make sure the name of the shop is displayed...
	if (!$shopname[1]) $shopname[1] = $ashopname;

	// Print thank you message...
	$thankyoutext = str_replace("%amount%",$parsed_price,$thankyoutext);
	$thankyoutext = str_replace("%orderid%",$invoiceid,$thankyoutext);
	$thankyoutext = str_replace("%gatewayorderid%",$parsed_remoteorderid,$thankyoutext);
	$thankyoutext = str_replace("%description%",$descriptionstring,$thankyoutext);
	$thankyoutext = str_replace("%firstname%",$parsed_firstname,$thankyoutext);
	$thankyoutext = str_replace("%lastname%",$parsed_lastname,$thankyoutext);
	if ($shipto) $thankyoutext = str_replace("%shippingaddress%",$shipto,$thankyoutext);
	else $thankyoutext = str_replace("%shippingaddress%",NOSHIP,$thankyoutext);
	$thankyoutext = str_replace("%email%",$parsed_email,$thankyoutext);
	$thankyoutext = str_replace("%password%",$password,$thankyoutext);
	$thankyoutext = str_replace("%keycodes%",$unlockkeystring,$thankyoutext);
	if (is_array($shopname)) $useshopname = end($shopname);
	else $useshopname = $shopname;
	$thankyoutext = str_replace("%shopname%",$useshopname,$thankyoutext);
	$thankyoutext = str_replace("%shopaddress%",$shopaddress,$thankyoutext);
	$thankyoutext = str_replace("%shopemail%",$shopemail,$thankyoutext);
	$thankyoutext = str_replace("%shopphone%",$shopphone,$thankyoutext);
	if (!$membershops) $useshopname = $ashopname;
	echo "<br /><br />$thankyoutext
	<br /><table class=\"ashopthankyouframe\"><tr><td align=\"center\"><br><span class=\"ashopthankyoutext2\"><a href=\"$orderpagelink\">".BACK."$useshopname</a><br></span></td></tr>
		</table>";

	// Print delivery form if needed...
	if ($subscriptiongoods && $authorized[0] != "PENDING") echo "
	<br><table class=\"ashopthankyouframe\">
    <tr><td><span class=\"ashopthankyoutext2\"><p>".ACCESSP."</p><p>$subscriptionlinks</p></span></td></tr></table><br>";
	if ($downloadgoods && $authorized[0] != "PENDING") {
		echo "
        <br><table class=\"ashopthankyouframe\">
		<tr><td><span class=\"ashopthankyoutext2\">".ACCESSD."</span></td></tr><tr><td>
        <form method=\"post\" action=\"$ashopurl/deliver.php\">
        <table width=\"400\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
        <tr><td><span class=\"ashopthankyoutext2\">".EMAIL.": </span></td><td><input type=\"text\" name=\"email\"></td><td>&nbsp;</td></tr>
        <tr><td><span class=\"ashopthankyoutext2\">".PASS.": </span></td><td><input type=\"password\" name=\"password\"></td>";
		if ($device == "mobile") echo "<td>&nbsp;</td></tr><tr><td colspan=\"2\"><input type=\"submit\" value=\"Login\"></td></tr></table></form></td></tr></table><br>";
		else echo "<td><input type=\"submit\" value=\"Login\"></td></tr></table></form></td></tr></table><br>";
	}

	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/payment-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/payment.html");
}

// Process the order...
$orderid = $custom['orderid'];
$checkhash = hash('sha512', $transaction['hash'] . $secret);
if ($hash == $checkhash && $status == 1 && !empty($orderid)) {
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
	$remoteorderid=$invoice;
	$securitycheck = md5("$remoteorderid$secret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$orderid'";
	//$result = @mysqli_query($db, "$sql");

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
	exit;
}

// Redirect the customer to BIPS for payment...
if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
	if ($address2) $address .= ", $address2";
	$amount = number_format($amount,2,'.','');
	$bipscurrency = strtoupper($ashopcurrency);
	$orderdetails = "{\"orderid\":\"$invoice\"}";

	// Get the country code...
	foreach ($countries as $shortcountry => $longcountry) if ($country == $longcountry) $country = $shortcountry;

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");

	$orderdetails = array(
		'orderid' => $invoice,
		'returnurl' => rawurlencode("$ashopurl/payment/bips.php?payopt=$payoption&ofinv=$invoice"),
		'cancelurl' => rawurlencode($ashopurl),
		'callbackurl' => rawurlencode("$ashopurl/payment/bips.php"),
		'secret' => $secret);

	$orderdetails = json_encode($orderdetails);

	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, 'https://bips.me/api/v1/invoice');
	curl_setopt ($ch, CURLOPT_USERPWD, $merchantid);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, "price=$amount&currency=USD&item=$description&custom=$orderdetails");
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	$bipsinvoiceurl = curl_exec($ch);
	$bipsinvoiceerror = curl_error($ch);
	curl_close($ch);
	if (!$bipsinvoiceerror && !empty($bipsinvoiceurl)) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/payment-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/payment.html");
		echo "<iframe src=\"$bipsinvoiceurl/iframe\" style=\"seamless:seamless; width: 600px; height: 500px; border: none;\"></iframe>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/payment-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/payment.html");
		//header("Location: $bipsinvoiceurl");
		exit;
	} else {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/payment-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/payment.html");
		echo "<table class=\"ashopmessagetable\"><tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">An error occurred. Please contact the webshop. Error message: ".$bipsinvoiceerror."</span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/payment-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/payment.html");
	}
}
?>