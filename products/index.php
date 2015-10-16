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

if (!headers_sent() && isset($_COOKIE["fixbackbutton"])) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("fixbackbutton", "");
}

if (!headers_sent() && isset($_COOKIE["wsjconsent_consent"]) && $_COOKIE["wsjconsent_consent"] == "true") {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	$year = date("Y",time());
	$year += 20;
	setcookie("wsjconsent_consent","true", mktime(0,0,0,12,1,$year), "/");
}

// Initialize the storefront page...
include "admin/config.inc.php";
include "admin/ashopfunc.inc.php"; 
include "includes/metatags.inc.php";
include "includes/sortorder.inc.php";
include "includes/theme.inc.php";

// Validate language code...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;

// Validate customer session...
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";
if (!ashop_is_md5($customersessionid)) $customersessionid = "";

// Check if the shop parameter is provided...
if (empty($shop) || !is_numeric($shop)) $currentshop = "";
else $currentshop = $shop;

// Remember the shop parameter if separate payment options should be used...
if (!empty($_GET["shop"]) && is_numeric($_GET["shop"]) && $memberpayoptions) setcookie("shop",$shop,0,"/");
else if (!empty($_COOKIE["shop"])) {
	//setcookie("shop","",time() - 42000,"/");
	setcookie("shop","");
}

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Check if a mobile device is being used...
$device = ashop_mobile();

// Check if this is an upsell, the storefront or a categories/product page...
if (!empty($specialoffer) && $specialoffer == "true") $templatename = "upsell";
//else if ((!empty($cat) && is_numeric($cat)) || (!empty($product) && is_numeric($product)) || strstr($_SERVER["REQUEST_URI"],"/catalog/") || !empty($searchstring)) $templatename = "catalog2";
else $templatename = "catalog";

if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatename-$lang.html")) $templatepath = "$ashoppath$templatepath/$templatename-$lang.html";
else $templatepath = "$ashoppath$templatepath/$templatename.html";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/$templatename.html")) $templatepath = "$ashoppath/members/files/$ashopuser/$templatename.html";

$fp = @fopen ("$templatepath","r");
if ($fp) {
	while (!feof ($fp)) $template .= fgets($fp, 4096);
	fclose($fp);
} else die("Template file missing!");

// Remember catalog parameters when product details pages are viewed...
if (!empty($product) && (empty($cat) || empty($exp))) {
	$refererquery = str_replace($ashopurl,"",$_SERVER["HTTP_REFERER"]);
	$refererquery = str_replace($ashopsurl,"",$refererquery);
	$refererquery = substr($refererquery,strpos($refererquery,"?")+1);
	$refererquery = explode("&",$refererquery);
	foreach ($refererquery as $refererquerypart) {
		$refererquerypart = explode("=",$refererquerypart);
		if ($refererquerypart[0] == "cat" && empty($cat)) $cat = $refererquerypart[1];
		if ($refererquerypart[0] == "exp" && empty($exp)) $exp = $refererquerypart[1];
	}
}

// Parse meta tags...
$template = str_replace("<!-- AShopmetakeywords -->", $ashopmetakeywords, $template);
$template = str_replace("<!-- AShopmetadescription -->", $ashopmetadescription, $template);
$template = str_replace("<!-- AShopimage -->", $ashopimage, $template);

// Get the base URL for this shopping cart...
$ashopbaseurl = $ashopurl;
if ($_SERVER['HTTPS'] == "on") $ashopbaseurl = str_replace("http://","https://",$ashopbaseurl);
if (strstr($_SERVER['HTTP_HOST'],"www.") && !strstr($ashopbaseurl,"www.")) $ashopbaseurl = str_replace("//","//www.",$ashopbaseurl);
else if (!strstr($_SERVER['HTTP_HOST'],"www.") && strstr($ashopbaseurl,"www.")) $ashopbaseurl = str_replace("www.","",$ashopbaseurl);

// Parse URL, title and shop name...
$template = str_replace("<!-- AShopBaseURL -->", $ashopbaseurl, $template);
$template = str_replace("<!-- AShopURL -->", $ashopurl, $template);
$template = str_replace("<!-- AShoptitle -->", $ashoptitle, $template);
$template = str_replace("<!-- AShopname -->", $ashopname, $template);

// Parse logo image tag...
include "includes/logo.inc.php";
$template = str_replace("<!-- AShoplogo -->", $ashoplogohtml, $template);

// Parse shopping mall tags...
if (!empty($shop) && !is_numeric($shop)) $shop = "";
$template = str_replace("<!-- AShopmember -->", $shop, $template);
$template = str_replace("<!-- AShopmemberheader -->", $pageheader, $template);
$template = str_replace("<!-- AShopmemberfooter -->", $pagefooter, $template);

