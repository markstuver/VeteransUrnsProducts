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

// Validate variables...
if (!empty($returnurl) && !ashop_is_url($returnurl)) $returnurl = "";
if (!empty($_GET["invoice"]) && !is_numeric($_GET["invoice"])) $_GET["invoice"] = "";

if (!$_GET["invoice"]) {
	header("Location: $ashopurl");
	exit;
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/payment.inc.php";

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Check if this is a member auction payment...
if ($memberpayoptions) {
	$memberauctionresult = @mysqli_query($db, "SELECT userid FROM memberorders WHERE auction='1' AND orderid='$invoice'");
	if (@mysqli_num_rows($memberauctionresult)) $shop = @mysqli_result($memberauctionresult,0,"userid");
}

// Get member template path if no theme is used...
if (!$shop) $shop = 1;
if (!is_numeric($shop)) $shop = 1;
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if (!$db) $error = 1;

// Get billing details...
$orderresult = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$invoice'");
if (!@mysqli_num_rows($orderresult)) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
	echo "<br><br><p align=\"center\"><font face=\"$font\" color=\"#900000\"><span class=\"fontsize2\"><b>".NOTEXIST1."</b><br><br>".NOTEXIST2."!</span></font></p>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	exit;
}
$orderrow = @mysqli_fetch_array($orderresult);
$orderid = $invoice;
if ($orderrow["paid"]) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
	echo "<br><br><p align=\"center\"><font face=\"$font\" color=\"#900000\"><span class=\"fontsize2\"><b>".ALREADYPAID1."</b><br><br>".ALREADYPAID2."!</span></font></p>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	exit;
}
$newbasket = $orderrow["products"];
$subtotal = $orderrow["price"];
$description = $orderrow["description"];
$customerid = $orderrow["customerid"];
$taxandshippingcost = $orderrow["tax"] + $orderrow["shipping"];
$subsubtotal = $subtotal-$taxandshippingcost;
$paypalproductstring .= "
<input type=\"hidden\" name=\"quantity_1\" value=\"1\">
<input type=\"hidden\" name=\"item_name_1\" value=\"$description\">
<input type=\"hidden\" name=\"amount_1\" value=\"".number_format($subsubtotal,2,'.','')."\">
";
if (!empty($orderrow["tax"])) $paypalproductstring .= "<input type=\"hidden\" name=\"tax_cart\" value=\"{$orderrow["tax"]}\">
";

// Get shipping address if provided...
$result = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customerid'");
$shippingbusiness = @mysqli_result($result, 0, "shippingbusiness");
$shippingfirstname = @mysqli_result($result, 0, "shippingfirstname");
$shippinglastname = @mysqli_result($result, 0, "shippinglastname");
$shippingaddress = @mysqli_result($result, 0, "shippingaddress");
$shippingaddress2 = @mysqli_result($result, 0, "shippingaddress2");
$shippingzip = @mysqli_result($result, 0, "shippingzip");
$shippingcity = @mysqli_result($result, 0, "shippingcity");
$shippingstate = @mysqli_result($result, 0, "shippingstate");
$shippingcountry = @mysqli_result($result, 0, "shippingcountry");
$shippingphone = @mysqli_result($result, 0, "shippingphone");

if ($_POST["relay"]) {
	if ($cancel_x) {
		if ($_POST["returnurl"]) {
			$returnurl = str_replace("|","&",$_POST["returnurl"]);
			header("Location: $returnurl");
		}
		else header("Location: index.php");
		exit;
	}
	if ($_POST["relay"] == "https://www.sfipay.com/handle.php") {
		$payoptarray = explode("?",$return_url);
		$payopt = explode("=",$payoptarray[1]);
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie("payopt",$payopt[1]);
	} else if ($_POST["relay"] == "http://www.payso.com/sales.php") {
		$relay = "{$_POST["relay"]}?OA={$_POST["OA"]}&amount={$_POST["payso_amount"]}&email=1&ship=1&postback=1&cur=US&url={$_POST["url"]}";
	}
	// Make sure the page isn't stored in the browsers cache...
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	echo "<html><head><title>".REDIRECTSERV."</title>\n".CHARSET."</head><body onload=\"document.forms[0].submit()\"><form method=\"POST\" action=\"$relay\">";
	foreach($_POST as $fieldname=>$fieldvalue) if($fieldname != "relay" && $fieldname != "x" && $fieldname != "y") echo "<input type=\"hidden\" name=\"$fieldname\" value=\"$fieldvalue\">";
	echo "<input type=\"hidden\" name=\"securitykey\" value=\"".md5($ashoppath)."\"></form></body></html>";
	exit;
}

