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
session_start();
// Validate variables...
if (isset($cat) && !is_numeric($cat)) unset($cat);
if ($_GET["shop"] && is_numeric($_GET["shop"]) && $_GET["shop"] > 1) {
	$shop = $_GET["shop"];
	setcookie("shop",$shop);
}
if (isset($shop) && !is_numeric($shop)) $shop = "";
if (empty($shop)) {
	$shop = 1;
	$shopurlstring = "";
	$shophtmlstring = "";
} else {
	$shopurlstring = "&shop=$shop";
	$shophtmlstring = "&amp;shop=$shop";
}
if (isset($returnurl) && !ashop_is_url($returnurl)) unset($returnurl);
$basket = urldecode($basket);
$basket = html_entity_decode($basket);
$basket = str_replace("<","",$basket);
$basket = str_replace(">","",$basket);
if (!empty($addwlitem)) {
	$checkwlitem = str_replace("a","",$addwlitem);
	$checkwlitem = str_replace("b","",$checkwlitem);
	$checkwlitem = str_replace("d","",$checkwlitem);
	if (!is_numeric($checkwlitem)) $addwlitem = "";
	else if (substr($addwlitem,0,1) != "b") $addwlitem = "b".$addwlitem;
}
if (!empty($wlqty) && !is_numeric($wlqty)) $wlqty = 0;
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";
if (!empty($sid) && !ashop_is_md5($sid)) $sid = "";

// Get the domain for cookies...
$ashopurlarray = parse_url($ashopurl);
$ashopurlhost = $ashopurlarray['host'];
if (substr($ashopurlhost,0,4) == "www.") $ashopurldomain = substr($ashopurlhost,4);
else $ashopurldomain = $ashopurlhost;

// Connect to database...
$db = mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Unescape &-characters in returnurl...
$returnurl = str_replace("|","&",$returnurl);
if (isset($returnurl) && !ashop_is_url($returnurl)) unset($returnurl);

// Get customer profile and price level...
if (!empty($_COOKIE["customersessionid"])) {
	$customerresult = mysqli_query($db,"SELECT level, firstname, lastname FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$pricelevel = mysqli_result($customerresult,0,"level");
} else $pricelevel = 0;
if ($pricelevel > 0) {
	$templatefile = "wscart";
	$displaywithtax = $displaywswithtax;
} else $templatefile = "cart";

// Set basket variable from quantity, product and attribute fields...
$updateshipping = FALSE;
if (!$fixbackbutton) {
	if (!empty($wlqty) && !empty($addwlitem)) {
		$basket .= $wlqty.$addwlitem;
		$updateshipping = TRUE;
	}
}

// Keep wishlist active...
if (isset($_POST["wlsubmit_x"]) && (empty($_POST["email"]) || ashop_is_email($_POST["email"]))) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("wlemail",$_POST["email"]);
} else if (isset($_COOKIE["wlemail"]) && ashop_is_email($_COOKIE["wlemail"])) $_POST["email"] = $_COOKIE["wlemail"];

// Combine the same products in the basket cookie...
$basket = ashop_combineproducts($basket);
if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
$p3psent = TRUE;
setcookie("basket","$basket",0,'',"$ashopurldomain");

// Check if shipping and tax needs to be updated...
if (!empty($taxandshipping)) $checksid = md5($basket.$taxandshipping.$ashoppath);
else {
	$checksid = "";
	$sid = "";
}
if (($salestaxtype == "euvat" && $checksid != $sid) || (!empty($shipid) && $checksid != $sid)) {
	$updateshipping = TRUE;
	if ($salestaxtype && empty($shipid)) $shipid = "1";
}

// Update shipping if needed...
if ($updateshipping) {
	if ($shipid) $basketurl = "shipping.php?action=basket&";
	else $basketurl = "basket.php?";
    if ($returnurl) {
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$basketurl"."returnurl=$returnurl\"></head></html>";
			exit;
		} else {
			header ("Location: $basketurl"."returnurl=$returnurl");
			exit;
		}
	} else {
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$basketurl"."cat=$cat$shopurlstring\"></head></html>";
			exit;
		} else {
			header ("Location: $basketurl"."cat=$cat$shopurlstring");
			exit;
		}
	}
}

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
include "language/$lang/basket.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/cart.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

