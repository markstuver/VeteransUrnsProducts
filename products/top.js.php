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
// Module: toplist.js.php
// Input variables: items = number of items to show, redirect = where to link to,
// showgifts = 1, include free gifts in top list
// layout = 0 : show top sellers and latest additions below top sellers
// layout = 1 : show only top sellers
// layout = 2 : show only latest additions
// layout = 3 : show latest additions as boxes

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";
// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/topjs.inc.php";
if ($lang != $defaultlanguage) $toplistlang = $lang;
else $toplistlang = "";

// Validate $layout...
if (isset($layout) && !is_numeric($layout)) unset($layout);
if (isset($layout) && ($layout > 3 || $layout < 0)) unset($layout);
if (!isset($layout)) $layout = 0;

// Validate $shop...
if (isset($shop) && !is_numeric($shop)) unset($shop);

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$redirect) {
	$redirect = "index.php";
}

if (!$items || !is_numeric($items)) $items = 10;

// Include styles...
echo "document.write('<style type=\"text/css\">');\n";
echo "document.write('.ashopboxtable { width: 200px; border: 1px solid $catalogheader; text-align: left; }');\n";
echo "document.write('.ashopboxheader { background-color: $catalogheader; padding: 5px; font-family: $font; font-size: {$fontsize2}px; color: $catalogheadertext; font-weight: bold; }');\n";
echo "document.write('.ashopboxcontent { background-color: $categorycolor; font-family: $font; font-size: {$fontsize2}px; color: $categorytextcolor; }');\n";
echo "document.write('</style>');\n";