// Get payment options...
$sql="SELECT * FROM payoptions WHERE (wholesaleonly = '' OR wholesaleonly IS NULL OR wholesaleonly = '0') AND gateway!='googleco' AND userid='$shop'";
if ($payoption) $sql .= " AND payoptionid='$payoption'";
$sql .= " ORDER BY ordernumber";
$payoptionresult = @mysqli_query($db, "$sql");
$numberofpayoptions = @mysqli_num_rows($payoptionresult);
if ($numberofpayoptions == "1") $gw = @mysqli_result($payoptionresult, 0, "gateway");

// Create TeleSign Code if needed...
if($telesignid) $telesigncode = ashop_telesigncode();
else $telesigncode = "";

// Only show gateways that can be used with the selected currency...
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";

// Automatically submit single payment options...
if (($numberofpayoptions == "1"  && file_exists("$ashoppath/admin/gateways$pathprefix/$gw.gw") && $invoice) || (!$subtotal && $invoice)) {
	// Make sure the page isn't stored in the browsers cache...
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
		if (!$subtotal) {
			$payoptionid = 0;
			$payoptionfee = 0;
			$gw = "manual";
		} else {
			$payoptionid = @mysqli_result($payoptionresult, 0, "payoptionid");
			$payoptionfee = @mysqli_result($payoptionresult, 0, "fee");
			if (empty($payoptionfee) || $payoptionfee == 0) {
				$payoptionfee = 0.00;
				// Create PayPal shipping parameter...
				if (($gw == "paypal" || $gw == "paypalsandbox") && $orderrow["shipping"]) $paypalproductstring .= "
				<input type=\"hidden\" name=\"handling_cart\" value=\"{$orderrow["shipping"]}\">";
			} else if ($gw == "paypal" || $gw == "paypalsandbox") {
				$orderrow["shipping"] += $payoptionfee;
				$paypalproductstring .= "
				<input type=\"hidden\" name=\"handling_cart\" value=\"{$orderrow["shipping"]}\">";
			}
			$merchantid = @mysqli_result($payoptionresult, 0, "merchantid");
			$transactionkey = @mysqli_result($payoptionresult, 0, "transactionkey");
			$secret = @mysqli_result($payoptionresult, 0, "secret");
			$logourl = @mysqli_result($payoptionresult, 0, "logourl");
			$vspartner = @mysqli_result($payoptionresult, 0, "vspartner");
			$pageid = @mysqli_result($payoptionresult, 0, "pageid");
			$gwbgcolor = @mysqli_result($payoptionresult, 0, "bgcolor");
			$gwbgurl = @mysqli_result($payoptionresult, 0, "bgurl");
			$testmode = @mysqli_result($payoptionresult, 0, "testmode");
			$initialperiod = @mysqli_result($payoptionresult, 0, "initialperiod");
			if (!empty($initialperiod) && strstr($initialperiod,"|")) {
				$initialperiodarray = explode("|",$initialperiod);
				$initialperiod = $initialperiodarray[0];
				$initialperiodunits = $initialperiodarray[1];
			} else {
				$initialperiod = "";
				$initialperiodunits = "";
			}
			$recurringperiod = @mysqli_result($payoptionresult, 0, "recurringperiod");
			if (!empty($recurringperiod) && strstr($recurringperiod,"|")) {
				$recurringperiodarray = explode("|",$recurringperiod);
				$recurringperiod = $recurringperiodarray[0];
				$recurringperiodunits = $recurringperiodarray[1];
			} else {
				$recurringperiod = "";
				$recurringperiodunits = "";
			}
			$rebills = @mysqli_result($payoptionresult, 0, "rebills");
		}
		if ($payoptionfee != "0.00" && $gw == "inetsecure") $isproductstring .= "|".number_format($payoptionfee,2,'.','')."::1::tsf::Transaction Fee::{US}";
		include "admin/gateways$pathprefix/$gw.gw";
		if (!$subtotal) $paymenturl = "giftform.php";
		if ($noquerystring == "TRUE" || file_exists("$ashoppath/agreement.txt")) { $relayurl = $paymenturl; $paymenturl = "checkout.php"; }
		echo "\n\n<html><head><title>".REDIRECTFORM."</title>\n".CHARSET."</head><body onload=\"document.paymentform.submit()\"><form name=\"paymentform\" method=\"post\" action=\"$paymenturl\"></font>\n";
		if ($gw_amount) {
			echo "<input type=\"hidden\" name=\"$gw_amount\" value=\"";
			if (!$subtotal) echo "0";
			else echo number_format($subtotal+$payoptionfee,2,'.','');
			echo "\">\n";
		}
		if ($gw == "authorizenetsim" || $gw == "authecheck" || $gw == "firstdataglobal") @generate_fingerprint($transactionkey, $merchantid, number_format($subtotal+$payoptionfee,2,'.',''));
		if ($gw_merchantid) echo "<input type=\"hidden\" name=\"$gw_merchantid\" value=\"$merchantid\">\n";
		if ($relayurl) echo "<input type=\"hidden\" name=\"relay\" value=\"$relayurl\">\n";
		if ($gw_orderid) echo "<input type=\"hidden\" name=\"$gw_orderid\" value=\"$orderid\">\n";
		if ($gw_logourl) echo "<input type=\"hidden\" name=\"$gw_logourl\" value=\"$logourl\">\n";
		if ($gw_vspartner) echo "<input type=\"hidden\" name=\"$gw_vspartner\" value=\"$vspartner\">\n";
		if ($gw_pageid) echo "<input type=\"hidden\" name=\"$gw_pageid\" value=\"$pageid\">\n";
		if ($gw_returnurl1) echo "<input type=\"hidden\" name=\"$gw_returnurl1\" value=\"$postbackurl\">\n";
		if ($gw_description) {
			if (($gw == "paypal" || $gw == "paypalsandbox") && strlen($description) > 127) echo "<input type=\"hidden\" name=\"$gw_description\" value=\"".substr($description,0,124)."...\">\n";
			else echo "<input type=\"hidden\" name=\"$gw_description\" value=\"$description\">\n";
		}
		if ($gw_basket) {
			if ($gw == "paypal" || $gw == "paypalsandbox") echo "<input type=\"hidden\" name=\"$gw_basket\" value=\"$payoptionid"."ashoporderstring".substr($newbasket,0,50)."\">\n";
			else  echo "<input type=\"hidden\" name=\"$gw_basket\" value=\"$payoptionid"."ashoporderstring$newbasket\">\n";
		}
		if ($gw_extrafields) echo "$gw_extrafields\n";
		if ($gw_firstname && $shippingfirstname) echo "<input type=\"hidden\" name=\"$gw_firstname\" value=\"$shippingfirstname\">\n";
		if ($gw_lastname && $shippinglastname) echo "<input type=\"hidden\" name=\"$gw_lastname\" value=\"$shippinglastname\">\n";
		if ($gw_address && $shippingaddress) echo "<input type=\"hidden\" name=\"$gw_address\" value=\"$shippingaddress\">\n";
		if ($gw_city && $shippingcity) echo "<input type=\"hidden\" name=\"$gw_city\" value=\"$shippingcity\">\n";
		if ($gw_zip && $shippingzip) echo "<input type=\"hidden\" name=\"$gw_zip\" value=\"$shippingzip\">\n";
		if ($gw_state && $shippingstate) echo "<input type=\"hidden\" name=\"$gw_state\" value=\"$shippingstate\">\n";
		if ($gw_country && $shippingcountry) echo "<input type=\"hidden\" name=\"$gw_country\" value=\"$shippingcountry\">\n";
		if ($gw_phone && $shippingphone) echo "<input type=\"hidden\" name=\"$gw_phone\" value=\"$shippingphone\">\n";
		if ($gw_shipfirstname && $shippingfirstname) echo "<input type=\"hidden\" name=\"$gw_shipfirstname\" value=\"$shippingfirstname\">\n";
		if ($gw_shiplastname && $shippinglastname) echo "<input type=\"hidden\" name=\"$gw_shiplastname\" value=\"$shippinglastname\">\n";
		if ($gw_shipaddress && $shippingaddress) echo "<input type=\"hidden\" name=\"$gw_shipaddress\" value=\"$shippingaddress\">\n";
		if ($gw_shipcity && $shippingcity) echo "<input type=\"hidden\" name=\"$gw_shipcity\" value=\"$shippingcity\">\n";
		if ($gw_shipzip && $shippingzip) echo "<input type=\"hidden\" name=\"$gw_shipzip\" value=\"$shippingzip\">\n";
		if ($gw_shipstate && $shippingstate) echo "<input type=\"hidden\" name=\"$gw_shipstate\" value=\"$shippingstate\">\n";
		if ($gw_shipcountry && $shippingcountry) echo "<input type=\"hidden\" name=\"$gw_shipcountry\" value=\"$shippingcountry\">\n";
		if ($sendpayoptionid == "TRUE") echo "<input type=\"hidden\" name=\"payoption\" value=\"$payoptionid\">";
		if ($gw_returnurl2) echo "<input type=\"hidden\" name=\"$gw_returnurl2\" value=\"$postbackurl?payopt=$payoptionid&fromshop=$shop&returnurl=$returnurl\">\n";
		if ($gw_cancel) echo "<input type=\"hidden\" name=\"$gw_cancel\" value=\"$ashopurl\">\n";
		if ($testmode == "1") echo "$testrequest\n";
		if ($gw == "2checkout" || $gw == "2checkoutv2") echo $twocoproductstring;
		if ($gw == "paypal" || $gw == "paypalsandbox") echo $paypalproductstring;
		if ($affiliate) echo "<input type=\"hidden\" name=\"$gw_affiliate\" value=\"$affiliate\">\n";
		if ($gwbgcolor && $gw_bgcolor) echo "<input type=\"hidden\" name=\"$gw_bgcolor\" value=\"$gwbgcolor\">\n";
		if ($gwbgurl && $gw_bgurl) echo "<input type=\"hidden\" name=\"$gw_bgurl\" value=\"$gwbgurl\">\n";
		if ($taxandshippingcost) echo "<input type=\"hidden\" name=\"productcost\" value=\"".number_format($productcost,2,'.','')."\"><input type=\"hidden\" name=\"taxandshippingcost\" value=\"".number_format($taxandshippingcost,2,'.','')."\">";
		if ($returnurl && (strstr($paymenturl, "orderform.php") || strstr($paymenturl, "giftform.php"))) echo "<input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\">";
		if (strstr($paymenturl, "orderform.php")) echo "<input type=\"hidden\" name=\"lang\" value=\"$lang\">";
		echo "</form></body></html>";
	exit;
}

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
?>

    <table class="ashopcheckoutframe">
      
  <tr align="center"> 
    <td><br>
	  