// Remove back button fix used in checkout.php...
if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
$p3psent = TRUE;
setcookie("fixbackbutton", "");

// Make sure the return URL is set if needed...
if (!$returnurl && !strstr($HTTP_REFERER, "index.php") && !strstr($HTTP_REFERER, "basket.php") && !strstr($HTTP_REFERER, "checkout.php") && !strstr($HTTP_REFERER, "shipping.php")) $returnurl = $HTTP_REFERER;

// Use relative paths in return URL...
$returnurl = str_replace("$ashopurl/","",$returnurl);
$returnurl = str_replace("$ashopsurl/","",$returnurl);
if (ini_get('magic_quotes_gpc')) $returnurl = stripslashes($returnurl);

// Unset the return URL if requested...
if ($returntoshop == "true") $returnurl = "";

// Empty shopping cart if requested...
if ($removeall != "") {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("basket","",0,'',"$ashopurldomain");
	setcookie ("taxandshipping", "");
	if ($returnurl) {
		$returnurl = str_replace("|","&",$returnurl);
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$returnurl\"></head></html>";
			exit;
		} else header("Location: $returnurl");
	} else {
		$catalogscript = "index.php";
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$catalogscript?cat=$cat$shopurlstring\"></head></html>";
			exit;
		} else header("Location: $catalogscript?cat=$cat$shopurlstring");
	}
}

// Check if PayPal Express Checkout is available...
if ($pricelevel > 0) $ppcheckresult = mysqli_query($db,"SELECT * FROM payoptions WHERE gateway='paypalec' AND userid='$shop' AND (retailonly = '' OR retailonly IS NULL OR retailonly = '0')");
else $ppcheckresult = mysqli_query($db,"SELECT * FROM payoptions WHERE gateway='paypalec' AND userid='$shop' AND (wholesaleonly = '' OR wholesaleonly IS NULL OR wholesaleonly = '0')");
$ppecid = mysqli_result($ppcheckresult,0,"payoptionid");

// Check if Google Checkout is available...
$gcocheckresult = mysqli_query($db,"SELECT * FROM payoptions WHERE gateway='googleco' AND userid='$shop'");
$gcoid = mysqli_result($gcocheckresult,0,"merchantid");
$gcokey = mysqli_result($gcocheckresult,0,"secret");
$gcotest = mysqli_result($gcocheckresult,0,"testmode");

// Parse shopping cart string...
$basket = ashop_applydiscounts($db, $basket);
if ($memberpayoptions) {
	$basket = ashop_memberproductstring($db, $basket, $shop);
	if (empty($basket)) $taxandshipping = 0;
}
$productsincart = ashop_parseproductstring($db, $basket);

// Remove a product...
if ($remove != 0){
	$items = explode("a", $basket);
    for ($i = 0; $i < count($items)-1; $i++) {
		if ($remove != $i+1) {
			$newbasket = $newbasket.$items[$i]."a";
		}
	}
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
    setcookie("basket","$newbasket",0,'',"$ashopurldomain");
	if ($shipid) $basketurl = "shipping.php?action=basket&";
	else $basketurl = "basket.php?";
    if ($returnurl) {
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$basketurl"."returnurl=$returnurl\"></head></html>";
			exit;
		} else header ("Location: $basketurl"."returnurl=$returnurl");
	} else {
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$basketurl"."cat=$cat$shopurlstring\"></head></html>";
			exit;
		} else header ("Location: $basketurl"."cat=$cat$shopurlstring");
	}
}

