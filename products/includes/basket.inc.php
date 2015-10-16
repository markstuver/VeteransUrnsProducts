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

if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";

// Validate variables...
$basket = urldecode($basket);
$basket = html_entity_decode($basket);
$basket = str_replace("<","",$basket);
$basket = str_replace(">","",$basket);
if (!empty($returnurl) && !ashop_is_url($returnurl)) $returnurl = "";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$returnurl = str_replace("|","&",$returnurl);

// Combine the same products in the basket cookie...
$basket = ashop_combineproducts($basket);

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/basket.inc.php";

// Parse shopping cart string...
$basket = ashop_applydiscounts($db, $basket);
$productsincart = ashop_parseproductstring($db, $basket);

$physicalgoods = 0;
$subtotal = 0;
$totalqty = ashop_totalqty($basket);
echo "<table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\">";
if ($productsincart) {
	foreach($productsincart as $productnumber => $thisproduct) {
		$productid = $thisproduct["productid"];
		$quantity = $thisproduct["quantity"];
		$price = $thisproduct["price"];
		$taxmultiplier = 1+($taxpercentage/100);
		if ($thisproduct["taxable"] && $displaywithtax == 1 && !$taxandshipping) $price *= $taxmultiplier;
		$pricetext = $thisproduct["pricetext"];
		$showprice = number_format($price,2,'.','');
		if($pricetext) {
			$qtypricingresult = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$productid'");
			if (@mysqli_num_rows($qtypricingresult)) $showprice = $pricetext;
		}
		$name = $thisproduct["name"];
		$parameters = $thisproduct["parameters"];

		// Check if shipping or sales tax should be charged...
		if (!$shipto) {
			$sql="SELECT shipping, taxable FROM product WHERE productid=$productid";
			$result = @mysqli_query($db, "$sql");
			$thisshipping = @mysqli_result($result, 0, "shipping");
			$thistaxable = @mysqli_result($result, 0, "taxable");
			if (($thisshipping || $thistaxable) && !($thisproduct["disableshipping"] && $thisproduct["disabletax"])) $physicalgoods = 1;
		}

		// Check discounts...
		if ($thisproduct["discounted"] == "true") {
			eval ("\$thisproductdiscount = \$discount$productid;");
			$name = "$name (".DISCOUNTED.")";
		} else $thisproductdiscount = "0";

		// Calculate subtotal...
		if (!$thisproduct["qtytype"] || $thisproduct["qtytype"] == "1" || $thisproduct["qtytype"] == "3") $subtotalqty = $quantity;
		else {
			if (!$thisproduct["qtycategory"]) $subtotalqty = $totalqty;
			else $subtotalqty = ashop_categoryqty($db, $basket, $thisproduct["qtycategory"]);
		}
		$thistotal = ashop_subtotal($db, $productid, $subtotalqty, $quantity, $thisproductdiscount, $price, $thisproduct["qtytype"]);
		$subtotal += $thistotal;

		echo "<tr><td align=\"left\" valign=\"top\"><FONT SIZE=\"1\" FACE=\"$font\" COLOR=\"$formstextcolor\"><span class=\"fontsize1\">$quantity: $name";
			if ($parameters) echo " $parameters";
			echo "</span></FONT></td>
			<td align=\"right\" valign=\"top\"><FONT SIZE=\"1\" FACE=\"$font\" COLOR=\"$formstextcolor\"><span class=\"fontsize1\">".$currencysymbols[$ashopcurrency]["pre"].number_format($thistotal,2,'.','').$currencysymbols[$ashopcurrency]["post"]."</span></FONT></td></tr>";
	}
	echo "<tr><td colspan=\"2\"><hr></td></tr>
  <tr><td align=\"left\" valign=\"top\"><b><FONT FACE=\"$font\" SIZE=\"1\"><span class=\"fontsize1\">".SUBTOTAL.":</span></FONT></b></td><td align=\"right\" valign=\"top\"><FONT FACE=\"$font\" SIZE=\"1\"><span class=\"fontsize1\">".$currencysymbols[$ashopcurrency]["pre"].number_format($subtotal,2,'.','').$currencysymbols[$ashopcurrency]["post"]."</span></FONT></td></tr>";
} else echo "<tr><td colspan=\"2\"><FONT FACE=\"$font\" SIZE=\"1\"><span class=\"fontsize1\">".CARTEMPTY."</span></FONT></td></tr>";
echo "</TABLE>";
?>