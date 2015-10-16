<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
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

session_start();

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/checkout.inc.php";

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/checkout.html")) $templatepath = "/members/files/$ashopuser";

// Get payment option information...
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid='$payoption'";
else $sql = "SELECT * FROM payoptions WHERE gateway='klarna'";
$result = @mysqli_query($db, "$sql");
$payoption = @mysqli_result($result, 0, "payoptionid");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "../admin/gateways$pathprefix/$gateway.gw";
$merchantid = @mysqli_result($result, 0, "merchantid");
$payoptionsecret = @mysqli_result($result, 0, "secret");
$testmode = @mysqli_result($result, 0, "testmode");
$klarnacurrency = strtoupper($ashopcurrency);

// Show confirmation page...
if (!empty($confirm)) {
	// Get the domain for cookies...
	$ashopurlarray = parse_url($ashopurl);
	$ashopurlhost = $ashopurlarray['host'];
	if (substr($ashopurlhost,0,4) == "www.") $ashopurldomain = substr($ashopurlhost,4);
	else $ashopurldomain = $ashopurlhost;
	header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	if ($basket != "") {
		setcookie ("basket", "",time() - 42000,'/',"$ashopurldomain");
	}
	if ($fixbackbutton != "") {
		setcookie ("fixbackbutton", "",time() - 42000,'/',"$ashopurldomain");
	}
	if (isset($_COOKIE['payopt'])) {
		setcookie ("payopt", "",time() - 42000,'/',"$ashopurldomain");
	}

	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/payment-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/payment.html");

	if ($device != "mobile") echo "<div align=\"center\" style=\"width: 750px;\">";

	generate_confirmation($payoptionsecret, $confirm, $testmode);

	if ($device != "mobile") echo "</div>";

	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/payment-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/payment.html");
	exit;
}

// Process order...
if (!empty($push)) {
	$orderdetails = push_order($payoptionsecret, $push, $testmode);
	$invoice = $orderdetails["orderid"];
	$email = $orderdetails["email"];
	if ($email == "checkout-se@testdrive.klarna.com") $email = $ashopemail;
	$firstname = $orderdetails["firstname"];
	$lastname = $orderdetails["lastname"];
	$address = $orderdetails["address"];
	$city = $orderdetails["city"];
	$zip = $orderdetails["zip"];
	$state = $orderdetails["state"];
	$country = strtoupper($orderdetails["country"]);
	foreach ($countries as $shortcountry=>$longcountry) if ($country == $shortcountry) $country = $longcountry;
	$phone = $orderdetails["phone"];
	$orderstatus = $orderdetails["orderstatus"];
	$amount = $orderdetails["amount"];
	$amount = $amount/100;

	if ($orderstatus == "checkout_complete") {

		// Get remaining info from preliminary order...
		$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$invoice'");
		$row = @mysqli_fetch_array($result);
		$products = $payoption."ashoporderstring".$row["products"];
		$description = $row["description"];

		$remoteorderid = $orderdetails["remoteorderid"];
		$securitycheck = md5("$remoteorderid$payoptionsecret");
		$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=Success&invoice=$invoice&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
		$fp = @fsockopen ("$urldomain", 80);
		$response = "";
		if ($fp) {
			fputs ($fp, $header . $querystring);
			while (!feof($fp)) $response .= fread ($fp, 8192);
			fclose ($fp);
		}

		echo "SUCCESS";
	}
	exit;
}

$order = null;
$cart = array();