// Update quantity of a product...
if ($updateqty != 0){
	$newbasket = "";
	$items = explode("a", $basket);
    for ($i = 0; $i < count($items)-1; $i++) {
		if ($updateqty == $i+1) {
			$modifieditem = substr($items[$i],strpos($items[$i],"b"));
			$modifieditem = $qty.$modifieditem;
			$newbasket = $newbasket.$modifieditem."a";
		} else $newbasket = $newbasket.$items[$i]."a";
	}
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
    setcookie("basket","$newbasket",0,'',"$ashopurldomain");
	if ($shipid) $basketurl = "shipping.php?action=basket&";
	else $basketurl = "basket.php?";
    if ($returnurl) {
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$basketurl"."returnurl=$returnurl\"></head></html>";
			exit;
		} else header ("Location: $basketurl"."returnurl=$returnurl");
	} else {
		if (strstr($SERVER_SOFTWARE, "IIS")) {
			echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$basketurl"."cat=$cat$shopurlstring\"></head></html>";
			exit;
		} else header ("Location: $basketurl"."cat=$cat$shopurlstring");
	}
}

// Get currency rate if needed...
if (isset($curr) && preg_match("/^[a-z]*$/", $curr) && strlen($curr) == 3 && $curr != $ashopcurrency) $crate = getcurrency($curr);
else {
	$curr = "";
	$crate = 0;
}

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
$catalogscript = "index.php";

if ($device != "mobile") echo "
<table class=\"ashopcartframe1\">

  <tr align=\"center\"> 
    <td><br />
	  <table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" align=\"center\">
	  <tr><td align=\"right\" valign=\"top\">";
echo "
            <a href=\"";
if ($returnurl) echo "$returnurl\"";
else echo "$catalogscript?cat=$cat$shophtmlstring\"";
if ($device == "mobile") echo " data-ajax=\"false\" data-role=\"button\"";
echo ">";
if ($device == "mobile") echo CONTINUESHOPPING;
else echo "<img src=\"{$buttonpath}images/continue-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".CONTINUESHOPPING."\" />";
echo "</a>";
if ($device != "mobile") echo "
			</td><td align=\"left\" valign=\"top\">";
echo "<a href=\"basket.php?returnurl=";
$escreturnurl = str_replace("&","|",$returnurl);
echo $escreturnurl;
echo "&amp;cat=$cat$shophtmlstring&amp;removeall=true\"";
if ($device == "mobile") echo " data-ajax=\"false\" data-role=\"button\"";
echo ">";
if ($device == "mobile") echo EMPTYCART;
else echo "<img src=\"{$buttonpath}images/emptycart-$lang.png\" alt=\"".EMPTYCART."\" class=\"ashopbutton\" border=\"0\" />";
echo "</a>";
if ($shipid && $taxandshipping) {
	echo " <a href=\"";
	if ($returnurl) {
		$escreturnurl = str_replace("&","|",$returnurl); echo "shipping.php?changeshipping=true&amp;action=basket&amp;returnurl=$escreturnurl\"";
	} else echo "shipping.php?changeshipping=true&amp;action=basket&amp;cat=$cat$shophtmlstring\"";
	if ($device == "mobile") echo " data-role=\"button\" data-ajax=\"false\"";
	echo ">";
	if ($device == "mobile") echo CHANGESHIPPING;
	else echo "<img src=\"{$buttonpath}images/shipping-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".CHANGESHIPPING."\">";
	echo "</a>";
}
if ($device != "mobile") echo "</td></tr></table>";
echo "<p><span class=\"ashopcarttext\">
        ".CARTCONTAINS."</span></p>
      <br />
      <table class=\"ashopcartframe2\">
        <tr class=\"ashoptableheader\"> 
          <td> 

<table class=\"ashopcarttable\" border=\"1\" cellpadding=\"5\">
  <tr>";
 if ($showimagesincart) echo "<td align=\"center\"><span class=\"ashopcartlabel\">".PICTURE."</span></td>";
 echo "
	<td align=\"left\" style=\"padding-left: 18px;\"><span class=\"ashopcartlabel\">".QTY."</span></td>
	<td align=\"left\"><span class=\"ashopcartlabel\">".PRODUCT."</span></td>
	<td align=\"right\"><span class=\"ashopcartlabel\">".PRICE."</span></td>
	<td align=\"right\"><span class=\"ashopcartlabel\">".AMOUNT."</span></td>
	<td>&nbsp;</td></tr>