// Parse number of products...
if (!empty($shop) && $shop > 1) $totalproductscount = @mysqli_query($db, "SELECT productid FROM product WHERE userid='$shop' AND (copyof='' OR copyof IS NULL) AND (prodtype != 'content' OR prodtype IS NULL)");
else $totalproductscount = @mysqli_query($db, "SELECT productid FROM product WHERE (copyof='' OR copyof IS NULL) AND (prodtype != 'content' OR prodtype IS NULL)");
$totalproductscount = @mysqli_num_rows($totalproductscount);
$template = str_replace("<!-- AShopnumberofproducts -->", $totalproductscount, $template);

// Parse CSS...
if (isset($shop) && is_numeric($shop) && $shop > 1) $template = str_replace("<!-- AShopcss -->", "includes/ashopcss.inc.php?shop=$shop", $template);
else $template = str_replace("<!-- AShopcss -->", "includes/ashopcss.inc.php", $template);

// Create newsletter subscription form as popup...
if (strpos($template,"<!-- AShopnewsletterform -->") !== false) {
	$shop = $currentshop;
	$layout = 2;
	$subscribe = "index.php";
	ob_start();
	include "includes/newsletter.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShopnewsletterform -->", $resulthtml, $template);
}

// Create newsletter subscription form...
if (strpos($template,"<!-- AShopnewsletterbox -->") !== false) {
	$shop = $currentshop;
	$layout = 1;
	$subscribe = "index.php";
	ob_start();
	include "includes/newsletter.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShopnewsletterbox -->", $resulthtml, $template);
}

// Create menu...
if (strpos($template,"<!-- AShopmenu -->") !== false && strpos($template,"<!-- /AShopmenu -->") !== false) {
	$shop = $currentshop;
	ob_start();
	include "includes/menu.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShopmenu -->","<!-- /AShopmenu -->",$resulthtml);
}

// Create search form...
if (strpos($template,"<!-- AShopsearchbox -->") !== false) {
	$shop = $currentshop;
	$layout = 3;
	$search = "index.php";
	ob_start();
	include "includes/topform.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShopsearchbox -->", $resulthtml, $template);
}

// Create shopping mall shops list...
if (strpos($template,"<!-- AShopmembers -->") !== false) {
	$shop = $currentshop;
	$layout = 1;
	ob_start();
	include "includes/shops.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShopmembers -->", $resulthtml, $template);
}

// Create customer profile links...
if (strpos($template,"<!-- AShopcustomerlinks -->") !== false) {
	$shop = $currentshop;
	$layout = 6;
	ob_start();
	include "includes/topform.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShopcustomerlinks -->", $resulthtml, $template);
}

// Create breadcrumbs...
if (strpos($template,"<!-- AShopbreadcrumbs -->") !== false && strpos($template,"<!-- /AShopbreadcrumbs -->") !== false) {
	$shop = $currentshop;
	$showcategoryname = "off";
	ob_start();
	include "includes/breadcrumbs.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShopbreadcrumbs -->","<!-- /AShopbreadcrumbs -->",$resulthtml);
}

// Create category directory...
if (strpos($template,"<!-- AShopdirectory -->") !== false && strpos($template,"<!-- /AShopdirectory -->") !== false) {
	$shop = $currentshop;
	$directorycolumns = 2;
	ob_start();
	include "includes/directory.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShopdirectory -->","<!-- /AShopdirectory -->",$resulthtml);
}

// Create standard categories list...
if (strpos($template,"<!-- AShopcategories -->") !== false && strpos($template,"<!-- /AShopcategories -->") !== false) {
	$shop = $currentshop;
	$layout = 1;
	$catalog = "index.php";
	ob_start();
	include "includes/categories.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShopcategories -->","<!-- /AShopcategories -->",$resulthtml);
}

// Create unordered categories list for CSS styling...
if (strpos($template,"<!-- AShopcategorieslist -->") !== false && strpos($template,"<!-- /AShopcategorieslist -->") !== false) {
	$shop = $currentshop;
	$layout = 2;
	$catalog = "index.php";
	ob_start();
	include "includes/categories.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShopcategorieslist -->","<!-- /AShopcategorieslist -->",$resulthtml);
}
			
// Create manufacturers list...
if (strpos($template,"<!-- AShopmanufacturers -->") !== false && strpos($template,"<!-- /AShopmanufacturers -->") !== false) {
	$shop = $currentshop;
	$layout = 1;
	$catalog = "index.php";
	ob_start();
	include "includes/manufacturers.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShopmanufacturers -->","<!-- /AShopmanufacturers -->",$resulthtml);
}

// Create unordered manufacturers list for CSS styling...
if (strpos($template,"<!-- AShopmanufacturerslist -->") !== false && strpos($template,"<!-- /AShopmanufacturerslist -->") !== false) {
	$shop = $currentshop;
	$layout = 2;
	$catalog = "index.php";
	ob_start();
	include "includes/manufacturers.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShopmanufacturerslist -->","<!-- /AShopmanufacturerslist -->",$resulthtml);
}

