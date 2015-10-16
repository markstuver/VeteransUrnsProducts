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
// --------------------------------------------------------------------
// Module: toplist.inc.php
// Input variables: items = number of items to show, redirect = where to link to,
// showgifts = 1, include free gifts in top list
// layout = 1 : show only top sellers
// layout = 2 : show only latest additions
// mode = table : format the output as a table
// mode = list: format the output as an unordered list

// Include configuration file and functions...
if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/topjs.inc.php";
if (!empty($defaultlanguage) && $lang != $defaultlanguage) $toplistlang = $lang;
else $toplistlang = "";

// Validate $layout...
if (isset($layout) && !is_numeric($layout)) unset($layout);
if (isset($layout) && ($layout > 2 || $layout < 0)) unset($layout);
if (!isset($layout)) $layout = 0;

// Validate $mode...
if (isset($mode) && !empty($mode)) {
	if ($mode != "table" && $mode != "list") $mode = "";
}

// Validate $shop...
if (isset($shop) && !is_numeric($shop)) $shop = "";

if (!$redirect) {
	$redirect = "index.php";
}

if (!$items || !is_numeric($items)) $items = 10;

// Show top sellers...
if ($layout == 1) {
	// Check if there is a cached top list available...
	$date = date("Ymd", time()+$timezoneoffset);
	if (file_exists("$ashoppath/updates/top{$toplistlang}{$mode}{$shop}{$layout}{$date}")) {
		$fp = fopen ("$ashoppath/updates/top{$toplistlang}{$mode}{$shop}{$layout}{$date}","r");
		if ($fp) {
			while (!feof ($fp)) echo fgets($fp, 4096);
			fclose($fp);
		}
	} else {
		if (is_dir("$ashoppath/updates") && is_writable("$ashoppath/updates")) {
			// Remove old top lists...
			$findfile = opendir("$ashoppath/updates");
			if (isset($findfile)) while (false !== ($foundfile = readdir($findfile))) {
				$checklen = strlen("top{$toplistlang}{$mode}{$shop}{$layout}");
				if (substr($foundfile,0,$checklen) == "top{$toplistlang}{$mode}{$shop}{$layout}" && !is_dir("$ashoppath/updates/$foundfile")) unlink("$ashoppath/updates/$foundfile");
			}
			// Create new top list...
			$fp = @fopen("$ashoppath/updates/top{$toplistlang}{$mode}{$shop}{$layout}{$date}", "w");
		}

		if (!empty($shop)) $result = @mysqli_query($db, "SELECT products FROM orders WHERE paid != '' AND (language='$lang' OR language IS NULL OR language='') AND userid LIKE '%|$shop|%' ORDER BY paid DESC LIMIT 500");
		else $result = @mysqli_query($db, "SELECT products FROM orders WHERE paid != '' AND (language='$lang' OR language IS NULL OR language='') ORDER BY paid DESC LIMIT 500");

		if (@mysqli_num_rows($result)) {

			if ($mode == "list") {
				echo "<ul>\n";
				if ($fp) @fwrite($fp, "<ul>\n");
			}

			while ($row = @mysqli_fetch_array($result)) {
				$parsed_products = ashop_quickparseproductstring($db,$row["products"]);
				if ($parsed_products) foreach ($parsed_products as $productnumber=>$productinfo) {
					if ($productinfo["productid"] && $productinfo["active"] && ($showgifts == "1" || $productinfo["price"] > 0) && (empty($shop) || $productinfo["userid"] == $shop)) {
						$products["{$productinfo["productid"]}"] += $productinfo["quantity"];
						$productnames["{$productinfo["productid"]}"] = $productinfo["name"];
					}
				}
			}
			$productnumber = 1;
			if ($products) {
				@arsort($products);
				foreach ($products as $productid=>$quantity) {
					$productname = $productnames["$productid"];
					//$productname = addslashes($productname);
					if ($show == "names" || empty($show)) {
						if (empty($mode) || $mode == "table") {
							echo "$productnumber. <a href=\"$redirect?product=$productid\">$productname</a><br />\n";
							if ($fp) @fwrite($fp, "$productnumber. <a href=\"$redirect?product=$productid\">$productname</a><br />\n");
						} else if ($mode == "list") {
							echo "<li>$productnumber. <a href=\"$redirect?product=$productid\">$productname</a></li>\n";
							if ($fp) @fwrite($fp, "<li>$productnumber. <a href=\"$redirect?product=$productid\">$productname</a></li>\n");
						}
					} else if ($show == "pictures") {
						// Get product image info...
						$productimage = ashop_productimages($productid);
						$picture = $productimage["thumbnail"];
						if (empty($mode) || $mode == "table") {
							echo "<a href=\"$redirect?product=$productid\"><img src=\"prodimg/$productid/$picture\" border=\"0\"></a><br /><br />\n";
							if ($fp) @fwrite($fp, "<a href=\"$redirect?product=$productid\"><img src=\"prodimg/$productid/$picture\" border=\"0\"></a><br /><br />\n");
						} else if ($mode == "list") {
							echo "<li><a href=\"$redirect?product=$productid\"><img src=\"prodimg/$productid/$picture\" border=\"0\"></a></li>\n";
							if ($fp) @fwrite($fp, "<li><a href=\"$redirect?product=$productid\"><img src=\"prodimg/$productid/$picture\" border=\"0\"></a></li>\n");
						}
					}
					$productnumber++;
					if ($productnumber > $items) break;
				}
			}
			if ($mode == "list") {
				echo "</ul>";
				if ($fp) @fwrite($fp, "</ul>\n");
			}
		}
		fclose($fp);
	}
}

// Show latest additions...
if ($layout == 2) {
	if (!empty($shop)) $result = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND userid='$shop' AND (prodtype!='content' OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) ORDER BY productid DESC LIMIT 100");
	else $result = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND (prodtype!='content' OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) ORDER BY productid DESC LIMIT 100");
	
	if (@mysqli_num_rows($result)) {
		if ($mode == "list") echo "<ul>\n";
		
		$productnumber = 1;
		$names = array();
		while ($row = @mysqli_fetch_array($result)) {
			$picture = "";
			if ($productnumber > $items) break;
			$checklanguage = @mysqli_query($db, "SELECT * FROM category, productcategory WHERE productcategory.productid='{$row["productid"]}' AND (category.language='$lang' OR category.language='any' OR category.language='' OR category.language IS NULL) AND category.categoryid=productcategory.categoryid");
			if (@mysqli_num_rows($checklanguage)) {
				if (!in_array($row["name"],$names)) {
					$names[] .= $row["name"];
					if (empty($show) || $show == "names") {
						if (empty($mode) || $mode == "table") echo "$productnumber. <a href=\"$redirect?product={$row["productid"]}\">{$row["name"]}</a><br />\n";
						else if ($mode == "list") echo "<li>$productnumber. <a href=\"$redirect?product={$row["productid"]}\">{$row["name"]}</a></li>\n";
					} else if ($show == "pictures") {
						// Get product image info...
						$productimage = ashop_productimages($row["productid"]);
						$picture = $productimage["thumbnail"];
						if (empty($mode) || $mode == "table") echo "<a href=\"$redirect?product={$row["productid"]}\"><img src=\"prodimg/{$row["productid"]}/$picture\" border=\"0\"></a><br /><br />\n";
						else if ($mode == "list") echo "<li><a href=\"$redirect?product={$row["productid"]}\"><img src=\"prodimg/$picture\" border=\"0\"></a></li>\n";
					}
					$productnumber++;
				}
			}
		}
		if ($mode == "list") echo "</ul>\n";
	}
}

$layout = "";
?>