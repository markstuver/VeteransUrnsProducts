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

if (preg_match("/\Wcondensed.inc.php/",$_SERVER["PHP_SELF"])>0) {
	header("Location: ../../index.php");
	exit;
}

// Condensed layout...
if (!empty($productid) && !empty($productname)) {

	$description = strip_tags($description);
	echo "<table class=\"ashopitemboxcondensed\"><tr>";
	if ($productimage["thumbnail"]) {
		$imagesize = getimagesize("$ashoppath/prodimg/$productid/{$productimage["thumbnail"]}");
		if ($imagesize[0] == $thumbnailwidth) $imagesizestring = $imagesize[3];
		else {
			$imagesizeratio = $thumbnailwidth/$imagesize[0];
			$imageheight = $imagesize[1]*$imagesizeratio;
			$imagesizestring = "width=\"$thumbnailwidth\" height=\"$imageheight\"";
		}
		echo "
		<td width=\"$thumbnailwidth\" align=\"center\" valign=\"top\">";
		if ($producturl) echo "
		<a href=\"$producturl\"><img src=\"prodimg/$productid/{$productimage["thumbnail"]}\" class=\"ashopproductimage\" alt=\"$safeproductname\" $imagesizestring border=\"0\" /></a>";
		else echo "<img src=\"prodimg/$productid/{$productimage["thumbnail"]}\" alt=\"$safeproductname\" $imagesizestring />";
		if ($productimage["additionalimages"] > 0) echo "<br /><span class=\"ashopproductsmalltext\"><a class=\"gallery\" href=\"gallery.php?productid=$productid\">".MOREIMAGES."</a></span>";
		echo "</td>\n";
	}
	echo	"
	<td valign=\"top\">";
	if ($shoppingcart > "0" && $shoppingcart < "3" && !$pricehtml && !$overrideshoppingcart) {
		echo "<form name=\"product$productid\" action=\"\" method=\"post\" onsubmit=\"return buyitem('$buyproductid', product$productid.quantity.value)\" style=\"margin-bottom: 0px;\">";
	} else if (($shoppingcart == "0" && !$pricehtml) || $overrideshoppingcart) {
		if ($licensetext) echo "<form name=\"product$productid\" action=\"buy.php\" method=\"post\" onsubmit=\"return checkLicense(this)\" style=\"margin-bottom: 0px;\">";
		else echo "<form action=\"buy.php\" method=\"post\" style=\"margin-bottom: 0px;\">";
	}
	echo "
	<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\" width=\"100%\"><tr><td><span class=\"ashopproductname\">";
	if ($producturl) echo "<a href=\"$producturl\">$productname</a></span>";
	else echo "$productname</span>";
	if($enablecustomerlogin && !empty($_COOKIE["customersessionid"])) {
		echo "<span class=\"ashopproducttext\"> - </span><a href=\"javascript: addToWishlist('$buyproductid', $windowheight, ";
		if ($parametercount) echo "1";
		else echo "0";
		echo ")\"><span class=\"ashopproductwishlist\">".ADDTOWISHLIST."</span></a>";
	}
	if ($files && $producttype != "subscription" && $showfileinfo) echo " <span class=\"ashopproductinfo\">($filetypes$filesize)</span>";
	echo "<span class=\"ashopproducttext\"> - ";
	if ($previewurl) {
		if ($xspfcode) echo $xspfcode;
		else echo "<span class=\"ashopproducttext\"><a href=\"$previewurl\" target=\"_blank\">".DOWNLOADDEMO."</a></span><br />";
	}
	if (!$pricehtml) {
		echo PRICE.":";
		if ($regprice) echo " ".$regprice."<span class=\"ashopproductsale\">";
		echo " $price ";
		if ($regprice) echo "</span>";
	} else if ($pricehtml) echo $pricehtml;
	if ($avail) echo " $avail";
	if ($shoppingcart > "0" && $shoppingcart < "3" && !$overrideshoppingcart) {
		if ($pricehtml) {
			echo "<form name=\"product$productid\" action=\"\" method=\"post\" onsubmit=\"return buyitem('$buyproductid', product$productid.quantity.value)\" style=\"margin-bottom: 0px;\">";
		}
		if ($producttype == "subscription" || $shoppingcart == "2" || $pricehtml) echo "<input type=\"hidden\" name=\"quantity\" value=\"1\" />";
		else echo "&nbsp;&nbsp;&nbsp;&nbsp;".QTY.": <input class=\"ashopquantityfield\" type=\"text\" name=\"quantity\" size=\"2\" value=\"1\" /> ";
		
		if (!$pricehtml) echo "&nbsp;";
		else {
			echo "<div id=\"buybutton{$productid}\"";
			if (!$endprice) echo " style=\"display: none;\"";
			echo ">";
		}
		echo "<input type=\"image\" src=\"{$buttonpath}images/buysp-$lang.png\" class=\"ashopbutton\" id=\"addtocart$productid\" name=\"buy\" alt=\"".ADDTOCART."\" style=\"vertical-align: text-bottom; border: none;\" />\n";
		if ($pricehtml) echo "</div>";

	} else if ($shoppingcart == "0" || $overrideshoppingcart) {
		if ($pricehtml) {
			if ($licensetext) echo "<form name=\"product$productid\" action=\"buy.php\" method=\"post\" onsubmit=\"return checkLicense(this)\" style=\"margin-bottom: 0px;\">";
			else echo "<form action=\"buy.php\" method=\"post\" style=\"margin-bottom: 0px;\">";
		}
		echo " ";
		echo "<input type=\"hidden\" name=\"item\" value=\"$productid\" />
		<input type=\"hidden\" name=\"quantity\" value=\"1\" />
		<input type=\"hidden\" name=\"cat\" value=\"$cat\" />
		<input type=\"hidden\" name=\"shop\" value=\"$shop\" />
		<input type=\"hidden\" name=\"redirect\" value=\"checkout.php\" />";
		if ($licensetext) echo "<span class=\"ashopproductsmalltext\"><input type=\"checkbox\" name=\"acceptlicense\" /> ".AGREE1." <a href=\"javascript:showlicense($productid)\">".AGREE2."</a> ".AGREE3." $productname.</span>";
		
		if (!$pricehtml) echo "&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<input type=\"image\" border=\"0\" src=\"{$buttonpath}images/buy";
		if ($files && $producttype != "subscription") echo "d";
		echo "-$lang.png\" class=\"ashopbutton\" name=\"buy\" alt=\"".BUY."\" style=\"vertical-align: -40%; border: none;\" />";

	} else {
		// Price list mode...
		if (!$producturl) $producturl = "index.php?product=$productid";
		if (!$pricehtml) echo "&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<a href=\"$producturl\"><img src=\"{$buttonpath}images/buymi-$lang.png\" class=\"ashopbutton\" alt=\"".BUY."\" style=\"vertical-align: -40%;\" border=\"0\" /></a>";
	}
	if (!empty($description) && !$pricehtml) echo "<br />$description";
	echo "
	</span></td></tr></table></form>
	</td></tr></table>";
}
?>