// Show top sellers...
if ($layout == 0 || $layout == 1) {
	// Check if there is a cached top list available...
	$date = date("Ymd", time()+$timezoneoffset);
	if (file_exists("$ashoppath/updates/topjs{$toplistlang}{$shop}{$layout}{$date}")) {
		$fp = fopen ("$ashoppath/updates/topjs{$toplistlang}{$shop}{$layout}{$date}","r");
		if ($fp) {
			while (!feof ($fp)) echo fgets($fp, 4096);
			fclose($fp);
		}
	} else {
		if (is_dir("$ashoppath/updates") && is_writable("$ashoppath/updates")) {
			// Remove old top lists...
			$findfile = opendir("$ashoppath/updates");
			if (isset($findfile)) while (false !== ($foundfile = readdir($findfile))) {
				if (substr($foundfile,0,5) == "topjs{$toplistlang}{$shop}{$layout}" && !is_dir("$ashoppath/updates/$foundfile")) unlink("$ashoppath/updates/$foundfile");
			}
			// Create new top list...
			$fp = @fopen("$ashoppath/updates/topjs{$toplistlang}{$shop}{$layout}{$date}", "w");
		}

		echo "document.write('<table class=\"ashopboxtable\" cellspacing=\"0\"><tr><td class=\"ashopboxheader\">&nbsp;&nbsp;&nbsp;".TOP10."</td></tr><tr><td class=\"ashopboxcontent\">');\n";
		if ($fp) @fwrite($fp, "document.write('<table class=\"ashopboxtable\" cellspacing=\"0\"><tr><td class=\"ashopboxheader\">&nbsp;&nbsp;&nbsp;".TOP10."</td></tr><tr><td class=\"ashopboxcontent\">');\n");

		if (!empty($shop)) $result = @mysqli_query($db, "SELECT products FROM orders WHERE paid != '' AND (language='$lang' OR language IS NULL OR language='') AND userid LIKE '%|$shop|%' ORDER BY paid DESC LIMIT 500");
		else $result = @mysqli_query($db, "SELECT products FROM orders WHERE paid != '' AND (language='$lang' OR language IS NULL OR language='') ORDER BY paid DESC LIMIT 500");
		while ($row = @mysqli_fetch_array($result)) {
			$parsed_products = ashop_quickparseproductstring($db,$row["products"]);
			if ($parsed_products) foreach ($parsed_products as $productnumber=>$productinfo) {
				if ($productinfo["productid"] && $productinfo["active"] && ($showgifts == "1" || $productinfo["price"] > 0) && ($productinfo["userid"] == $shop || empty($shop))) {
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
				$productname = addslashes($productname);
				echo "document.write('$productnumber. <a href=\"$redirect?product=$productid\">$productname</a><br>');\n";
				if ($fp) @fwrite($fp, "document.write('$productnumber. <a href=\"$redirect?product=$productid\">$productname</a><br>');\n");
				if ($show == "pictures") {
					// Get product image info...
					$productimage = ashop_productimages($productid);
					$picture = $productimage["thumbnail"];
					echo "document.write('<center><a href=\"$redirect?product=$productid\"><img src=\"$ashopurl/prodimg/$productid/$picture\" border=\"0\"></a></center><br><br>');\n";
					if ($fp) @fwrite($fp, "document.write('<center><a href=\"$redirect?product=$productid\"><img src=\"$ashopurl/prodimg/$productid/$picture\" border=\"0\"></a></center><br>');\n");
				}
				$productnumber++;
				if ($productnumber > $items) break;
			}
		}
		echo "document.write('</td></tr></table>');\n";
		if ($fp) @fwrite($fp, "document.write('</td></tr></table>');\n");
		if ($fp) @fclose($fp);
	}
	if ($layout == 0) echo "document.write('<br>');\n";
}

// Show latest additions...
if ($layout == 0 || $layout == 2) {
	echo "document.write('<table class=\"ashopboxtable\" cellspacing=\"0\"><tr><td class=\"ashopboxheader\">&nbsp;&nbsp;&nbsp;".LATEST."</td></tr><tr><td class=\"ashopboxcontent\">');\n";
	
	if (!empty($shop)) $result = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND userid='$shop' AND (prodtype!='content' OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) ORDER BY productid DESC LIMIT 100");
	else $result = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND (prodtype!='content' OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) ORDER BY productid DESC LIMIT 100");
	$productnumber = 1;
	while ($row = @mysqli_fetch_array($result)) {
		if ($productnumber > $items) break;
		$checklanguage = @mysqli_query($db, "SELECT * FROM category, productcategory WHERE productcategory.productid='{$row["productid"]}' AND (category.language='$lang' OR category.language='' OR category.language IS NULL OR category.language='any') AND category.categoryid=productcategory.categoryid");
		if (@mysqli_num_rows($checklanguage)) {
			echo "document.write('$productnumber. <a href=\"$redirect?product={$row["productid"]}\">".addslashes($row["name"])."</a><br>');\n";
			if ($show == "pictures") {
				// Get product image info...
				$productimage = ashop_productimages($row["productid"]);
				$picture = $productimage["thumbnail"];
				echo "document.write('<center><a href=\"$redirect?product={$row["productid"]}\"><img src=\"$ashopurl/prodimg/{$row["productid"]}/$picture\" border=\"0\"></a></center><br>');\n";
			}
			$productnumber++;
		} else echo "document.write('Language: $lang<br>');\n";
	}

	echo "document.write('</td></tr></table>');\n";
}

if ($layout == 3) {
	
/*	COMMENTED OUT TO REMOVE EXTRA SPACE THAT WAS THE CODE GENERATED HEADER
	echo "document.write('<div class=\"ashoppageheadertext2\" align=\"center\"><span style=\"font-size: 18px;\"><b>".LATEST."</b></span></div><br><table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">');\n";
*/
	if (!empty($shop)) $latestresult = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND userid='$shop' AND (prodtype!='content' OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) ORDER BY productid DESC LIMIT $items");
	else $latestresult = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND (prodtype!='content' OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) ORDER BY productid DESC LIMIT $items");
	$productnumber = 0;
	while ($latestrow = @mysqli_fetch_array($latestresult)) {
		if ($productnumber > 1) $productnumber = 0;
		
		/*CHANGED from isEqual to isNotEqual so the the recently added items line up correctly*/
		/*if ($productnumber == 0) echo "document.write('<tr>');\n";*/
		if ($productnumber != 0) echo "document.write('<tr>');\n";
		
		/* REMOVE BORDER AROUND RECENTLY ADDED ITEMS
		echo "document.write('<td width=\"50%\" valign=\"top\" align=\"center\"><table width=\"200\" cellpadding=\"5\" cellspacing=\"0\" style=\"border: 1px solid #000000;\"><tr><td align=\"center\">";*/
		echo "document.write('<td width=\"50%\" valign=\"top\" align=\"center\"><table width=\"200\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\">";
		$feature = $latestrow["productid"];
		include "includes/product.inc.php";
		echo "</td></tr></table><br></td>');\n";
		if ($productnumber == 1) echo "document.write('</tr>');\n";
		$productnumber++;
	}
	echo "document.write('</table>');\n";
}

@mysqli_close($db);
?>