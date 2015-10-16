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

if (preg_match("/\Wdefault.inc.php/",$_SERVER["PHP_SELF"])>0) {
	header("Location: ../../index.php");
	exit;
}

// Default layout...
if (!empty($productid) && !empty($productname)) {
	$thumbnailshown = FALSE;
	echo "<li data-icon=\"false\" style=\"padding-left: 15px; padding-right: 5px;\">";
	if ($productimage["thumbnail"]) {
		$imagesize = getimagesize("$ashoppath/prodimg/$productid/{$productimage["thumbnail"]}");
		if ($imagesize[0] == $thumbnailwidth) $imagesizestring = $imagesize[3];
		else {
			$imagesizeratio = $thumbnailwidth/$imagesize[0];
			$imageheight = $imagesize[1]*$imagesizeratio;
			$imagesizestring = "width=\"$thumbnailwidth\" height=\"$imageheight\"";
		}
		if ($producturl) echo "
		<p><a href=\"$producturl\" data-ajax=\"false\"><img src=\"prodimg/$productid/{$productimage["thumbnail"]}\" class=\"ashopproductimage\" alt=\"$safeproductname\" $imagesizestring border=\"0\" /></a></p>";
		else echo "<p><a href=\"#\" data-ajax=\"false\"><img src=\"prodimg/$productid/{$productimage["thumbnail"]}\" class=\"ashopproductimage\" alt=\"$safeproductname\" $imagesizestring border=\"0\" /></a></p>";
		$thumbnailshown = TRUE;
	}
	echo "
	<table border=\"0\" cellspacing=\"0\" align=\"center\" width=\"100%\"><tr><td style=\"white-space: normal;\"><span class=\"ashopproductname\">";
	if ($producturl) echo "<a href=\"$producturl\" data-ajax=\"false\">$productname</a></span>";
	else echo "$productname</span>";
	if($enablecustomerlogin && !empty($_COOKIE["customersessionid"])) {
		echo " - <a href=\"javascript: addToWishlist('$buyproductid', $windowheight, ";
		if ($parametercount) echo "1";
		else echo "0";
		echo ")\"><span class=\"ashopproductwishlist\">".ADDTOWISHLIST."</span></a>";
	}
	if ($files && $producttype != "subscription" && (!file_exists("$ashoppath/includes/aws/aws-config.php") || !$isawsvideo)) {
		echo " <span class=\"ashopproductinfo\">".DIRECTDOWNLOAD;
		if ($showfileinfo) echo " ($filetypes$filesize)";
		echo "</span>";
	} else if (($subscriptiondir && $producttype == "subscription") || (file_exists("$ashoppath/includes/aws/aws-config.php") && $isawsvideo)) echo " <span class=\"ashopproductinfo\">".INSTANTACCESS."</span>";
	else if ($unlockkeys) echo " <span class=\"ashopproductinfo\">".EMAILDELIVERY."</span>";
	$mobiledescription = str_replace("<p>","<br />",$description);
	$mobiledescription = str_replace("</p>","",$mobiledescription);
	$mobiledescription = str_replace("<h1>","<strong>",$mobiledescription);
	$mobiledescription = str_replace("</h1>","</strong>",$mobiledescription);
	$mobiledescription = str_replace("<h2>","<strong>",$mobiledescription);
	$mobiledescription = str_replace("</h2>","</strong>",$mobiledescription);
	$mobiledescription = str_replace("<h3>","<strong>",$mobiledescription);
	$mobiledescription = str_replace("</h3>","</strong>",$mobiledescription);
	$mobiledescription = str_replace("<h4>","<strong>",$mobiledescription);
	$mobiledescription = str_replace("</h4>","</strong>",$mobiledescription);
	$mobiledescription = str_replace("<h5>","<strong>",$mobiledescription);
	$mobiledescription = str_replace("</h5>","</strong>",$mobiledescription);
	$mobiledescription = str_replace("<h6>","<strong>",$mobiledescription);
	$mobiledescription = str_replace("</h6>","</strong>",$mobiledescription);
	echo "</td></tr>
	<tr><td><span class=\"ashopproducttext\" style=\"white-space:pre-wrap;padding-right:20px;\">$mobiledescription</span></td></tr>
	<tr><td>";

	if ($pricehtml) echo $pricehtml;
	else {
		echo "<span class=\"ashopproductlabel\">".PRICE.":</span><span class=\"ashopproducttext\">";
		if ($regprice) echo " ".$regprice."<span class=\"ashopproductsale\">";
		echo " $price</span>";
		if ($regprice) echo "</span>";
	}
	if ($avail) {
		echo ", $avail";
	}
	echo "</td></tr>";
	
	if ($previewurl) {
		if ($xspfcode) echo "<tr><td><br />$xspfcode</td></tr>";
		else echo "<tr><td><span class=\"ashopproducttext\"><a href=\"$previewurl\" target=\"_blank\">".DOWNLOADDEMO."</a></span></td></tr>";
	}
	echo "<tr><td>";
	if ($shoppingcart > "0" && $shoppingcart < "3" && !$overrideshoppingcart) {
		echo "
		<form name=\"product$productid\" action=\"\" method=\"post\" onsubmit=\"return buyitem('$buyproductid', product$productid.quantity.value)\" data-ajax=\"false\">";
		if ($producttype == "subscription" || $shoppingcart == "2" || $pricehtml) echo "<input type=\"hidden\" name=\"quantity\" value=\"1\" />";
		else echo "<span class=\"ashopproducttext\">".QUANTITY.": <input class=\"ashopquantityfield\" type=\"text\" name=\"quantity\" size=\"2\" value=\"1\" /> </span>";
		if (!$pricehtml) {
			if ($shoppingcart != "2") echo "&nbsp;";
		} else {
			echo "<div id=\"buybutton{$productid}\"";
			if (!$endprice) echo " style=\"display: none;\"";
			echo ">";
		}
		echo "<input type=\"submit\" class=\"ashopbutton\" id=\"addtocart$productid\" name=\"buy\" value=\"".ADDTOCART."\" />";
		if ($pricehtml) echo "</div>";
		echo "</form>\n";
	} else if ($shoppingcart == "0" || $overrideshoppingcart) {
		if (!empty($gcoid)) ashop_googlecheckoutbutton($db, "1b{$productid}a", $gcoid, $gcokey, $gcotest);
		else {
			echo "
			<form name=\"product$productid\" action=\"buy.php\" method=\"post\"";
			if ($licensetext) echo " onsubmit=\"return checkLicense(this)\" data-ajax=\"false\"";
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
		echo "<div style=\"float: right; margin-top: -30px;\"><a href=\"$producturl\" data-role=\"button\" data-ajax=\"false\">".MOREINFO."</a></div>";
	}
	echo "</td></tr></table></li>";
}
?>