";

$physicalgoods = 0;
$subtotal = 0;
$mainsubtotal = 0;
$totalqty = ashop_totalqty($basket);
// Convert currency...
if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
	$tempcurrency = $ashopcurrency;
	$ashopcurrency = $curr;
}
if ($productsincart) {
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
		if ($thisproduct["taxable"] == "2" && $displaywithtax == 1 && !$taxandshipping) $price *= $taxmultiplier2;
		else if ($thisproduct["taxable"] && $displaywithtax == 1 && !$taxandshipping) $price *= $taxmultiplier;
		$pricetext = $thisproduct["pricetext"];
		$name = $thisproduct["name"];
		$parameters = $thisproduct["parameters"];

		// Check if this item has a recurring fee and disable PayPal Express and Google Checkout if it does...
		if (!empty($thisproduct["recurringprice"]) && $thisproduct["recurringprice"] > 0 && !$thisproduct["billtemplate"]) {
			$gcoid = "";
			$ppecid = "";
		}

		// Check if shipping or sales tax should be charged...
		if (!$shipto) {
			$sql="SELECT shipping, taxable FROM product WHERE productid=$productid";
			$result = mysqli_query($db,"$sql");
			$thisshipping = mysqli_result($result, 0, "shipping");
			$thistaxable = mysqli_result($result, 0, "taxable");
			if (($thisshipping || $thistaxable) && !($thisproduct["disableshipping"] && $thisproduct["disabletax"])) $physicalgoods = 1;
		}

		// Check discounts...
		if ($thisproduct["discounted"] == "true") {
			if (isset($_SESSION) && is_array($_SESSION)) foreach ($_SESSION as $cookiename=>$cookievalue) {
				if (strstr($cookiename,"discount")) {
					$discountid = str_replace("discount","",$cookiename);
					$sql="SELECT * FROM discount WHERE productid='$productid' AND discountid='$discountid'";
					$result2 = mysqli_query($db,"$sql");
					if (mysqli_num_rows($result2)) $thisproductdiscount = $cookievalue;
					else {
						$sql="SELECT * FROM storediscounts WHERE discountid='$discountid' AND categoryid!='' AND categoryid IS NOT NULL";
						$result2 = mysqli_query($db,"$sql");
						if (mysqli_num_rows($result2)) {
							$discountcategory = mysqli_result($result2, 0, "categoryid");
							$result3 = mysqli_query($db,"SELECT * FROM productcategory WHERE productid='$productid' AND categoryid='$discountcategory'");
							if (mysqli_num_rows($result3)) $thisproductdiscount = $cookievalue;
						}
					}
				}
			}
			$name = "$name (".DISCOUNTED.")";
		} else $thisproductdiscount = "0";

		// Calculate subtotal...
		if (!$thisproduct["qtytype"] || $thisproduct["qtytype"] == "1" || $thisproduct["qtytype"] == "3") $subtotalqty = $quantity;
		else {
			if (!$thisproduct["qtycategory"]) $subtotalqty = $totalqty;
			else $subtotalqty = ashop_categoryqty($db, $basket, $thisproduct["qtycategory"]);
		}

		$thistotal = ashop_subtotal($db, $productid, $subtotalqty, $quantity, $thisproductdiscount, $price, $thisproduct["qtytype"]);
		$mainsubtotal += $thistotal;

		if ($thisproduct["qtytype"] && empty($pricetext)) $price = $thistotal/$quantity;
		
		// Convert currency...
		if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
			$tempprice = $price;
			$price = $price*$crate;
			$price = round($price,2);
			$thistotal = ashop_subtotal($db, $productid, $subtotalqty, $quantity, $thisproductdiscount, $price, $thisproduct["qtytype"]);
			$subtotal += $thistotal;
		} else $subtotal = $mainsubtotal;
		if($pricetext) {
			$qtypricingresult = mysqli_query($db,"SELECT * FROM qtypricelevels WHERE productid='$productid'");
			if (mysqli_num_rows($qtypricingresult)) $price = $pricetext;
		} else {
			$showprice =ashop_subtotal($db, $productid, $subtotalqty, 1, $thisproductdiscount, $price, $thisproduct["qtytype"]);
			$showprice = number_format($showprice,$showdecimals,$decimalchar,$thousandchar);
		}

		echo "
			<tr>";
			if ($showimagesincart && $device != "mobile") {
				// Get product image info...
				$productimage = ashop_productimages($productid);
				echo "<td width=\"$thumbnailwidth\">";
				if ($productimage["thumbnail"]) echo "<img src=\"prodimg/$productid/{$productimage["thumbnail"]}\" alt=\"$name\" width=\"$thumbnailwidth\">";
				else echo "&nbsp;";
				echo "</td>";
			}
			$updateproduct = $productnumber+1;
			echo "<td align=\"center\" width=\"60\"><span class=\"ashopcartcontents\">";
			if ($shoppingcart == "1" && $device != "mobile") {
				echo "<form action=\"basket.php\" name=\"qtyform$updateproduct\" method=\"post\" style=\"margin: 0px;\">";
				if (!$thisproduct["qtytlimit"]) echo "<input type=\"text\" class=\"ashopquantityfield\" name=\"qty\" size=\"2\" value=\"$quantity\">";
				else {
					echo "<select name=\"qty\">";
					for ($qty = 1; $qty <= $thisproduct["qtytlimit"]; $qty++) {
						echo "<option value=\"$qty\"";
						if ($qty == $quantity) echo " selected";
						echo ">$qty</option>";
					}
					echo "</select>";
				}
				echo "<input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"shop\" value=\"$shop\"><input type=\"hidden\" name=\"updateqty\" value=\"$updateproduct\"> <input type=\"image\" src=\"images/icon_refresh.png\" alt=\"".UPDATE."\" />";
				echo "</form>";
			} else echo $quantity;
			echo "</span></td>
			<td align=\"left\"><span class=\"ashopcartcontents\">$name";
			if ($parameters) echo " $parameters";
			echo "</span></td>
			<td align=\"right\" width=\"100\"><span class=\"ashopcartcontents\">";
			if ($pricetext) echo $pricetext;
			else echo $currencysymbols[$ashopcurrency]["pre"].$showprice.$currencysymbols[$ashopcurrency]["post"];
			echo "</span></td>
			<td align=\"right\" width=\"100\"><span class=\"ashopcartcontents\">".$currencysymbols[$ashopcurrency]["pre"].number_format($thistotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span></td>
			<td width=\"20\" align=\"center\"><a href=\"basket.php?remove=$updateproduct";
			if ($returnurl) echo "&amp;returnurl=$returnurl";
			else echo "&amp;cat=$cat$shophtmlstring";
			echo "\"><img src=\"images/icon_delete.png\" alt=\"".REMOVE."\" border=\"0\"></a></td></tr>";
	}
}