// Convert old topandlatest codes to just toplist for some backwards compatibility...
$template = str_replace("<!-- AShoptopandlatest -->","<!-- AShoptoplist -->",$template);
$template = str_replace("<!-- /AShoptopandlatest -->","<!-- /AShoptoplist -->",$template);

// Create top list of best selling shopping mall shops...
if (strpos($template,"<!-- AShoptopshoplist -->") !== false && strpos($template,"<!-- /AShoptopshoplist -->") !== false) {
	$redirect="index.php";
	$layout = 1;
	ob_start();
	include "includes/topshops.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShoptopshoplist -->","<!-- /AShoptopshoplist -->",$resulthtml);
}

// Create only top list...
if (strpos($template,"<!-- AShoptoplist -->") !== false && strpos($template,"<!-- /AShoptoplist -->") !== false) {
	$shop = $currentshop;
	$redirect="index.php";
	$layout = 1;
	ob_start();
	include "includes/toplist.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShoptoplist -->","<!-- /AShoptoplist -->",$resulthtml);
}

// Create only latest additions...
if (strpos($template,"<!-- AShoplatest -->") !== false && strpos($template,"<!-- /AShoplatest -->") !== false) {
	$shop = $currentshop;
	$redirect="index.php";
	$layout = 2;
	ob_start();
	include "includes/toplist.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShoplatest -->","<!-- /AShoplatest -->",$resulthtml);
}

// Create top bar with cart buttons and subtotal...
if (strpos($template,"<!-- AShoptopbar -->") !== false && strpos($template,"<!-- /AShoptopbar -->") !== false) {
	$shop = $currentshop;
	$search = "off";
	$layout = 1;
	ob_start();
	include "includes/topform.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShoptopbar -->","<!-- /AShoptopbar -->",$resulthtml);
}

// Create product list or search results...
if (strpos($template,"<!-- AShopstart -->") !== false && strpos($template,"<!-- AShopend -->") !== false) {
	$shop = $currentshop;
	$topform = "off";
	$categories = "off";
	ob_start();
	if($searchstring) {
		$search = "index.php";
		include "includes/search.inc.php";
	} else if ($product) {
		$catalog = "index.php";
		include "product.php";
	} else { 
		$catalog = "index.php";
		include "includes/catalog.inc.php";
	}
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShopstart -->","<!-- AShopend -->",$resulthtml);
}

// Create subtotal box...
if (strpos($template,"<!-- AShopsubtotal -->") !== false) {
	$shop = $currentshop;
	$layout = 4;
	$customerlogin = "off";
	ob_start();
	include "includes/topform.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShopsubtotal -->", $resulthtml, $template);
}

// Create shopping cart buttons...
if (strpos($template,"<!-- AShopcartbuttons -->") !== false) {
	$shop = $currentshop;
	$layout = 5;
	ob_start();
	include "includes/topform.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShopcartbuttons -->", $resulthtml, $template);
}

// Create language selector...
if (strpos($template,"<!-- AShoplanguages -->") !== false) {
	$shop = $currentshop;
	$redirect="index.php";
	ob_start();
	include "includes/language.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShoplanguages -->", $resulthtml, $template);
}
		
// Create currency selector...
if (strpos($template,"<!-- AShopcurrencies -->") !== false) {
	$shop = $currentshop;
	$redirect="index.php";
	$currencies="usd,cad,aud,eur";
	ob_start();
	include "includes/currency.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShopcurrencies -->", $resulthtml, $template);
}

// Create featured product carousel...
if (strpos($template,"<!-- AShopcarousel -->") !== false) {
	ob_start();
	include "includes/carousel.inc.php";
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = str_replace("<!-- AShopcarousel -->", $resulthtml, $template);
}
		
// Create news feed reader...
if (strpos($template,"<!-- AShopnews -->") !== false && strpos($template,"<!-- /AShopnews -->") !== false && !empty($ashopnewsfeed)) {
	$shop = $currentshop;
	include "includes/simplepie.inc.php";
	$feed = new SimplePie();
	$feed->set_cache_location("./updates");
	$feed->set_cache_duration(900);
	$feed->set_feed_url($ashopnewsfeed);
	$feed->init();
	ob_start();
	if ($feed->data) {
		$items = $feed->get_items(0,5);
		foreach($items as $item) echo " &nbsp;<img src=\"images/bullet.gif\" alt=\"o\" /> &nbsp;<a href=\"".$item->get_permalink()."\" target=\"_blank\">".$item->get_title()."</a><br />";
	}
	$resulthtml = ob_get_contents();
	ob_end_clean();
	$template = ashop_parsetags($template,"<!-- AShopnews -->","<!-- /AShopnews -->",$resulthtml);
}

// Parse custom affiliate tags...
if ($affiliate) $template = ashop_parseaffiliatetags($template);

echo $template;
?>