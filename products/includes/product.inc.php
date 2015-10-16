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

// Check if the script is run in JavaScript mode...
if ($_GET["mode"] == "js") {
	$mode = "js";

	// Include configuration file and functions...
	if (!$databaseserver || !$databaseuser) include "../admin/config.inc.php";
	if (empty($currencysymbols)) include "../admin/ashopconstants.inc.php";
	if (!function_exists(ashop_mailsafe)) include "../admin/ashopfunc.inc.php";
} else {
	$mode = "";

	// Include configuration file and functions...
	if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
	if (!function_exists(ashop_mailsafe)) include "admin/ashopfunc.inc.php";
}

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Set default values if necessary...
if (empty($shop) || !is_numeric($shop)) $shop = "1";
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;

// Validate variables...
if ($feature != "AShopAboutPage" && $feature != "AShopFirstPage" && $feature != "AShopFirstPageMobile" && $feature != "AShopTermsPage" && $feature != "AShopPrivacyPage" && $feature != "AShopPage" && !is_numeric($feature)) exit;

// Check if a content page has been requested...
if ($feature == "AShopAboutPage" || $feature == "AShopFirstPage" || $feature == "AShopFirstPageMobile" || $feature == "AShopTermsPage" || $feature == "AShopPrivacyPage" || $feature == "AShopPage") {
	if ($feature == "AShopPage" && !empty($id) && is_numeric($id)) $featuredproductresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$id' AND userid='$shop' AND (language='$lang' OR language='any' OR language IS NULL)");
	else $featuredproductresult = @mysqli_query($db, "SELECT * FROM product WHERE name='$feature' AND userid='$shop' AND (language='$lang' OR language='any' OR language IS NULL)");
	$contentpage = TRUE;
} else {
	$featuredproductresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$feature'");
	$contentpage = FALSE;
}
if (@mysqli_num_rows($featuredproductresult)) {
	$featuredproductrow = @mysqli_fetch_array($featuredproductresult);
	$featuredproducturl = $featuredproductrow["detailsurl"];
	$featuredproductname = $featuredproductrow["name"];
	$featuredproductdescr = $featuredproductrow["description"];
	$featuredproductdescr = ashop_parseaffiliatetags($featuredproductdescr);
	if (!$contentpage) {
		if (substr($featuredproductdescr,0,3) == "<p>") $featuredproductdescr = substr($featuredproductdescr,3);
		if (substr($featuredproductdescr,-4) == "</p>") $featuredproductdescr = substr($featuredproductdescr,0,strlen($featuredproductdescr)-4);
	}
	if (!empty($featuredproductrow["pricetext"])) {
		$featuredproductprice = $featuredproductrow["pricetext"];
		$featuredproductprice = str_replace("\r\n","",$featuredproductprice);
		$featuredproductprice = str_replace("\n","",$featuredproductprice);
		$featuredproductprice = str_replace("'","&#039;",$featuredproductprice);
	} else $featuredproductprice = $featuredproductrow["price"];
	$featuredproductinv = $featuredproductrow["inventory"];
	if (empty($featuredproductinv)) $featuredproductinv = 0;
	if (!$featuredproducturl) {
		$featuredproducturl = "$ashopurl/index.php?product=$feature";
	}
	if ($mode == "js") {
		$featuredproductdescr = str_replace("\r\n","",$featuredproductdescr);
		$featuredproductdescr = str_replace("\n","",$featuredproductdescr);
		$featuredproductdescr = str_replace("'","&#039;",$featuredproductdescr);
		$featuredproductdescr = substr($featuredproductdescr,0,247)."...";
		if (!$contentpage) {
			if ($show == "name") echo "document.write('<a href=\"$featuredproducturl\" style=\"text-decoration: none;\">$featuredproductname</a>');\n";
			else if ($show == "description") echo "document.write('$featuredproductdescr');\n";
			else if ($show == "image") {
				if (file_exists("$ashoppath/prodimg/$feature.gif")) echo "document.write('<a href=\"$featuredproducturl\"><img src=\"$ashopurl/prodimg/$feature.gif\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a>');\n";
				else if (file_exists("$ashoppath/prodimg/$feature.jpg")) echo "document.write('<a href=\"$featuredproducturl\"><img src=\"$ashopurl/prodimg/$feature.jpg\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a>');\n";
			}
			else if ($show == "price") {
				if (!empty($featuredproductrow["pricetext"])) echo "document.write('$featuredproductprice');\n";
				else echo "document.write('".$currencysymbols[$ashopcurrency]["pre"].number_format($featuredproductprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."');\n";
			}
			else if ($show == "inventory") echo "document.write('$featuredproductinv');\n";
			else {
				echo "document.write('<span class=\"ashopproductname\"><a href=\"$featuredproducturl\" style=\"text-decoration: none;\">$featuredproductname</a></span>');\n";
				if (file_exists("$ashoppath/prodimg/$feature.gif")) echo "document.write('<br><br><a href=\"$featuredproducturl\"><img src=\"$ashopurl/prodimg/$feature.gif\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>');\n";
				else if (file_exists("$ashoppath/prodimg/$feature.jpg")) echo "document.write('<br><br><a href=\"$featuredproducturl\"><img src=\"$ashopurl/prodimg/$feature.jpg\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>');\n";
				if ($description != "off") echo "document.write('<br><span class=\"ashopproducttext\">$featuredproductdescr</span>');\n";
			}
		} else {
			for ($ffeature = 1; $ffeature <= 10; $ffeature++) $featuredproductdescr = str_replace("%feature{$ffeature}%","",$featuredproductdescr);
			echo "document.write('$featuredproductdescr');\n";
		}
	} else {
		if (!$contentpage) {
			echo "<span class=\"ashopproductname\"><a href=\"$featuredproducturl\" style=\"text-decoration: none;\">$featuredproductname</a></span>";
			if (file_exists("$ashoppath/prodimg/$feature.gif")) echo "<br><br><a href=\"$featuredproducturl\"><img src=\"prodimg/$feature.gif\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>";
			else if (file_exists("$ashoppath/prodimg/$feature.jpg")) echo "<br><br><a href=\"$featuredproducturl\"><img src=\"prodimg/$feature.jpg\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>";
			echo "<br><span class=\"ashopproducttext\">$featuredproductdescr</span>";
		} else {
			for ($ffeature = 1; $ffeature <= 10; $ffeature++) {
				$ffeaturehtml = "";
				$ffeaturecheck = strpos($featuredproductdescr, "%feature{$ffeature}%");
				if ($ffeaturecheck === false) {
				} else {
					$ffeaturedproductresult = @mysqli_query($db, "SELECT * FROM product WHERE featured='$ffeature' AND (prodtype != 'content' OR prodtype IS NULL)");
					if (@mysqli_num_rows($featuredproductresult)) {
						$ffeaturedproductrow = @mysqli_fetch_array($ffeaturedproductresult);
						$ffeaturedproductid = $ffeaturedproductrow["productid"];
						$ffeaturedproducturl = $ffeaturedproductrow["detailsurl"];
						$ffeaturedproductname = $ffeaturedproductrow["name"];
						$ffeaturedproductdescr = $ffeaturedproductrow["description"];
						if (!$ffeaturedproducturl) {
							$ffeaturedproducturl = "$ashopurl/index.php?product=$ffeaturedproductid";
						}
						$ffeaturehtml = "<span class=\"ashopproductname\"><a href=\"$ffeaturedproducturl\" style=\"text-decoration: none;\">$ffeaturedproductname</a></span>";
						if (file_exists("$ashoppath/prodimg/$ffeaturedproductid.gif")) $ffeaturehtml .= "<br><br><a href=\"$ffeaturedproducturl\"><img src=\"prodimg/$ffeaturedproductid.gif\" alt=\"$ffeaturedproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>";
						else if (file_exists("$ashoppath/prodimg/$ffeaturedproductid.jpg")) $ffeaturehtml .= "<br><br><a href=\"$ffeaturedproducturl\"><img src=\"prodimg/$ffeaturedproductid.jpg\" alt=\"$ffeaturedproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>";
						else $ffeaturehtml .= "<br><span class=\"ashopproducttext\">$ffeaturedproductdescr</span>";
					}
					$featuredproductdescr = str_replace("%feature{$ffeature}%",$ffeaturehtml,$featuredproductdescr);
				}
			}
			echo $featuredproductdescr;
		}
	}
}

// Clean up...
$feature = "";
?>