// Show storewide discount of amount type if any...
if ($discountall) {
	$storediscountresult = mysqli_query($db,"SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='$'");
	if (mysqli_num_rows($storediscountresult)) {
		$storediscountrow = mysqli_fetch_array($storediscountresult);
		if ($storediscountrow["value"]) {
			$mainsubtotal -= $storediscountrow["value"];
			// Convert currency...
			if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
				$tempstorediscount = $storediscountrow["value"];
				$storediscountrow["value"] = $storediscountrow["value"]*$crate;
			}
			echo "<tr>";
			if ($showimagesincart) echo "<td>&nbsp;</td>";
			echo "<td align=\"center\">&nbsp;</td>
			<td align=\"left\"><span class=\"ashopcartcontents\">".DISCOUNT."</span></td>
			<td align=\"right\" width=\"120\">&nbsp;</td>
			<td align=\"right\" width=\"70\"><span class=\"ashopcartcontents\">-".$currencysymbols[$ashopcurrency]["pre"].number_format($storediscountrow["value"],$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span></td><td width=\"50\">&nbsp;</td></tr>";
			$subtotal -= $storediscountrow["value"];
		}
	}
}

// Check for free shipping discounts...
$shippingdiscountamount = 0;
if ($discountall) {
	$storediscountresult = mysqli_query($db,"SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='s'");
	if (mysqli_num_rows($storediscountresult)) {
		$shippingarray = ashop_gethandlingcost($taxandshipping);
		$shippingdiscountamount = $shippingarray["shipping"];
	}
}