<?php
echo "<p><span class=\"ashopcheckouttext1\">".PAYMENTFOR." $invoice</span></p><p><span class=\"ashopcheckouttext2\">$description</span></p>";
if ($taxandshippingcost) echo "<span class=\"ashopcheckouttext1\">".PRICE."</span><span class=\"ashopcheckouttext2\"> ".$currencysymbols[$ashopcurrency]["pre"].number_format($subsubtotal,2,'.','').$currencysymbols[$ashopcurrency]["post"].", </span><span class=\"ashopcheckouttext1\">".TAX."</span><span class=\"ashopcheckouttext2\"> ".$currencysymbols[$ashopcurrency]["pre"].number_format($taxandshippingcost,2,'.','').$currencysymbols[$ashopcurrency]["post"]."<br>";
echo "<span class=\"ashopcheckouttext1\">".AMOUNT."</span><span class=\"ashopcheckouttext2\"> ".$currencysymbols[$ashopcurrency]["pre"].number_format($subtotal,2,'.','').$currencysymbols[$ashopcurrency]["post"]."</p><p><span class=\"ashopcheckouttext1\">".CHOOSE."</span></p>";

// Print order forms...
$gw = "";
if ($numberofpayoptions) {
		for ($option = 0; $option < $numberofpayoptions; $option++) {
			$noquerystring = "";
			$relayurl = "";
			$gw = @mysqli_result($payoptionresult, $option, "gateway");
			if (file_exists("$ashoppath/admin/gateways$pathprefix/$gw.gw")) {
				$payoptionid = @mysqli_result($payoptionresult, $option, "payoptionid");
				$payoptionname = @mysqli_result($payoptionresult, $option, "name");
				$payoptiondescr = @mysqli_result($payoptionresult, $option, "description");
				$payoptionfee = @mysqli_result($payoptionresult, $option, "fee");
				if (empty($payoptionfee) || $payoptionfee == 0) {
					$payoptionfee = 0.00;
					// Create PayPal shipping parameter...
					if (($gw == "paypal" || $gw == "paypalsandbox") && $orderrow["shipping"]) $paypalproductstring .= "
					<input type=\"hidden\" name=\"handling_cart\" value=\"{$orderrow["shipping"]}\">";
				} else if ($gw == "paypal" || $gw == "paypalsandbox") {
					$orderrow["shipping"] += $payoptionfee;
					$paypalproductstring .= "
					<input type=\"hidden\" name=\"handling_cart\" value=\"{$orderrow["shipping"]}\">";
				}
				$merchantid = @mysqli_result($payoptionresult, $option, "merchantid");
				$transactionkey = @mysqli_result($payoptionresult, $option, "transactionkey");
				$secret = @mysqli_result($payoptionresult, $option, "secret");
				$logourl = @mysqli_result($payoptionresult, $option, "logourl");
				$vspartner = @mysqli_result($payoptionresult, $option, "vspartner");
				$pageid = @mysqli_result($payoptionresult, $option, "pageid");
				$gwbgcolor = @mysqli_result($payoptionresult, $option, "bgcolor");
				$gwbgurl = @mysqli_result($payoptionresult, $option, "bgurl");
				$testmode = @mysqli_result($payoptionresult, $option, "testmode");
				$initialperiod = @mysqli_result($payoptionresult, $option, "initialperiod");
				if (!empty($initialperiod) && strstr($initialperiod,"|")) {
					$initialperiodarray = explode("|",$initialperiod);
					$initialperiod = $initialperiodarray[0];
					$initialperiodunits = $initialperiodarray[1];
				} else {
					$initialperiod = "";
					$initialperiodunits = "";
				}
				$recurringperiod = @mysqli_result($payoptionresult, $option, "recurringperiod");
				if (!empty($recurringperiod) && strstr($recurringperiod,"|")) {
					$recurringperiodarray = explode("|",$recurringperiod);
					$recurringperiod = $recurringperiodarray[0];
					$recurringperiodunits = $recurringperiodarray[1];
				} else {
					$recurringperiod = "";
					$recurringperiodunits = "";
				}
				$rebills = @mysqli_result($payoptionresult, 0, "rebills");
				if ($payoptionfee != "0.00" && $gw == "inetsecure") $isproductstring .= "|".number_format($payoptionfee,2,'.','')."::1::tsf::Transaction Fee::{US}";

				include "admin/gateways$pathprefix/$gw.gw";

				if ($noquerystring == "TRUE" || file_exists("$ashoppath/agreement.txt")) { $relayurl = $paymenturl; $paymenturl = "checkout.php"; }
				
				echo "\n\n<p><form name=\"paymentform\" method=\"post\" action=\"$paymenturl\">\n<table class=\"ashopcheckouttable\"><tr><td align=\"center\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\"><tr><td width=\"30\">&nbsp;</td><td align=\"left\" valign=\"top\"><span class=\"ashopcheckoutcontents\">
				<b>$payoptionname</b><br>$payoptiondescr";
				if ($payoptionfee != "0.00") echo "<br>Transaction fee: <b>".$currencysymbols[$ashopcurrency]["pre"]."$payoptionfee".$currencysymbols[$ashopcurrency]["post"]."</b>";

				echo "</span>\n";
				
				if ($gw_amount) {
					echo "<input type=\"hidden\" name=\"$gw_amount\" value=\"";
					if (!$subtotal) echo "0";
					else echo number_format($subtotal+$payoptionfee,2,'.','');
					echo "\">\n";
				}

				if ($gw == "authorizenetsim" || $gw == "authecheck" || $gw == "firstdataglobal") @generate_fingerprint($transactionkey, $merchantid, number_format($subtotal+$payoptionfee,2,'.',''));

				if ($gw_merchantid) echo "<input type=\"hidden\" name=\"$gw_merchantid\" value=\"$merchantid\">\n";
				if ($relayurl) echo "<input type=\"hidden\" name=\"relay\" value=\"$relayurl\">\n";
				if ($gw_orderid) echo "<input type=\"hidden\" name=\"$gw_orderid\" value=\"$orderid\">\n";
				if ($gw_logourl) echo "<input type=\"hidden\" name=\"$gw_logourl\" value=\"$logourl\">\n";
				if ($gw_vspartner) echo "<input type=\"hidden\" name=\"$gw_vspartner\" value=\"$vspartner\">\n";
				if ($gw_pageid) echo "<input type=\"hidden\" name=\"$gw_pageid\" value=\"$pageid\">\n";
				if ($gw_returnurl1) echo "<input type=\"hidden\" name=\"$gw_returnurl1\" value=\"$postbackurl\">\n";
				if ($gw_description) {
					if (($gw == "paypal" || $gw == "paypalsandbox") && strlen($description) > 127) echo "<input type=\"hidden\" name=\"$gw_description\" value=\"".substr($description,0,124)."...\">\n";
					else echo "<input type=\"hidden\" name=\"$gw_description\" value=\"$description\">\n";
				}
				if ($gw_basket) {
					if ($gw == "paypal" || $gw == "paypalsandbox") echo "<input type=\"hidden\" name=\"$gw_basket\" value=\"$payoptionid"."ashoporderstring".substr($newbasket,0,50)."\">\n";
					else  echo "<input type=\"hidden\" name=\"$gw_basket\" value=\"$payoptionid"."ashoporderstring$newbasket\">\n";
				}
				if ($gw_extrafields) echo "$gw_extrafields\n";
				if ($gw_firstname && $shippingfirstname) echo "<input type=\"hidden\" name=\"$gw_firstname\" value=\"$shippingfirstname\">\n";
				if ($gw_lastname && $shippinglastname) echo "<input type=\"hidden\" name=\"$gw_lastname\" value=\"$shippinglastname\">\n";
				if ($gw_address && $shippingaddress) echo "<input type=\"hidden\" name=\"$gw_address\" value=\"$shippingaddress\">\n";
				if ($gw_city && $shippingcity) echo "<input type=\"hidden\" name=\"$gw_city\" value=\"$shippingcity\">\n";
				if ($gw_zip && $shippingzip) echo "<input type=\"hidden\" name=\"$gw_zip\" value=\"$shippingzip\">\n";
				if ($gw_state && $shippingstate) echo "<input type=\"hidden\" name=\"$gw_state\" value=\"$shippingstate\">\n";
				if ($gw_country && $shippingcountry) echo "<input type=\"hidden\" name=\"$gw_country\" value=\"$shippingcountry\">\n";
				if ($gw_phone && $shippingphone) echo "<input type=\"hidden\" name=\"$gw_phone\" value=\"$shippingphone\">\n";
				if ($gw_shipfirstname && $shippingfirstname) echo "<input type=\"hidden\" name=\"$gw_shipfirstname\" value=\"$shippingfirstname\">\n";
				if ($gw_shiplastname && $shippinglastname) echo "<input type=\"hidden\" name=\"$gw_shiplastname\" value=\"$shippinglastname\">\n";
				if ($gw_shipaddress && $shippingaddress) echo "<input type=\"hidden\" name=\"$gw_shipaddress\" value=\"$shippingaddress\">\n";
				if ($gw_shipcity && $shippingcity) echo "<input type=\"hidden\" name=\"$gw_shipcity\" value=\"$shippingcity\">\n";
				if ($gw_shipzip && $shippingzip) echo "<input type=\"hidden\" name=\"$gw_shipzip\" value=\"$shippingzip\">\n";
				if ($gw_shipstate && $shippingstate) echo "<input type=\"hidden\" name=\"$gw_shipstate\" value=\"$shippingstate\">\n";
				if ($gw_shipcountry && $shippingcountry) echo "<input type=\"hidden\" name=\"$gw_shipcountry\" value=\"$shippingcountry\">\n";
				if ($sendpayoptionid == "TRUE") echo "<input type=\"hidden\" name=\"payoption\" value=\"$payoptionid\">";
				if ($gw_returnurl2) echo "<input type=\"hidden\" name=\"$gw_returnurl2\" value=\"$postbackurl?payopt=$payoptionid&fromshop=$shop&returnurl=$returnurl\">\n";
				if ($gw_cancel) echo "<input type=\"hidden\" name=\"$gw_cancel\" value=\"$ashopurl\">\n";
				if ($testmode == "1") echo "$testrequest\n";
				if ($gw == "2checkout" || $gw == "2checkoutv2") echo $twocoproductstring;
				if ($gw == "paypal" || $gw == "paypalsandbox") echo $paypalproductstring;
				if ($affiliate) echo "<input type=\"hidden\" name=\"$gw_affiliate\" value=\"$affiliate\">\n";
				if ($gwbgcolor && $gw_bgcolor) echo "<input type=\"hidden\" name=\"$gw_bgcolor\" value=\"$gwbgcolor\">\n";
				if ($gwbgurl && $gw_bgurl) echo "<input type=\"hidden\" name=\"$gw_bgurl\" value=\"$gwbgurl\">\n";
				if ($taxandshippingcost) echo "<input type=\"hidden\" name=\"productcost\" value=\"".number_format($productcost,2,'.','')."\"><input type=\"hidden\" name=\"taxandshippingcost\" value=\"".number_format($taxandshippingcost,2,'.','')."\">";
				if ($returnurl && strstr($paymenturl, "orderform.php")) echo "<input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\">";
				if (strstr($paymenturl, "orderform.php")) echo "<input type=\"hidden\" name=\"lang\" value=\"$lang\">";
				if ($gw == "paypalec") echo "</td><td width=\"100\" align=\"right\" valign=\"bottom\"><input type=\"image\" border=\"0\" src=\"https://www.paypal.com/en_US/i/btn/btn_xpressCheckoutsm.gif\" alt=\"Place order\"></td></tr></table></td></tr></table></form></p>";
				else echo "</td><td width=\"100\" align=\"right\" valign=\"bottom\"><input type=\"image\" border=\"0\" src=\"{$buttonpath}images/next-$lang.png\" class=\"ashopbutton\" alt=\"Place order\"></td></tr></table></td></tr></table></form></p>";
			}
		}
}
echo "</td></tr></table><p align=\"center\"><span class=\"ashopcheckouttext2\">".IPLOG1.": {$_SERVER["REMOTE_ADDR"]} ".IPLOG2."</span></p>";

// Close database...

@mysqli_close($db);

// Print footer using template...

if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
?>