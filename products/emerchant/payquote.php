<?php
if (!$quote && !$payoptionid && $_POST) {
	echo "<html><head>
	<title>Payment Processed</title>
		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
		<link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\">
		</head>
		<body bgcolor=\"#FFFFFF\" text=\"#000000\">
		<table width=\"580\" height=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\"><tr><td colspan=\"2\" align=\"center\"><span class=\"heading3\">Payment Successfully Processed!</span><br><br><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">Close this window and click \"Convert\" to complete the order.</font><br><br><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><a href=\"javascript:this.close();\">Close Window</a></font><br><br><br></td></tr></table></body></html>";
	exit;
}
include "../admin/checklicense.inc.php";
include "emfunc.inc.php";
include "checklogin.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get quote information...
$result = @mysqli_query($db, "SELECT * FROM emerchant_quotes WHERE id='$quote'");
$quoterow = @mysqli_fetch_array($result);
$products = $quoterow["products"];
$comments = $quoterow["comments"];
$commentprices = explode("|",$quoterow["commentprices"]);
$commentprice = 0;
if ($commentprices) foreach ($commentprices as $commentpricenumber=>$thiscommentprice) $commentprice += $thiscommentprice;
$customer = $quoterow["customerid"];
$shipping = $quoterow["shipping"];
$productprices = $quoterow["productprices"];
$orderreference = "em".sprintf("%06d",$quote);
$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
$customerrow = @mysqli_fetch_array($result);
$result = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customer'");
$shippingrow = @mysqli_fetch_array($result);

// Calculate product cost...
$subtotal = 0;
$productsincart = ashop_parseproductstring($db, $products);
if($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
	$productid = $thisproduct["productid"];
	$quantity = $thisproduct["quantity"];
	$price = $thisproduct["price"];
	$name = $thisproduct["name"];
	$parameters = $thisproduct["parameters"];
	$thistotal = $thisproduct["price"] * $thisproduct["quantity"];
	$subtotal += $thistotal;
	$descriptionstring .= $thisproduct["quantity"].": ".$thisproduct["name"].$thisproduct["parameters"];
	$isproductstring .= number_format($price,2,'.','')."::$quantity::$productid::$name::{US}";
	if (count($productsincart) > 1 && $productnumber < count($productsincart)-1) {
		$descriptionstring .= ", ";
		$isproductstring .= "|";
	}
}
$subtotal += $commentprice;
$displaydescr = str_replace(",","<br>",$descriptionstring);

$handlingcosts = ashop_gethandlingcost($shipping);

// Calculate total cost...
$totalcost = $subtotal + $handlingcosts["salestax"] + $handlingcosts["shipping"];

$paypalproductstring .= "
<input type=\"hidden\" name=\"quantity_1\" value=\"1\">
<input type=\"hidden\" name=\"item_name_1\" value=\"$descriptionstring\">
<input type=\"hidden\" name=\"amount_1\" value=\"".number_format($subtotal,2,'.','')."\">
<input type=\"hidden\" name=\"tax_cart\" value=\"{$handlingcosts["salestax"]}\">
<input type=\"hidden\" name=\"handling_cart\" value=\"{$handlingcosts["shipping"]}\">
";

// Get payoption information...
$payoptionresult = @mysqli_query($db, "SELECT * FROM payoptions WHERE payoptionid='$payoptionid'");