// Get customer profile and price level...
if (!empty($_COOKIE["customersessionid"])) {
	$customerresult = @mysqli_query($db, "SELECT level, firstname, lastname, email, zip FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$pricelevel = @mysqli_result($customerresult,0,"level");
	$customeremail = @mysql_result($customerresult,0,"email");
	$customerzip = @mysql_result($customerresult,0,"zip");
} else $pricelevel = 0;

$productsincart = ashop_parseproductstring($db, $splitorderstring[1]);

if ($productsincart) {
	if (!empty($taxandshipping)) $shippingarray = ashop_gethandlingcost($taxandshipping);
	$totalqty = ashop_totalqty($splitorderstring[1]);
	foreach($productsincart as $productnumber => $thisproduct) {
		$productid = $thisproduct["productid"];
		$quantity = $thisproduct["quantity"];
		if ($pricelevel < 1) $price = $thisproduct["price"];
		else if ($pricelevel == 1) $price = $thisproduct["wholesaleprice"];
		else {
			$pricelevels = $thisproduct["wspricelevels"];
			$price = $pricelevels[$pricelevel-2];
		}
		$taxmultiplier = 1+($taxpercentage/100);
		$taxmultiplier2 = 1+($taxpercentage2/100);
		$originalprice = $price;
		if ($shippingarray["salestax"] == 0) $thisproduct["taxable"] = 0;
		if ($thisproduct["taxable"] == "2") {
			if ($displaywithtax == "2") {
				$tax = $taxpercentage2*100;
			} else {
				$price *= $taxmultiplier2;
				$tax = $taxpercentage2*100;
			}
		} else if ($thisproduct["taxable"]) {
			if ($displaywithtax == "2") {
				$tax = $taxpercentage*100;
			} else {
				$price *= $taxmultiplier;
				$tax = $taxpercentage*100;
			}
		} else $tax = 0;
		$sku = $thisproduct["skucode"];
		if (empty($sku)) $sku = $productid;
		$name = $thisproduct["name"];
		$parameters = $thisproduct["parameters"];
		$name = "$name $parameters";
		$name = urlencode($name);

		// Check discounts...
		if ($thisproduct["discounted"] == "true") {
			if (isset($_SESSION) && is_array($_SESSION)) foreach ($_SESSION as $cookiename=>$cookievalue) {
				if (strstr($cookiename,"discount")) {
					$discountid = str_replace("discount","",$cookiename);
					$sql="SELECT * FROM discount WHERE productid='$productid' AND discountid='$discountid'";
					$result2 = @mysqli_query($db, "$sql");
					if (@mysqli_num_rows($result2)) $thisproductdiscount = $cookievalue;
					else {
						$sql="SELECT * FROM storediscounts WHERE discountid='$discountid' AND categoryid!='' AND categoryid IS NOT NULL";
						$result2 = @mysqli_query($db, "$sql");
						if (@mysqli_num_rows($result2)) {
							$discountcategory = @mysqli_result($result2, 0, "categoryid");
							$result3 = @mysqli_query($db, "SELECT * FROM productcategory WHERE productid='$productid' AND categoryid='$discountcategory'");
							if (@mysqli_num_rows($result3)) $thisproductdiscount = $cookievalue;
						}
					}
				}
			}
		} else $thisproductdiscount = "0";

		// Calculate subtotal...
		if (!$thisproduct["qtytype"] || $thisproduct["qtytype"] == "1" || $thisproduct["qtytype"] == "3") $subtotalqty = $quantity;
		else {
			if (!$thisproduct["qtycategory"]) $subtotalqty = $totalqty;
			else $subtotalqty = ashop_categoryqty($db, $basket, $thisproduct["qtycategory"]);
		}

		$thistotal = ashop_subtotal($db, $productid, $subtotalqty, $quantity, $thisproductdiscount, $price, $thisproduct["qtytype"]);
		$thistotalwithoutdiscount = ashop_subtotal($db, $productid, $subtotalqty, $quantity, "", $price, $thisproduct["qtytype"]);

		$discount = $thistotal - $thistotalwithoutdiscount;

		$priceperitem = $thistotal/$quantity;
		$discountperitem = $discount/$quantity;

		$priceperitem = intval($priceperitem*100);
		$discountperitem = intval($discountperitem*100);

		$cart[] = 
			array(
				'reference' => "$sku",
				'name' => "$name",
				'quantity' => $quantity,
				'unit_price' => $priceperitem,
				'discount_rate' => $discountperitem,
				'tax_rate' => $tax
			);

	}

	// Extract and include shipping...
	if ($taxandshipping) {
		$shippingdiscountamount = 0;
		if ($discountall) {
			$storediscountresult = @mysqli_query($db, "SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='s'");
			if (@mysqli_num_rows($storediscountresult)) {
				$shippingdiscountamount = $shippingarray["shipping"];
			}
		}
		$items = explode("a", $taxandshipping);
		$arraycount = 1;
		$taxandshippingcost = 0;
		if ($items[0] && count($items)==1) $arraycount = 0;
		for ($i = 0; $i < count($items)-$arraycount; $i++) {
			$thisitem = explode("b", $items[$i]);
			if ($thisitem[0] == "sh" || $thisitem[0] == "st" || $thisitem[0] == "sd") {
				$price = $thisitem[1];
				if ($thisitem[0] == "sh") {
					$name = SHIPPING;
					if ($shippingdiscountamount) $price = 0;
					else {
						if ($shippingtax == "1") {
							$taxmultiplier = 1+($taxpercentage/100);
							$price *= $taxmultiplier;
							$tax = $taxpercentage*100;
						} else $tax = 0;
						$totalshipping = $price;
						$totalshipping = intval($totalshipping*100);
						$cart[] = array(
							'type' => 'shipping_fee',
							'reference' => 'SHIPPING',
							'name' => 'Shipping Fee',
							'quantity' => 1,
							'unit_price' => $totalshipping,
							'tax_rate' => $tax
						);
					}
				}
			}
		}
	}

	// Include storewide discount of amount type if any...
	if ($discountall) {
		$storediscountresult = @mysqli_query($db, "SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='$'");
		if (@mysqli_num_rows($storediscountresult)) {
			$storediscountrow = @mysqli_fetch_array($storediscountresult);
			if ($storediscountrow["value"]) {
				$totaldiscount = number_format($storediscountrow["value"],0,'','');
				$totaldiscount = intval($totaldiscount*100);
				$tax = $taxpercentage*100;
				$cart[] = array(
					'type' => 'discount',
					'reference' => 'DISCOUNT',
					'name' => 'Discount',
					'quantity' => 1,
					'unit_price' => -$totaldiscount,
					'tax_rate' => $tax
				);
			}
		}
	}
}

// DESKTOP: Width of containing block shall be at least 750px
// MOBILE: Width of containing block shall be 100% of browser window (No
// padding or margin)
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/payment-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/payment.html");

if ($device != "mobile") echo "<div align=\"center\" style=\"width: 750px;\">";

try {
	generate_snippet($merchantid, $payoptionsecret, $testmode, $invoice, $cart, $customeremail, $customerzip);
}

//catch exception
catch(Exception $e) {
	$errorarray = json_decode($e->getMessage(), true);
	echo '<br /><br />Klarna felmeddelande: '. $errorarray["internal_message"];
}

if ($device != "mobile") echo "</div>";

if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/payment-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/payment-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/payment.html");
?>