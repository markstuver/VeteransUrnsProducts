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

if (preg_match("/\Wdefault.inc.php/",$_SERVER["PHP_SELF"])>0) {
	header("Location: ../../index.php");
	exit;
}

// Default layout...
if (!empty($productid) && !empty($productname)) {
	$thumbnailshown = FALSE;
	if ($shoppingcart == 3 && empty($producturl)) $producturl = "index.php?product=$productid";
	echo "<div class=\"ashopitembackground\">
	<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\" width=\"100%\"><tr>";
	if ($productimage["thumbnail"]) {
		$imagesize = getimagesize("$ashoppath/prodimg/$productid/{$productimage["thumbnail"]}");
		if ($imagesize[0] == $thumbnailwidth) $imagesizestring = $imagesize[3];
		else {
			$imagesizeratio = $thumbnailwidth/$imagesize[0];
			$imageheight = $imagesize[1]*$imagesizeratio;
			$imagesizestring = "width=\"$thumbnailwidth\" height=\"$imageheight\"";
		}
		echo "
		<td align=\"center\" valign=\"top\"";
		if ($itemsperrow == 1) echo " width=\"$thumbnailwidth\">";
		else echo ">";
		if ($itemsperrow > 2) {
			echo "<p><span class=\"ashopproductname\">";
			if ($producturl) echo "<a href=\"$producturl\">$productname</a></span></p>";
			else echo "$productname</span>";
			if($enablecustomerlogin && !empty($_COOKIE["customersessionid"])) {
				echo " - <a href=\"javascript: addToWishlist('$buyproductid', $windowheight, ";
				if ($parametercount) echo "1";
				else echo "0";
				echo ")\"><span class=\"ashopproductwishlist\">".ADDTOWISHLIST."</span></a>";
			}
			echo "</p>";
		}
		if ($producturl) echo "
		<a href=\"$producturl\"><img src=\"prodimg/$productid/{$productimage["thumbnail"]}\" class=\"ashopproductimage\" alt=\"$safeproductname\" $imagesizestring border=\"0\" /></a>";
		else echo "<img src=\"prodimg/$productid/{$productimage["thumbnail"]}\" alt=\"$safeproductname\" $imagesizestring />";
		if ($productimage["additionalimages"] > 0) echo "<br /><span class=\"ashopproductsmalltext\"><a class=\"gallery\" href=\"gallery.php?productid=$productid\">".MOREIMAGES."</a></span>";
		echo "</td>\n";
		if ($itemsperrow > 2) echo "</tr>\n";
		$thumbnailshown = TRUE;
	}
	if ($itemsperrow > 2) echo "<tr>\n";
	echo "<td valign=\"top\">
	<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\" width=\"100%\"><tr>";
	if ($itemsperrow > 2) echo "<td align=\"center\">";
	else echo "<td align=\"left\">";
	if ($itemsperrow < 3 || !$thumbnailshown) {
		echo "<span class=\"ashopproductname\">";
		if ($producturl) echo "<a href=\"$producturl\">$productname</a></span>";
		else echo "$productname</span>";
		if($enablecustomerlogin && !empty($_COOKIE["customersessionid"])) {
			echo " - <a href=\"javascript: addToWishlist('$buyproductid', $windowheight, ";
			if ($parametercount) echo "1";
			else echo "0";
			echo ")\"><span class=\"ashopproductwishlist\">".ADDTOWISHLIST."</span></a>";
		}
	}
	if ($files && $producttype != "subscription" && (!file_exists("$ashoppath/includes/aws/aws-config.php") || !$isawsvideo)) {
		echo " <span class=\"ashopproductinfo\">".DIRECTDOWNLOAD;
		if ($showfileinfo) echo " ($filetypes$filesize)";
		echo "</span>";
	} else if (($subscriptiondir && $producttype == "subscription") || (file_exists("$ashoppath/includes/aws/aws-config.php") && $isawsvideo)) echo " <span class=\"ashopproductinfo\">".INSTANTACCESS."</span>";
	else if ($unlockkeys) echo " <span class=\"ashopproductinfo\">".EMAILDELIVERY."</span>";
	echo "</td></tr>
	<tr><td align=\"left\">";
	echo "<span class=\"ashopproducttext\">$description</span></td></tr>
	<tr>";
	if ($itemsperrow > 2) echo "<td align=\"center\">";
	else echo "<td align=\"left\">";

	if ($hideprice == "1") { }
	else if ($pricehtml) echo $pricehtml;
	else {
		if ($itemsperrow > 2) echo "<span class=\"ashopproductlabel\" style=\"float:none;\">";
		else echo "<span class=\"ashopproductlabel\">";
		echo PRICE.":</span><span class=\"ashopproducttext\">";
		if ($regprice) echo " ".$regprice."<span class=\"ashopproductsale\">";
		echo " $price</span>";
		if ($regprice) echo "</span>";
	}
	if ($avail) {
		echo ", $avail";
	}
	echo "</td></tr>";
	
	if ($previewurl) {
		if ($xspfcode) echo "<tr><td>$xspfcode</td></tr>";
		else echo "<tr><td><span class=\"ashopproducttext\"><a href=\"$previewurl\" target=\"_blank\">".DOWNLOADDEMO."</a></span></td></tr>";
	}
	if ($licensetext && $shoppingcart != "0") $shoppingcart = "3";
	echo "<tr>";
	if ($itemsperrow > 2) echo "<td align=\"center\">";
	else echo "<td align=\"left\">";
	if ($shoppingcart > "0" && $shoppingcart < "3" && !$overrideshoppingcart) {
		echo "
		<form name=\"product$productid\" action=\"\" method=\"post\" onsubmit=\"return buyitem('$buyproductid', product$productid.quantity.value)\">";
		if ($producttype == "subscription" || $shoppingcart == "2" || $pricehtml) echo "<input type=\"hidden\" name=\"quantity\" value=\"1\" />";
		else {
			echo "<span class=\"ashopproducttext\">".QUANTITY.": ";
			if (!empty($qtytlimit)) {
				echo "<select class=\"ashopquantityselect\" id=\"quantity$productid\" name=\"quantity\">\n";
				for ($qty = 1; $qty <= $qtytlimit; $qty++) echo "<option value=\"$qty\">$qty</option>\n";
				echo "</select>\n";
			} else echo "<input class=\"ashopquantityfield\" type=\"text\" name=\"quantity\" size=\"2\" value=\"1\" />";
			echo " </span>";
		}
		if (!$pricehtml) {
			if ($shoppingcart != "2") echo "&nbsp;";
		} else {
			echo "<div id=\"buybutton{$productid}\"";
			if (!$endprice) echo " style=\"display: none;\"";
			echo ">";
		}
		echo "<input type=\"image\" src=\"{$buttonpath}images/buysp-$lang.png\" class=\"ashopbutton\" id=\"addtocart$productid\" name=\"buy\" alt=\"".ADDTOCART."\" style=\"vertical-align: text-bottom; border: none;\" />";
		if ($pricehtml) echo "</div>";
		echo "</form>\n";
	} else if ($shoppingcart == "0" || ($shoppingcart != "3" && $overrideshoppingcart)) {
		if (!empty($gcoid)) ashop_googlecheckoutbutton($db, "1b{$productid}a", $gcoid, $gcokey, $gcotest);
		else {
			echo "
			<form name=\"product$productid\" action=\"buy.php\" method=\"post\"";
			if ($licensetext) echo " onsubmit=\"return checkLicense(this)\"";
			echo ">
			<input type=\"hidden\" name=\"item\" value=\"$productid\" />
			<input type=\"hidden\" name=\"quantity\" value=\"1\" />
			<input type=\"hidden\" name=\"cat\" value=\"$cat\" />
			<input type=\"hidden\" name=\"shop\" value=\"$shop\" />
			<input type=\"hidden\" name=\"redirect\" value=\"checkout.php\" />";
			if ($salediscount) echo "<input type=\"hidden\" name=\"discount\" value=\"$discountcode\" />";
			if ($licensetext) echo "<span class=\"ashopproductsmalltext\"><input type=\"checkbox\" name=\"acceptlicense\"> ".AGREE1." <a href=\"javascript:showlicense($productid)\">".AGREE2."</a> ".AGREE3." $productname.</span><br /><br />";
			echo "<input type=\"image\" border=\"0\" src=\"{$buttonpath}images/buy";
			if ($files && $producttype != "subscription") echo "d";
			echo "-$lang.png\" class=\"ashopbutton\" name=\"buy\" alt=\"".BUY."\" />";
			echo "</form>";
		}
	} else {
		// Price list mode...
		if (!$producturl) $producturl = "index.php?product=$productid";
		if ($itemsperrow == 1) {
			echo "<div style=\"float: right; margin-top: -30px;\"><a href=\"$producturl\"><img src=\"{$buttonpath}images/buymi-$lang.png\" class=\"ashopbutton\" alt=\"".MOREINFO."\" border=\"0\" /></a></div>";
		} else {
			echo "<a href=\"$producturl\"><img src=\"{$buttonpath}images/buymi-$lang.png\" class=\"ashopbutton\" alt=\"".MOREINFO."\" border=\"0\" /></a>";
		}
	}
	echo "</td></tr></table></td></tr></table></div>";
}
?>