// Make sure the page isn't stored in the browsers cache...
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Create payment form...
$gw = @mysqli_result($payoptionresult, 0, "gateway");
$payoptionfee = @mysqli_result($payoptionresult, 0, "fee");
$merchantid = @mysqli_result($payoptionresult, 0, "merchantid");
$transactionkey = @mysqli_result($payoptionresult, 0, "transactionkey");
$logourl = @mysqli_result($payoptionresult, 0, "logourl");
$vspartner = @mysqli_result($payoptionresult, 0, "vspartner");
$pageid = @mysqli_result($payoptionresult, 0, "pageid");
$gwbgcolor = @mysqli_result($payoptionresult, 0, "bgcolor");
$gwbgurl = @mysqli_result($payoptionresult, 0, "bgurl");
$testmode = @mysqli_result($payoptionresult, 0, "testmode");
if ($payoptionfee != "0.00" && $gw == "inetsecure") $isproductstring .= "|".number_format($payoptionfee,2,'.','')."::1::tsf::Transaction Fee::{US}";
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "../admin/gateways$pathprefix/$gw.gw";
if ($noquerystring == "TRUE") { $relayurl = $paymenturl; $paymenturl = "checkout.php"; }
echo "\n\n<html><head><title>Redirecting to payment form...</title></head><body onload=\"document.paymentform.submit()\"><form name=\"paymentform\" method=\"post\" action=\"$paymenturl\">\n";
if (!$gw_amount) $gw_amount = "amount";
echo "<input type=\"hidden\" name=\"$gw_amount\" value=\"";
if (!$totalcost) echo "0";
else echo number_format($totalcost+$payoptionfee,2,'.','');
echo "\">\n";
if ($gw == "authorizenetsim" || $gw == "authecheck" || $gw == "firstdataglobal") @generate_fingerprint($transactionkey, $merchantid, number_format($totalcost+$payoptionfee,2,'.',''));
echo "<input type=\"hidden\" name=\"emerchantquote\" value=\"$quote\">\n";
if ($gw_merchantid) echo "<input type=\"hidden\" name=\"$gw_merchantid\" value=\"$merchantid\">\n";
if ($relayurl) echo "<input type=\"hidden\" name=\"relay\" value=\"$relayurl\">\n";
if ($gw_orderid) echo "<input type=\"hidden\" name=\"$gw_orderid\" value=\"$orderreference\">\n";
if ($gw_logourl) echo "<input type=\"hidden\" name=\"$gw_logourl\" value=\"$logourl\">\n";
if ($gw_vspartner) echo "<input type=\"hidden\" name=\"$gw_vspartner\" value=\"$vspartner\">\n";
if ($gw_pageid) echo "<input type=\"hidden\" name=\"$gw_pageid\" value=\"$pageid\">\n";
if ($gw_returnurl1) echo "<input type=\"hidden\" name=\"$gw_returnurl1\" value=\"$ashopurl/emerchant/payquote.php\">\n";
if ($gw_returnurl2) echo "<input type=\"hidden\" name=\"$gw_returnurl2\" value=\"$ashopurl/emerchant/payquote.php\">\n";
if ($gw_description) echo "<input type=\"hidden\" name=\"$gw_description\" value=\"$descriptionstring\">\n";
if ($gw_basket) echo "<input type=\"hidden\" name=\"$gw_basket\" value=\"$payoptionid"."ashoporderstring$products$shipping\">\n";
if ($gw_extrafields) echo "$gw_extrafields\n";
if ($sendpayoptionid == "TRUE") echo "<input type=\"hidden\" name=\"payoption\" value=\"$payoptionid\">";
if ($gw_cancel) echo "<input type=\"hidden\" name=\"$gw_cancel\" value=\"$ashopurl/emerchant\">\n";
if ($testmode == "1") echo "$testrequest\n";
if ($gw == "paypal" || $gw == "paypalsandbox") echo $paypalproductstring;
if ($affiliate) echo "<input type=\"hidden\" name=\"$gw_affiliate\" value=\"$affiliate\">\n";
if ($gwbgcolor && $gw_bgcolor) echo "<input type=\"hidden\" name=\"$gw_bgcolor\" value=\"$gwbgcolor\">\n";
if ($gwbgurl && $gw_bgurl) echo "<input type=\"hidden\" name=\"$gw_bgurl\" value=\"$gwbgurl\">\n";
if ($gw_firstname) echo "<input type=\"hidden\" name=\"$gw_firstname\" value=\"{$customerrow["firstname"]}\">\n";
if ($gw_lastname) echo "<input type=\"hidden\" name=\"$gw_lastname\" value=\"{$customerrow["lastname"]}\">\n";
if ($gw_address) echo "<input type=\"hidden\" name=\"$gw_address\" value=\"{$customerrow["address"]}\">\n";
if ($gw_city) echo "<input type=\"hidden\" name=\"$gw_city\" value=\"{$customerrow["city"]}\">\n";
if ($gw_zip) echo "<input type=\"hidden\" name=\"$gw_zip\" value=\"{$customerrow["zip"]}\">\n";
if ($gw_state) echo "<input type=\"hidden\" name=\"$gw_state\" value=\"{$customerrow["state"]}\">\n";
if ($gw_country) echo "<input type=\"hidden\" name=\"$gw_country\" value=\"{$customerrow["country"]}\">\n";
if ($gw_email) echo "<input type=\"hidden\" name=\"$gw_email\" value=\"{$customerrow["email"]}\">\n";
if ($gw_phone) echo "<input type=\"hidden\" name=\"$gw_phone\" value=\"{$customerrow["phone"]}\">\n";
if ($gw_shipfirstname) echo "<input type=\"hidden\" name=\"$gw_shipfirstname\" value=\"{$shippingrow["firstname"]}\">\n";
if ($gw_shiplastname) echo "<input type=\"hidden\" name=\"$gw_shiplastname\" value=\"{$shippingrow["lastname"]}\">\n";
if ($gw_shipaddress) echo "<input type=\"hidden\" name=\"$gw_shipaddress\" value=\"{$shippingrow["address"]}\">\n";
if ($gw_shipcity) echo "<input type=\"hidden\" name=\"$gw_shipcity\" value=\"{$shippingrow["city"]}\">\n";
if ($gw_shipzip) echo "<input type=\"hidden\" name=\"$gw_shipzip\" value=\"{$shippingrow["zip"]}\">\n";
if ($gw_shipstate) echo "<input type=\"hidden\" name=\"$gw_shipstate\" value=\"{$shippingrow["state"]}\">\n";
if ($gw_shipcountry) echo "<input type=\"hidden\" name=\"$gw_shipcountry\" value=\"{$shippingrow["country"]}\">\n";
if ($taxandshippingcost) echo "<input type=\"hidden\" name=\"productcost\" value=\"".number_format($productcost,2,'.','')."\"><input type=\"hidden\" name=\"taxandshippingcost\" value=\"".number_format($taxandshippingcost,2,'.','')."\">";
echo "</form></body></html>";
?>