// Extract and show sales tax and shipping.
if ($taxandshipping) {
  $items = explode("a", $taxandshipping);
  $arraycount = 1;
  if ($items[0] && count($items)==1) $arraycount = 0;
  for ($i = 0; $i < count($items)-$arraycount; $i++) {
	$thisitem = explode("b", $items[$i]);
	if ($thisitem[0] == "sh" || $thisitem[0] == "st" || $thisitem[0] == "sd") {
		if ($thisitem[0] == "sh") $name = SHIPPING;
		if ($thisitem[0] == "st") $name = SALESTAX;
		if ($thisitem[0] == "sd") $name = QTYDISCOUNT;
		$price = $thisitem[1];
		if ($shippingdiscountamount && $thisitem[0] == "sh") $price = 0;
		$thistotal = $price;
		if ($thisitem[0] == "sd") $mainsubtotal -= $price;
		else if ($thisitem[0] == "st" && $displaywithtax == 2) { }
		else $mainsubtotal += $price;

		// Convert currency...
		if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
			$tempprice = $price;
			$price = $price*$crate;
			$tempthistotal = $thistotal;
			$thistotal = $thistotal*$crate;
		}

		if ($thisitem[0] == "sd") $subtotal -= $price;
		else if ($thisitem[0] == "st" && $displaywithtax == 2) { }
		else $subtotal += $price;

		echo "
		<tr>";
		if ($showimagesincart) echo "<td>&nbsp;</td>";
		echo "<td>&nbsp;</td>
			<td align=\"left\">
			<span class=\"ashopcartcontents\">$name</span></td>
			<td align=\"right\">
			<span class=\"ashopcartcontents\">";
		if ($thisitem[0] == "sd") echo "-";
		echo $currencysymbols[$ashopcurrency]["pre"].number_format($price,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span></td>
			<td align=\"right\"><span class=\"ashopcartcontents\">";
		if ($thisitem[0] == "sd") echo "-";
		echo $currencysymbols[$ashopcurrency]["pre"].number_format($thistotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span></td>
			<td>&nbsp;</td></tr>";

	}
  }
} else if ($physicalgoods) {
	echo "
	<tr>";
	if ($showimagesincart) echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>
	<td align=\"left\">
	  <span class=\"ashopcartcontents\">".SHIPPINGORTAX."</span></td>
	<td align=\"right\">
	  <a href=\"shipping.php?cal=true&amp;action=basket";
	if ($returnurl) echo "&amp;returnurl=$returnurl";
	if ($cat) echo "&amp;cat=$cat";
	echo "\"><span class=\"ashopcartcontents\">".CALCULATE."</span></a></td>
	<td align=\"right\">&nbsp;</td>
	<td>&nbsp;</td></tr>";
}
  echo "
  <tr>";
  if ($showimagesincart) echo "<td align=\"right\" style=\"background-color:$categorycolor;\" colspan=\"4\">";
  else echo "<td align=\"right\" style=\"background-color:$categorycolor;\" colspan=\"3\">";

  // Make sure no negative amounts are shown...
  if ($subtotal < 0) $subtotal = 0;

  echo "<span class=\"ashopcarttotals\"><b>".TOTAL.":</b></span></td>
  <td align=\"right\">
  <span class=\"ashopcartcontents\">".$currencysymbols[$ashopcurrency]["pre"].number_format($subtotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
  // Convert back to main currency...
  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
	  $ashopcurrency = $tempcurrency;
	  echo "<br />(".$currencysymbols[$ashopcurrency]["pre"].number_format($mainsubtotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"].")";
  }
  echo "</span></td><td style=\"background-color:$categorycolor;\">&nbsp;</td></tr></table>";

  echo "
  <p align=\"right\"><a href=\"checkout.php?";
  if ($returnurl) { 
	  $escreturnurl = str_replace("&","|",$returnurl);
	  echo "returnurl=$escreturnurl&amp;sid=$sid\"";
  } else echo "cat=$cat$shophtmlstring&amp;sid=$sid\"";
  if ($device == "mobile") echo " data-ajax=\"false\" data-role=\"button\"";
  echo ">";
  if ($device == "mobile") echo CHECKOUT;
  else echo "<img class=\"ashopbutton\" border=\"0\" src=\"{$buttonpath}images/checkout-$lang.png\" alt=\"".CHECKOUT."\" />";
  echo "</a>";
  if ($ppecid) echo " <span class=\"ashoporderformlabel\">".THEWORDOR." <a href=\"checkout.php?payoption=$ppecid\"><img border=\"0\" align=\"absmiddle\" src=\"images/btn_xpressCheckoutsm.gif\" alt=\"Place order\"></a></span>";
  if ($gcoid) ashop_googlecheckoutbutton($db, $basket, $gcoid, $gcokey, $gcotest, 1, 1, 1);
  echo "
			  </p>
          </td>
        </tr>
      </table>";

if($enablecustomerlogin) {

	if (!empty($_POST["email"])) {
		$email = str_replace("'","",$_POST["email"]);
		$email = str_replace(";","",$email);
		if (!ashop_is_email($email)) $email = "";
	} else if (!empty($_GET["email"])) {
		$email = str_replace("'","",$_GET["email"]);
		$email = str_replace(";","",$email);
		if (!ashop_is_email($email)) $email = "";
	} else $email = "";

	if (!empty($email)) {
		$result = mysqli_query($db,"SELECT customerid,email,firstname,lastname FROM customer WHERE email='$email'");
		$customerid = mysqli_result($result,0,"customerid");
		$firstname = mysqli_result($result,0,"firstname");
		$lastname = mysqli_result($result,0,"lastname");
	}

	// Get wishlist for this customer...
	if (!empty($_COOKIE["customersessionid"])) {
		$result = mysqli_query($db,"SELECT customerid,email,firstname,lastname FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
		if (!empty($email)) {
			$loggedincustomerid = mysqli_result($result,0,"customerid");
			if ($loggedincustomerid == $customerid) $email = "";
		} else {
			$customerid = mysqli_result($result,0,"customerid");
			$firstname = mysqli_result($result,0,"firstname");
			$lastname = mysqli_result($result,0,"lastname");
		}
	}
	$result = mysqli_query($db,"SELECT * FROM savedcarts WHERE customerid='$customerid'");
	if (mysqli_num_rows($result)) $wishlist = mysqli_result($result,0,"productstring");
	
	// Remove a wishlist item...
	if ($removewl != 0 && empty($email)) {
		$items = explode("a", $wishlist);
		for ($i = 0; $i < count($items)-1; $i++) if ($removewl != $i+1) $newwishlist = $newwishlist.$items[$i]."a";
		mysqli_query($db,"UPDATE savedcarts SET productstring='$newwishlist' WHERE customerid='$customerid'");
		$wishlist = $newwishlist;
	}
	
if (!empty($firstname) || !empty($lastname)) echo "<p><span class=\"ashopcarttext\">$firstname $lastname's ".WISHLIST."</span></p>";
else echo "<p><span class=\"ashopcarttext\">".WISHLISTS."</span></p>";
echo "
      <p>
      <table class=\"ashopcartframe2\">
        <tr align=\"center\"> 
          <td>";

	// Show wishlist...
	$productsonlist = ashop_parseproductstring($db, $wishlist);
	echo "<form method=\"post\" action=\"basket.php\"";
	if ($device == "mobile") echo " data-ajax=\"false\"";
	echo "><span class=\"ashopcartcontents\">".LOADCART."<br /><input type=\"text\" name=\"email\" value=\"$email\" size=\"40\" /><input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\" /><input type=\"hidden\" name=\"cat\" value=\"$cat\" /> ";
	if ($device == "mobile") echo "</span><input type=\"submit\" data-role=\"button\" name=\"wlsubmit\" value=\"".SUBMIT."\" /><span class=\"ashopcartcontents\">";
	else echo "<input type=\"image\" src=\"{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" name=\"wlsubmit\" alt=\"".SUBMIT."\" align=\"absbottom\" />";
	echo "</span></form>";
	if (empty($_COOKIE["customersessionid"])) echo "<p align=\"center\"><span class=\"ashopcartcontents\">".LOGINORREGISTER."</span></p>";
	else echo "<br />";
	if ($productsonlist) {
		if ($device == "mobile") echo "<table width=\"98%\" style=\"border: 1px solid $itembordercolor;\" cellspacing=\"0\" cellpadding=\"5\">";
		else echo "<table width=\"80%\" style=\"border: 1px solid $itembordercolor;\" cellspacing=\"0\" cellpadding=\"5\">";
		foreach($productsonlist as $productnumber => $thisproduct) {
			$productid = $thisproduct["productid"];
			$productname = $thisproduct["name"];
			$parameters = $thisproduct["parameters"];
			$productresult = mysqli_query($db,"SELECT detailsurl FROM product WHERE productid='$productid'");
			$producturl = mysqli_result($productresult,0,"detailsurl");
			$thiswlitem = substr($thisproduct["segment"],strpos($thisproduct["segment"],"b"));
			echo "
			<form action=\"basket.php\" method=\"post\"";
			if ($device == "mobile") echo " data-ajax=\"false\"";
			echo "><tr>";
			if ($shoppingcart == "1") echo "<td align=\"left\" width=\"20\"><input type=\"text\" class=\"ashopquantityfield\" name=\"wlqty\" size=\"2\" value=\"1\" /></td>";
			else echo "<input type=\"hidden\" name=\"wlqty\" value=\"1\" />";
			echo "<td align=\"left\"";
			if ($device == "mobile") echo " style=\"padding-left: 15px;\"";
			echo "><span class=\"ashopcartcontents\">";
			if (!empty($producturl)) echo "<a href=\"$producturl\">";
			echo $productname;
			if ($parameters) echo " $parameters";
			if (!empty($producturl)) echo "</a>";
			echo "</span></td>
			<td align=\"right\" width=\"200\"><span class=\"ashopcartcontents\">";
			if ($shoppingcart != "1") $alreadyincart = ashop_checkproduct($productid, $basket);
			else $alreadyincart = 0;
			if ($alreadyincart == 0 || $shoppingcart == "1") {
				if ($device == "mobile") echo "<input type=\"submit\" data-role=\"button\" value=\"".ADDTOCART."\" />";
				else echo "
				<input type=\"image\" src=\"{$buttonpath}images/buysp-$lang.png\" alt=\"".ADDTOCART."\" class=\"ashopbutton\" border=\"0\" />";
			}
			if (!empty($_COOKIE["customersessionid"]) && empty($email)) {
				echo " <a href=\"basket.php?removewl=",$productnumber+1;
				if ($returnurl) echo "&amp;returnurl=$returnurl";
				else echo "&amp;cat=$cat$shophtmlstring";
				echo "\"><img src=\"{$buttonpath}images/delete-$lang.png\" alt=\"".DELETE."\" class=\"ashopbutton\" border=\"0\" /></a>";
			}
			echo "</td>
			</tr><input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\" /><input type=\"hidden\" name=\"cat\" value=\"$cat\" /><input type=\"hidden\" name=\"shop\" value=\"$shop\" /><input type=\"hidden\" name=\"email\" value=\"$email\" /><input type=\"hidden\" name=\"addwlitem\" value=\"$thiswlitem\" /></form>";
		}
		echo "</table>";
	}
	echo "</td></tr></table>";
}
echo "</td></tr></table>";

if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
?>