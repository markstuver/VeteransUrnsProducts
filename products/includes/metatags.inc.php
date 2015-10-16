<?php
// AShop
// Copyright 2013 - AShop Software - http://www.ashopsoftware.com
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

if (!isset($product)) $product = 0;
$url = "";
if (isset($product) && !is_numeric($product)) $url = $ashopurl."/catalog/".$product.".html";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Convert URI and make it safe...
if (empty($url)) {
	if (!isset($_SERVER['REQUEST_URI']) and isset($_SERVER['SCRIPT_NAME'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['QUERY_STRING']) and !empty($_SERVER['QUERY_STRING'])) $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
	}
	if ($_SERVER['HTTPS'] == "on") $url = "https://";
	else $url = "http://";
	$url .= $HTTP_HOST.$REQUEST_URI;
}
if (strpos($url,"/catalog/")) {
	$url = str_replace("/catalog/vendor/","/catalog/",$url);
	$url = stripslashes($url);
	$url = @mysqli_real_escape_string($db, $url);
	$url = str_replace("\'","",$url);
	$url = str_replace("\"","",$url);
	$url = str_replace("\n","",$url);
	$url = str_replace(";","",$url);
} else $url = "";

// Get product by url...
if (!empty($url)) {
	$productresult = @mysqli_query($db, "SELECT * FROM product WHERE detailsurl='$url' AND (copyof='' OR copyof IS NULL)");
	$productrow = @mysqli_fetch_array($productresult);
	if (!empty($productrow["metakeywords"])) $ashopmetakeywords = strip_tags($productrow["metakeywords"]);
	if (!empty($productrow["metadescription"])) $ashopmetadescription = strip_tags($productrow["metadescription"]);
	if (!empty($productrow["name"])) $ashoptitle = $ashopname." - ".$productrow["name"];
	else $ashoptitle = $ashopname;
	$ashoptitle = strip_tags($ashoptitle);
	if (!empty($productrow["productid"])) {
		// Get product image info...
		$productimage = ashop_productimages($productrow["productid"]);
		if (!empty($productimage["product"])) $ashopimage = $productimage["product"];
		else $ashopimage = "$ashopimage/images/logo.gif";
	} else $ashopimage = "$ashopimage/images/logo.gif";

	// If this product belongs to a vendor, use their settings...
	if ($membershops == "1") {
		$shop = $productrow["userid"];
		if (!empty($shop) && $shop > 1) {
			$shopresult = @mysqli_query($db, "SELECT * FROM user WHERE userid='$shop'");
			$shoprow = @mysqli_fetch_array($shopresult);
			if ($shoprow["shopname"]) $ashopname = $shoprow["shopname"];
			if ($shoprow["theme"]) $ashoptheme = $shoprow["theme"];
			if ($shoprow["username"]) $ashopuser = $shoprow["username"];
			if ($shoprow["bgcolor"]) $bgcolor = $shoprow["bgcolor"];
			if ($shoprow["textcolor"]) $textcolor = $shoprow["textcolor"];
			if ($shoprow["linkcolor"]) $linkcolor = $shoprow["linkcolor"];
			if ($shoprow["formsbgcolor"]) $formsbgcolor = $shoprow["formsbgcolor"];
			if ($shoprow["formstextcolor"]) $formstextcolor = $shoprow["formstextcolor"];
			if ($shoprow["itembordercolor"]) $itembordercolor = $shoprow["itembordercolor"];
			if ($shoprow["itembgcolor"]) $itembgcolor = $shoprow["itembgcolor"];
			if ($shoprow["itemtextcolor"]) $itemtextcolor = $shoprow["itemtextcolor"];
			if ($shoprow["categorycolor"]) $categorycolor = $shoprow["categorycolor"];
			if ($shoprow["categorytextcolor"]) $categorytextcolor = $shoprow["categorytextcolor"];
			if ($shoprow["selectedcategory"]) $selectedcategory = $shoprow["selectedcategory"];
			if ($shoprow["selectedcategorytext"]) $selectedcategorytext = $shoprow["selectedcategorytext"];
			if ($shoprow["font"]) $font = $shoprow["font"];
			if ($shoprow["pageheader"]) $pageheader = $shoprow["pageheader"];
			if ($shoprow["pagefooter"]) $pagefooter = $shoprow["pagefooter"];
			if ($shoprow["metakeywords"]) $ashopmetakeywords = $shoprow["metakeywords"];
			if ($shoprow["metadescription"]) $ashopmetadescription = $shoprow["metadescription"];
			if ($shoprow["alertcolor"]) $alertcolor = $shoprow["alertcolor"];
			if ($shoprow["catalogheader"]) $catalogheader = $shoprow["catalogheader"];
			if ($shoprow["catalogheadertext"]) $catalogheadertext = $shoprow["catalogheadertext"];
			if ($shoprow["formsbordercolor"]) $formsbordercolor = $shoprow["formsbordercolor"];
			if ($shoprow["itemborderwidth"]) $itemborderwidth = $shoprow["itemborderwidth"];
			if ($shoprow["fontsize1"]) $fontsize1 = $shoprow["fontsize1"];
			if ($shoprow["fontsize2"]) $fontsize2 = $shoprow["fontsize2"];
			if ($shoprow["fontsize3"]) $fontsize3 = $shoprow["fontsize3"];
			if ($shoprow["tablesize1"]) $tablesize1 = $shoprow["tablesize1"];
			if ($shoprow["tablesize2"]) $tablesize2 = $shoprow["tablesize2"];
		}
	}

// Get product by ID...
} else if (!empty($product) && is_numeric($product)) {
	$productresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$product'");
	$productrow = @mysqli_fetch_array($productresult);
	if (!empty($productrow["metakeywords"])) $ashopmetakeywords = strip_tags($productrow["metakeywords"]);
	if (!empty($productrow["metadescription"])) $ashopmetadescription = strip_tags($productrow["metadescription"]);
	if (!empty($productrow["name"])) $ashoptitle = $ashopname." - ".$productrow["name"];
	else $ashoptitle = $ashopname;
	$ashoptitle = strip_tags($ashoptitle);

	// Get product image info...
	$productimage = ashop_productimages($product);
	if (!empty($productimage["product"])) $ashopimage = $productimage["product"];
	else $ashopimage = "$ashopimage/images/logo.gif";

	// If this product belongs to a vendor, use their settings...
	if ($membershops == "1") {
		$shop = $productrow["userid"];
		if (!empty($shop) && $shop > 1) {
			$shopresult = @mysqli_query($db, "SELECT * FROM user WHERE userid='$shop'");
			$shoprow = @mysqli_fetch_array($shopresult);
			if ($shoprow["shopname"]) $ashopname = $shoprow["shopname"];
			if ($shoprow["theme"]) $ashoptheme = $shoprow["theme"];
			if ($shoprow["username"]) $ashopuser = $shoprow["username"];
			if ($shoprow["bgcolor"]) $bgcolor = $shoprow["bgcolor"];
			if ($shoprow["textcolor"]) $textcolor = $shoprow["textcolor"];
			if ($shoprow["linkcolor"]) $linkcolor = $shoprow["linkcolor"];
			if ($shoprow["formsbgcolor"]) $formsbgcolor = $shoprow["formsbgcolor"];
			if ($shoprow["formstextcolor"]) $formstextcolor = $shoprow["formstextcolor"];
			if ($shoprow["itembordercolor"]) $itembordercolor = $shoprow["itembordercolor"];
			if ($shoprow["itembgcolor"]) $itembgcolor = $shoprow["itembgcolor"];
			if ($shoprow["itemtextcolor"]) $itemtextcolor = $shoprow["itemtextcolor"];
			if ($shoprow["categorycolor"]) $categorycolor = $shoprow["categorycolor"];
			if ($shoprow["categorytextcolor"]) $categorytextcolor = $shoprow["categorytextcolor"];
			if ($shoprow["selectedcategory"]) $selectedcategory = $shoprow["selectedcategory"];
			if ($shoprow["selectedcategorytext"]) $selectedcategorytext = $shoprow["selectedcategorytext"];
			if ($shoprow["font"]) $font = $shoprow["font"];
			if ($shoprow["pageheader"]) $pageheader = $shoprow["pageheader"];
			if ($shoprow["pagefooter"]) $pagefooter = $shoprow["pagefooter"];
			if ($shoprow["metakeywords"]) $ashopmetakeywords = $shoprow["metakeywords"];
			if ($shoprow["metadescription"]) $ashopmetadescription = $shoprow["metadescription"];
			if ($shoprow["alertcolor"]) $alertcolor = $shoprow["alertcolor"];
			if ($shoprow["catalogheader"]) $catalogheader = $shoprow["catalogheader"];
			if ($shoprow["catalogheadertext"]) $catalogheadertext = $shoprow["catalogheadertext"];
			if ($shoprow["formsbordercolor"]) $formsbordercolor = $shoprow["formsbordercolor"];
			if ($shoprow["itemborderwidth"]) $itemborderwidth = $shoprow["itemborderwidth"];
			if ($shoprow["fontsize1"]) $fontsize1 = $shoprow["fontsize1"];
			if ($shoprow["fontsize2"]) $fontsize2 = $shoprow["fontsize2"];
			if ($shoprow["fontsize3"]) $fontsize3 = $shoprow["fontsize3"];
			if ($shoprow["tablesize1"]) $tablesize1 = $shoprow["tablesize1"];
			if ($shoprow["tablesize2"]) $tablesize2 = $shoprow["tablesize2"];
		}
	}

// Set the title to the selected category name...
} else if (!empty($cat) && is_numeric($cat)) {
    $sql="SELECT name, description FROM category WHERE categoryid = '$cat'";
    $catresult = @mysqli_query($db, $sql);
    $seocategoryname = @mysqli_result($catresult, 0, "name");
	$seocategorydescription = @mysqli_result($catresult, 0, "description");
	if (!empty($seocategorydescription)) $ashopmetadescription = strip_tags($seocategorydescription);
	if (!empty($seocategoryname)) $ashoptitle = $ashopname." - ".$seocategoryname;
	else $ashoptitle = $ashopname;
	$ashoptitle = strip_tags($ashoptitle);
	$ashopimage = "$ashopimage/images/logo.gif";
} else if (!empty($searchstring)) {
	if (get_magic_quotes_gpc()) {
		$searchstring = stripslashes($searchstring);
	}
	$searchstring = strip_tags($searchstring);
	$searchstring = @mysqli_real_escape_string($db, $searchstring);
	$searchstring = str_replace("\"","",$searchstring);
	$searchstring = str_replace("%22","",$searchstring);
	$ashoptitle = $ashopname." - ".$searchstring;
	$ashopimage = "$ashopimage/images/logo.gif";
} else {
	$ashoptitle = $ashopname;
	$ashopimage = "$ashopimage/images/logo.gif";
}
?>