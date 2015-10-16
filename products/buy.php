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
include "admin/ashopconstants.inc.php";
include "admin/ashopfunc.inc.php";

// Initialize variables...
if (!isset($lang)) $lang = "";
if (!isset($price)) $price = "";
if (!isset($displaylicenseerror)) $displaylicenseerror = FALSE;
if (!isset($license)) $license = "";
if (!isset($discountcookiestring)) $discountcookiestring = "";
if (!isset($p3psent)) $p3psent = FALSE;
if (!ashop_is_url($redirect)) $redirect = "";
$redirect = str_ireplace("http://","",$redirect);
$redirect = str_ireplace("https://","",$redirect);
if (isset($shop) && !is_numeric($shop)) $shop = "";
$returnmessage = "";
$addreturnmessage = "";

session_start();

// Get the domain for cookies...
$ashopurlarray = parse_url($ashopurl);
$ashopurlhost = $ashopurlarray['host'];
if (substr($ashopurlhost,0,4) == "www.") $ashopurldomain = substr($ashopurlhost,4);
else $ashopurldomain = $ashopurlhost;

// Determine if a product ID should be passed back to the catalogue/search script...
$item = str_replace("s","",$item);

// Validate variables...
if (isset($item) && !is_numeric($item)) $item = 0;
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/buy.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check bid code if necessary...
$floatingpriceresult = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$item'");
if (@mysqli_num_rows($floatingpriceresult)) {
	$endprice = @mysqli_result($floatingpriceresult,0,"endprice");
	if (!$endprice) $returnmessage = AUCTIONNOTENDED;
	if (!ashop_checkbidcode($db, $_COOKIE["bidderhash"])) $returnmessage = NOTALLOWEDTOBUY;
	$bidderhash = explode("|",$_COOKIE["bidderhash"]);
	$thisbidder = $bidderhash[0];
	if (!ashop_checkfinalbid($db, $thisbidder, $item)) $returnmessage = NOTALLOWEDTOBUY;
	$shoppingcart = "2";
}

// Get customer profile and price level...
if (!empty($_COOKIE["customersessionid"])) {
	$customerresult = @mysqli_query($db, "SELECT customerid, level, firstname, lastname FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$pricelevel = @mysqli_result($customerresult,0,"level");
	$customerid = @mysqli_result($customerresult,0,"customerid");
} else $pricelevel = 0;

// Get the products name and price from the database...
$productresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$item' AND (prodtype!='content' OR prodtype IS NULL)");
$skucode = @mysqli_result($productresult, 0, "skucode");
$copyof = @mysqli_result($productresult, 0, "copyof");
if (is_numeric($copyof)) $item = $copyof;
$taxmultiplier = 1+($taxpercentage/100);
$taxable = @mysqli_result($productresult, 0, "taxable");
if (!$price) {
	if ($pricelevel < 1) $price = @mysqli_result($productresult, 0, "price");
	else if ($pricelevel == 1) $price = @mysqli_result($productresult, 0, "wholesaleprice");
	else {
		$pricelevels = @mysqli_result($productresult, 0, "wspricelevels");
		$pricelevels = explode("|",$pricelevels);
		$price = $pricelevels[$pricelevel-2];
	}
	if ($pricelevel > 0) $displaywithtax = $displaywswithtax;
}
$name = @mysqli_result($productresult, 0, "name");
$pricetext = @mysqli_result($productresult, 0, "pricetext");
$qtytype = @mysqli_result($productresult, 0, "qtytype");
$qtycategory = @mysqli_result($productresult, 0, "qtycategory");
$licensetext = @mysqli_result($productresult, 0, "licensetext");
$qtylimit = @mysqli_result($productresult, 0, "qtylimit");
$qtytlimit = @mysqli_result($productresult, 0, "qtytlimit");

// Check if this product's recurring period should override the main setting...
$recurringperiod = @mysqli_result($productresult, 0, "recurringperiod");
if (!empty($recurringperiod)) {
	$recurringperiodcheck = @mysqli_query($db, "SELECT payoptionid FROM payoptions WHERE recurringperiod!='$recurringperiod' AND recurringperiod IS NOT NULL AND recurringperiod!=''");
	if (@mysqli_num_rows($recurringperiodcheck)) $shoppingcart = 0;
}

// Check if there are enough products in stock for this purchase...
$useinventory = @mysqli_result($productresult, 0, "useinventory");
if ($useinventory) {
	$checksaasuinventory = "";
	if ($saasuwsaccesskey && $saasufileid) {
		$checksaasuinventory = ashop_saasu_getinventory($skucode);
		if ($checksaasuinventory != "nodata") {
			$inventory = $checksaasuinventory;
			@mysqli_query($db, "UPDATE product SET inventory = '$inventory' WHERE productid='$item'");
		} else $inventory = @mysqli_result($productresult, 0, "inventory");
	} else $inventory = @mysqli_result($productresult, 0, "inventory");

	$basket = ashop_combineproducts($basket);
	$alreadyincart = ashop_checkproduct($item, $basket, "");

	if (!$inventory) {
		$returnmessage = OUTOFSTOCK;
	} else if ($inventory < ($quantity+$alreadyincart)) {
		$quantity = $inventory - $alreadyincart;
		if ($inventory == $alreadyincart) {
			$quantity = 0;
			$returnmessage = NOTENOUGHINSTOCK;
		} else $addreturnmessage = NOTTHATMANYINSTOCK;			
	}
}

// Check if the product uses qty based pricing...
$qtypriceresult = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$item'");
$qtypricing = @mysqli_num_rows($qtypriceresult);

// Get parameters for this product...
$sql = "SELECT * FROM parameters WHERE productid='$item' ORDER BY parameterid";
$paramresult = @mysqli_query($db, "$sql");
$checkparams = TRUE;
while ($paramrow = @mysqli_fetch_array($paramresult)) if (!$paramrow["buybuttons"]) $checkparams = FALSE;

// Get sale for this product...
$personalsale = FALSE;
if ($customerid) {
	$discountresult = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$item' AND onetime='0' AND (code='' OR code IS NULL) AND customerid='$customerid'");
	if (@mysqli_num_rows($discountresult)) $personalsale = TRUE;
}
if (!$personalsale) $discountresult = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$item' AND onetime='0' AND (code='' OR code IS NULL)");
if (@mysqli_num_rows($discountresult)) {
	$discountcustomerid = @mysqli_result($discountresult,0,"customerid");
	if (empty($discountcustomerid) || $discountcustomerid == $customerid) {
		$salediscount = TRUE;
		$salediscountid = @mysqli_result($discountresult,0,"discountid");
	} else $salediscount = FALSE;
} else $salediscount = FALSE;

/* Apply referral discounts...
if (!empty($affiliate)) {
	$discountresult = @mysqli_query($db, "SELECT discount.* FROM discount,referraldiscount WHERE referraldiscount.affiliateid='$affiliate' AND referraldiscount.code=discount.code AND discount.productid='$item'");
	if (@mysqli_num_rows($discountresult)) {
		$affiliatediscount = TRUE;
		$discountcode = @mysqli_result($discountresult,0,"code");
		$salediscountid = @mysqli_result($discountresult,0,"discountid");
	} else $affiliatediscount = FALSE;
} else $affiliatediscount = FALSE;*/
 
// Check if single quantity mode is being used...
if ($shoppingcart == "2") if (ashop_checkproduct($item, $basket, "")) $returnmessage = ALREADYINCART;

// Get any parameter values and store in basket cookiestring...
$parameterstring = "";
$type = "";
if (@mysqli_num_rows($paramresult)) {
	for ($i = 0; $i < @mysqli_num_rows($paramresult); $i++) {
		$parameterid = @mysqli_result($paramresult, $i, "parameterid");
		$parametername = strtolower(@mysqli_result($paramresult, $i, "caption"));
		$subresult = @mysqli_query($db, "SELECT valueid FROM parametervalues WHERE parameterid='$parameterid'");
		if (@mysqli_num_rows($subresult) == 1) $thisparameter = @mysqli_result($subresult, 0, "valueid");
		else eval ("\$thisparameter = \$attribute$parameterid;");
		$subresult = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid='$parameterid' AND valueid='$thisparameter'");
		if (!@mysqli_num_rows($subresult)) {
			$parameterfiltername = str_replace(" ","",$parametername);
			if (file_exists("$ashoppath/admin/filters/$parameterfiltername.inc.php")) {
				$filter_attributeid = $parameterid;
				$filter_attributename = @mysqli_result($paramresult, $i, "caption");
				$filter_productid = $item;
				$filter_attributevalue = $thisparameter;
				include "$ashoppath/admin/filters/$parameterfiltername.inc.php";
				$parameterid = $filter_attributeid;
				$item = $filter_productid;
				$thisparameter = $filter_attributevalue;
			}
			$now = time()+$timezoneoffset;
			$thisparameter = str_replace("'","&#39;",$thisparameter);
			$thisparameter = str_replace("\"","&quot;",$thisparameter);
			@mysqli_query($db, "INSERT INTO customparametervalues (parameterid, value, timestamp) VALUES ('$parameterid', '$thisparameter', '$now')");
			if (@mysqli_affected_rows($db) == 1) $thisparameter = @mysqli_insert_id($db);
		} else {
			$type .= $thisparameter."|";
			$attributeprice = @mysqli_result($subresult, 0, "price");
			if ($attributeprice) {
				$thisparameterprices = explode("|",$attributeprice);
				$price = $thisparameterprices[$pricelevel];
			}
		}
		$parameterstring .= $thisparameter."b";
	}
}

// Check the inventory status of this type of the product...
if ($useinventory) {
	if ($type) $type = substr($type,0,-1);
	$typeinventoryresult = @mysqli_query($db, "SELECT inventory FROM productinventory WHERE productid='$item' AND type='$type'");
	if (@mysqli_num_rows($typeinventoryresult)) {
		$typeinventory = @mysqli_result($typeinventoryresult,0,"inventory");
		if (!$typeinventory) $returnmessage = OUTOFSTOCK;
		if ($typeinventory < $quantity) $returnmessage = NOTENOUGHINSTOCK;
	}
}

// Check if this customer is allowed to buy this many items...
if (!empty($qtylimit) && $qtylimit > 0) {
	if (!$useinventory) {
		$basket = ashop_combineproducts($basket);
		$alreadyincart = ashop_checkproduct($item, $basket, "");
	}
	if (empty($customerid)) $returnmessage = MUSTLOGIN;
	else if ($alreadyincart == $qtylimit) $returnmessage = NOTALLOWEDQTY;
	// Check if this customer has already bought this item before...
	else {
		$previouslybought = 0;
		$checkproductstring = "b".$item."a";
		$previouspurchaseresult = @mysqli_query($db, "SELECT products FROM orders WHERE date IS NOT NULL AND date != '' AND products LIKE '%$checkproductstring%' AND customerid='$customerid'");
		while ($previouspurchaserow = @mysqli_fetch_array($previouspurchaseresult)) {
			$previouspurchaseproducts = $previouspurchaserow["products"];
			$previouslybought += ashop_checkproduct($item, $previouspurchaseproducts, "");
		}
		if ($quantity+$alreadyincart+$previouslybought > $qtylimit) {
			if ($alreadyincart+$previouslybought == $qtylimit) $returnmessage = NOTALLOWEDQTY;
			else {
				$quantity = $qtylimit-$alreadyincart-$previouslybought;
				$addreturnmessage = NOTALLOWEDQTY;
			}
		}
	}
}
if (!empty($qtytlimit) && $qtytlimit > 0) {
	if (!$useinventory) {
		$basket = ashop_combineproducts($basket);
		$alreadyincart = ashop_checkproduct($item, $basket, "");
	}
	if ($quantity+$alreadyincart > $qtytlimit) {
		if ($alreadyincart == $qtytlimit) $returnmessage = NOTALLOWEDQTY;
		else {
			$quantity = $qtytlimit-$alreadyincart;
			$addreturnmessage = NOTALLOWEDQTY;
		}
	}
}


// Store discount...
if ($salediscount) {
	$discountcookiestring = md5($item."ashopdiscounts");
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	$_SESSION["discount$salediscountid"] = $discountcookiestring;
	//setcookie("discount$salediscountid", $discountcookiestring);
} else if ($affiliatediscount) {
	$discountcookiestring = md5($item.$discountcode."ashopdiscounts");
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	$_SESSION["discount$salediscountid"] = $discountcookiestring;
	//setcookie("discount$salediscountid", $discountcookiestring);
}

if (empty($returnmessage)) {
	$oldqty = ashop_getquantity($item, $basket);
	// Calculate current subtotal of quantity pricing items...
	if ($qtytype == "1" || $qtytype == "2" || $qtytype == "3" || $qtytype == "4") {
		if ($qtytype == "1" || $qtytype == "3") $subtotalqty = $oldqty;
		else {
			if ($qtycategory) $subtotalqty = ashop_categoryqty($db, $basket, $qtycategory);
			else $subtotalqty = ashop_totalqty("$basket");
		}
		$oldsubtotal = ashop_subtotal($db, $item, $subtotalqty, $oldqty, $discountcookiestring, $price, $qtytype);
	} else $oldsubtotal = 0;
	// Calculate new subtotal of quantity pricing items...
	if ($qtytype == "1" || $qtytype == "2" || $qtytype == "3" || $qtytype == "4") {
		if (!$qtytype || $qtytype == "1" || $qtytype == "3") $subtotalqty = $oldqty+$quantity;
		else {
			if ($qtycategory) $subtotalqty = ashop_categoryqty($db, $basket, $qtycategory)+$quantity;
			else $subtotalqty = ashop_totalqty("$basket")+$quantity;
		}
		$subtotal = ashop_subtotal($db, $item, $subtotalqty, $oldqty+$quantity, $discountcookiestring, $price, $qtytype);
	} else {
		$subtotalqty = $oldqty+$quantity;
		$subtotal = ashop_subtotal($db, $item, $subtotalqty, $quantity, $discountcookiestring, $price, $qtytype);
	}
	if ($taxable && $displaywithtax == 1) $subtotal = $subtotal*$taxmultiplier;
	$currenttotal = str_replace($thousandchar,"",$currenttotal);
	$currenttotal = str_replace($decimalchar,".",$currenttotal);
	if (!is_numeric($currenttotal)) $currenttotal = 0;
	// Get currency rate if needed...
	if (isset($curr) && preg_match("/^[a-z]*$/", $curr) && strlen($curr) == 3 && $curr != $ashopcurrency) {
		$crate = getcurrency($curr);
		$subtotal = $subtotal*$crate;
	}
	$subtotal += $currenttotal;
	$subtotal -= $oldsubtotal;
	$subtotal = number_format($subtotal,$showdecimals,$decimalchar,$thousandchar);
} else $subtotal = $currenttotal;

if (empty($returnmessage)) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	if ($shoppingcart == "0") setcookie("basket","$quantity"."b"."$parameterstring$item"."a",0,'',"$ashopurldomain");
	else setcookie("basket","$basket$quantity"."b"."$parameterstring$item"."a",0,'',"$ashopurldomain");
	$name = str_replace("'", "&#039;", $name);
	if ($redirect) header("Location: $redirect?shop=$shop&returnurl=$returnurl");
	else {
		header('Content-type: text/plain');
		if (!empty($addreturnmessage)) echo "$subtotal|$addreturnmessage";
		else echo "$subtotal|$quantity $name ".HASBEENADDED;
	}
} else {
	$returnurl = $_SERVER["HTTP_REFERER"];
	$returnurl = str_replace($ashopurl."/","",$returnurl);
	$returnurl = str_replace($ashopsurl."/","",$returnurl);
	if ($redirect) header("Location: $redirect?shop=$shop&returnurl=$returnurl");
	else {
		header('Content-type: text/plain');
		echo "$subtotal|$returnmessage";
	}
}
?>